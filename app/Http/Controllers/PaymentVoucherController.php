<?php

namespace App\Http\Controllers;

use App\BankCash;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\CustomerPayments;
use App\Models\Vendor;
use App\Models\VendorInvoice;
use App\Models\VendorPayments;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PaymentVoucherController extends Controller
{
    protected $route = 'payments';
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // Load vendors along with their related data
        $vendors = Vendor::has('invoices')->get();
        $invoices = [];

        if (request()->filled('vendor_id')) {
            $vendorId = request()->input('vendor_id');
            $vendor = Vendor::with('branch')->find($vendorId);

            if (!$vendor) {
                return response()->json(['error' => 'Vendor not found'], 404);
            }

            $invoices = $vendor->invoices()
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

        return view('pages.vendors.payment-voucher', [
            'title' => 'Add Payment Voucher',
            'resource' => 'Payment Vouchers',
            'type' => 'form',
            'action' => route($this->route . '.store'),
            'vendor_id_options' => $vendors,
            'invoices' => $invoices,
            'payment_modes' => $paymentModes,
        ]);
    }

    public function getInvoices($vendorId)
    {
        $vendor = Vendor::with('branch')->find($vendorId);
        if (!$vendor) {
            return response()->json(['error' => 'Vendor not found'], 404);
        }

        $invoices = $vendor->invoices()
            ->with(['items' => function($query) {
                $query->selectRaw('invoice_id, SUM(amount) as total_amount')->groupBy('invoice_id');
            }])
            ->get()
            ->map(function ($invoice) {
                $invoice->total_amount = $invoice->items->first()->total_amount ?? 0;

                $payment = VendorPayments::where('invoice_id', $invoice->id)->first();
                if ($payment) {
                    $invoice->balance_due = $payment->balance_due;
                    // Determine if the invoice is paid
                    $invoice->is_paid = $payment->balance_due == 0.00;
                } else {
                    $invoice->balance_due = $invoice->total_amount;
                    $invoice->is_paid = false; // Not paid
                }
                return $invoice;
            });


        return response()->json([
            'invoices' => $invoices,
            'branch' => $vendor->branch
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
        $invoice = VendorInvoice::findOrFail($invoiceId);
        $paymentAmount = $request->input('amount');
        $paymentModeId = $request->input('payment_mode_id');
        $totalAmountDue = $request->input('total_amount_due');

        // Check if a payment already exists for this invoice
        $existingPayment = VendorPayments::where('invoice_id', $invoiceId)->first();

        if ($existingPayment) {
            // Update the existing payment
            $existingPayment->amount_paid += $paymentAmount; // Accumulate the amount paid
            $existingPayment->balance_due = $totalAmountDue - $existingPayment->amount_paid; // Update balance due
            $existingPayment->payment_mode_id = $paymentModeId;
            $existingPayment->save(); // Save the updated payment record
        } else {
            // Create a new payment record
            VendorPayments::create([
                'vendor_id' => $invoice->vendor_id,
                'invoice_id' => $invoiceId,
                'total_amount' => $totalAmountDue,
                'amount_paid' => $paymentAmount,
                'balance_due' => $totalAmountDue - $paymentAmount,
                'payment_mode_id' => $paymentModeId,
            ]);
        }

        // Generate the receipt URL
        $receiptUrl = route('orders.receipt.pdf', $invoiceId);

        // Return a JSON response with the receipt URL
        return response()->json([
            'success' => true,
            'receipt_url' => $receiptUrl,
            'redirect' => route('vendors.receipts.view') // redirect for backend
        ]);
    }


    public function generateReceiptPdf($invoiceId)
    {
        // Fetch the latest payment for the given invoice
        $payment = VendorPayments::where('invoice_id', $invoiceId)
            ->latest('created_at')
            ->with(['vendor', 'invoice', 'paymentMode'])
            ->firstOrFail();

        // Retrieve the payment mode name (or set 'N/A' if not available)
        $paymentModeName = $payment->paymentMode ? $payment->paymentMode->name : 'N/A';

        // Load the PDF view with the payment and payment mode details
        $pdf = Pdf::loadView('pages.vendors.pdf', [
            'payment' => $payment,
            'payment_mode' => $paymentModeName,
        ]);

        // Stream the PDF file to the browser
        return $pdf->stream('receipt_' . $payment->id . '.pdf');
    }

    public function viewReceipts()
    {
        // Fetch all payment records with related vendor, invoice, and payment mode details
        $payments = VendorPayments::with(['vendor', 'invoice', 'paymentMode'])->get();

        $payment_modes = BankCash::all(); // Fetch available payment modes

        return view('pages.vendors.view-receipt', [
            'title' => 'View Receipts',
            'resource' => 'Receipts',
            'type' => 'table',
            'payments' => $payments,
            'payment_modes' => $payment_modes,  // Passing payment modes to the view
        ]);
    }

    public function pay(Request $request, $paymentId)
    {
        // Validate the request
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_mode_id' => 'required|exists:bank_cashes,id',
        ]);

        // Find the payment record by ID or throw an exception
        $payment = VendorPayments::findOrFail($paymentId);
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

        $receiptUrl = route('orders.receipt.pdf', $payment->invoice_id);

        return response()->json(['success' => true, 'message' => 'Payment successful.', 'receipt_url' => $receiptUrl]);
    }
}

