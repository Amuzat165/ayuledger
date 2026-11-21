<?php

namespace App\Http\Controllers;

use App\BankCash;
use App\Models\CustomerPayments;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\CustomerPaymentReceipt;
use App\Services\CustomerPaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class CustomerReceiptController extends Controller
{
    public function create()
    {
        // Load customers along with their related branch data
        $customers = Customer::has('invoices')
            ->with('branch') // Eager load branch data
            ->get();
        $invoices = [];

        if (request()->filled('customer_id')) {
            $customerId = request()->input('customer_id');
            $customer = Customer::with('branch')->find($customerId); // Include branch relation here too

            if (!$customer) {
                return response()->json(['error' => 'Customer not found'], 404);
            }

            $invoices = $customer->invoices()
                ->get()
                ->map(function ($invoice) {
                    $invoice->total_amount = $invoice->total_due;
                    try {
                        $invoice->balance_due = app(CustomerPaymentService::class)->balanceFor($invoice);
                        $invoice->is_paid = ((int) round((float) $invoice->balance_due * 100) === 0);
                        $invoice->payment_eligible = true;
                    } catch (\Illuminate\Validation\ValidationException $e) {
                        $invoice->balance_due = null;
                        $invoice->is_paid = false;
                        $invoice->payment_eligible = false;
                    }
                    return $invoice;
                });
        }

        $paymentModes = BankCash::all();

        return view('pages.customers.receipts.create', [
            'title' => 'Add Receipt',
            'resource' => 'Receipts',
            'type' => 'form',
            'action' => '',
            'customer_id_options' => $customers,
            'invoices' => $invoices,
            'payment_modes' => $paymentModes,
            'currency_symbol' => config('settings.currency_symbol', config('settings.currency_code', '')),
        ]);
    }

    public function getInvoices($customerId)
    {
        $customer = Customer::with('branch')->find($customerId);
        if (!$customer) {
            return response()->json(['error' => 'Customer not found'], 404);
        }

        // Fetch invoices with their items
        $invoices = $customer->invoices()
            ->with(['items' => function($query) {
                $query->selectRaw('invoice_id, SUM(amount) as total_amount')->groupBy('invoice_id');
            }])
            ->get()
            ->map(function ($invoice) {
                // Calculate total amount for the invoice
                $invoice->total_amount = $invoice->total_due;

                // Check if there is a corresponding payment and get the balance_due
                $payment = CustomerPayments::where('invoice_id', $invoice->id)->first();
                if ($payment) {
                    $invoice->balance_due = $payment->balance_due;
                    // Determine if the invoice is paid
                    $invoice->is_paid = $payment->balance_due == 0.00;
                } else {
                    $invoice->balance_due = $invoice->total_amount; // No payment, set balance due to total amount
                    $invoice->is_paid = false; // Not paid
                }

                return $invoice;
            });

        return response()->json([
            'invoices' => $invoices,
            'branch' => $customer->branch
        ]);
    }

    public function submitPayment(Request $request, $invoiceId)
    {
        // Validate the incoming request
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_mode_id' => 'required|exists:bank_cashes,id',
            'total_amount_due' => 'required|numeric|min:0',
        ]);

        // Find the invoice by ID or throw an exception
        $invoice = CustomerInvoice::findOrFail($invoiceId);
        $paymentAmount = $request->input('amount');
        $paymentModeId = $request->input('payment_mode_id');
        $totalAmountDue = $request->input('total_amount_due');

        // Check if a payment already exists for this invoice
        $existingPayment = CustomerPayments::where('invoice_id', $invoiceId)->first();

        if ($existingPayment) {
            // Update the existing payment
            $existingPayment->amount_paid += $paymentAmount; // Accumulate the amount paid
            $existingPayment->balance_due = $totalAmountDue - $existingPayment->amount_paid; // Update balance due
            $existingPayment->payment_mode_id = $paymentModeId;
            $existingPayment->save(); // Save the updated payment record
        } else {
            // Create a new payment record
            CustomerPayments::create([
                'customer_id' => $invoice->customer_id,
                'invoice_id' => $invoiceId,
                'total_amount' => $totalAmountDue,
                'amount_paid' => $paymentAmount,
                'balance_due' => $totalAmountDue - $paymentAmount,
                'payment_mode_id' => $paymentModeId,
            ]);
        }

        // Generate the receipt URL
        $receiptUrl = route('customers.invoices.pdf', $invoiceId);

        // Return a JSON response with the receipt URL
        return response()->json([
            'success' => true,
            'receipt_url' => $receiptUrl,
            'redirect' => route('customers.receipts.view') // redirect for backend
        ]);
    }



    public function generateReceiptPdf($invoiceId)
    {
        $latestEvent = CustomerPaymentReceipt::whereHas('allocations', function ($query) use ($invoiceId) {
            $query->where('invoice_id', $invoiceId);
        })->latest('received_at')->first();
        if ($latestEvent) {
            return redirect()->route('customer.payment_receipts.pdf', $latestEvent->id);
        }

        // Fetch the latest payment for the given invoice
        $payment = CustomerPayments::where('invoice_id', $invoiceId)
            ->latest('created_at') // Get the latest payment
            ->with(['customer', 'invoice', 'paymentMode']) // Load relationships
            ->firstOrFail();

        // Retrieve the payment mode name (or set 'N/A' if not available)
        $paymentModeName = $payment->paymentMode ? $payment->paymentMode->name : 'N/A';

        // Get company settings (header image, logo, company color, VAT)
        $headerImage = Config::get('settings.header_image', null);
        $companyLogo = Config::get('settings.company_logo', 'upload/company-logo/e.png');
        $companyName = Config::get('settings.company_name', 'AYUGLOBAL');
        $companyColor = Config::get('settings.company_color', '#00897B');
        $address_1 = Config::get('settings.address_1', '');
        $address_2 = Config::get('settings.address_2', '');
        $city = Config::get('settings.city', '');
        $phone = Config::get('settings.phone', '');
        $email = Config::get('settings.email', '');
        // A legacy aggregate payment is not a new invoice calculation; never recompute VAT on it.
        $vat_percentage = 0;

        // Load the PDF view with the payment and payment mode details
        $pdf = Pdf::loadView('pages.customers.receipts.pdf', [
            'payment' => $payment,
            'payment_mode' => $paymentModeName,
            'header_image' => $headerImage,
            'company_logo' => $companyLogo,
            'company_name' => $companyName,
            'company_color' => $companyColor,
            'address_1' => $address_1,
            'address_2' => $address_2,
            'city' => $city,
            'phone' => $phone,
            'email' => $email,
            'vat_percentage' => $vat_percentage,
            'currency_symbol' => Config::get('settings.currency_symbol', Config::get('settings.currency_code', '')),
        ]);

        // Configure PDF
        $pdf->setPaper('A4');

        // Stream the PDF file to the browser
        return $pdf->stream('receipt_' . $payment->id . '.pdf');
    }

    public function viewReceipts()
    {
        // Fetch all payment records with related customer, invoice, and payment mode details
        $payments = CustomerPayments::with(['customer', 'invoice', 'paymentMode'])->get();

        // Assuming payment_modes are also being passed to the view
        $payment_modes = BankCash::all();

        return view('pages.customers.receipts.view', [
            'title' => 'View Receipts',
            'resource' => 'Receipts',
            'type' => 'table',
            'payments' => $payments,
            'payment_modes' => $payment_modes,  // Passing payment modes to the view
        ]);
    }


    public function showPayments()
    {
        $payments = CustomerPayments::with(['invoice', 'customer', 'paymentMode'])->get(); // Eager load the relationships
        $payment_modes = BankCash::all(); // Fetch available payment modes (from BankCash)

        return view('pages.customers.receipts.view', compact('payments', 'payment_modes')); // Pass payment_modes to the view
    }


    public function pay(Request $request, $paymentId)
    {
        // Validate the request
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_mode_id' => 'required|exists:bank_cashes,id',
        ]);

        // Find the payment record by ID or throw an exception
        $payment = CustomerPayments::findOrFail($paymentId);
        $amountToPay = $request->input('amount');

        // Check if the payment amount exceeds the balance due
        if ($amountToPay > $payment->balance_due) {
            return response()->json(['error' => 'Payment amount exceeds balance due.']);
        }

        // Update the payment record
        $payment->amount_paid += $amountToPay; // Update the amount paid
        $payment->balance_due -= $amountToPay; // Update the balance due
        $payment->payment_mode_id = $request->input('payment_mode_id'); // Update payment mode if needed
        $payment->save(); // Save the changes

        $receiptUrl = route('customers.invoices.pdf', $payment->invoice_id);

        return response()->json(['success' => true, 'message' => 'Payment successful.', 'receipt_url' => $receiptUrl]);
    }

}
