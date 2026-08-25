<?php

namespace App\Http\Controllers;

use App\BankCash;
use App\Models\CustomerPayments;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class CustomerReceiptController extends Controller
{
    private $route;

    public function __construct()
    {
        $this->route = 'customers.receipts';
    }

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
                ->with(['items' => function($query) {
                    $query->selectRaw('invoice_id, SUM(amount) as total_amount')->groupBy('invoice_id');
                }])
                ->get()
                ->map(function ($invoice) {
                    $invoice->total_amount = $invoice->items->first()->total_amount ?? 0;
                    return $invoice;
                });
        }

        $paymentModes = BankCash::all();

        return view('pages.customers.receipts.create', [
            'title' => 'Add Receipt',
            'resource' => 'Receipts',
            'type' => 'form',
            'action' => route($this->route . '.store'),
            'customer_id_options' => $customers,
            'invoices' => $invoices,
            'payment_modes' => $paymentModes,
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
                $invoice->total_amount = $invoice->items->first()->total_amount ?? 0;

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
        $receiptUrl = route('invoices.receipt.pdf', $invoiceId); // assuming you have a route for generating the receipt PDF

        // Return a JSON response with the receipt URL
        return response()->json([
            'success' => true,
            'receipt_url' => $receiptUrl,
            'redirect' => route('customers.receipts.view') // redirect for backend
        ]);
    }



    public function generateReceiptPdf($invoiceId)
    {
        // Fetch the latest payment for the given invoice
        $payment = CustomerPayments::where('invoice_id', $invoiceId)
            ->latest('created_at') // Get the latest payment
            ->with(['customer', 'invoice', 'paymentMode']) // Load relationships
            ->firstOrFail();

        // Retrieve the payment mode name (or set 'N/A' if not available)
        $paymentModeName = $payment->paymentMode ? $payment->paymentMode->name : 'N/A';

        // Load the PDF view with the payment and payment mode details
        $pdf = Pdf::loadView('pages.customers.receipts.pdf', [
            'payment' => $payment,
            'payment_mode' => $paymentModeName, // Pass payment mode
        ]);

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

        $receiptUrl = route('invoices.receipt.pdf', $payment->invoice_id);

        return response()->json(['success' => true, 'message' => 'Payment successful.', 'receipt_url' => $receiptUrl]);
    }

}
