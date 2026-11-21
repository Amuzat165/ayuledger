<?php

namespace App\Http\Controllers;

use App\BankCash;
use App\Models\Vendor;
use App\Models\VendorPayments;
use App\Services\VendorPaymentSettlementService;
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

            $settlement = app(VendorPaymentSettlementService::class);
            $invoices = $vendor->invoices()->get()->map(function ($invoice) use ($settlement) {
                return $invoice->setAttribute('settlement_summary', $settlement->describeInvoice($invoice));
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
            'currency_symbol' => config('settings.currency_symbol'),
        ]);
    }

    public function getInvoices($vendorId)
    {
        $vendor = Vendor::with('branch')->find($vendorId);
        if (!$vendor) {
            return response()->json(['error' => 'Vendor not found'], 404);
        }

        $settlement = app(VendorPaymentSettlementService::class);
        $invoices = $vendor->invoices()->get()->map(function ($invoice) use ($settlement) {
            $summary = $settlement->describeInvoice($invoice);
            $invoice->setAttribute('settlement_summary', $summary);
            $invoice->setAttribute('total_amount', $summary['invoice_total']);
            $invoice->setAttribute('balance_due', $summary['balance_due']);
            $invoice->setAttribute('is_paid', $summary['settlement_available'] && $summary['balance_due'] === '0.00');
            return $invoice;
        });


        return response()->json([
            'invoices' => $invoices,
            'branch' => $vendor->branch
        ]);
    }


    public function submitPayment(Request $request, $invoiceId)
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'regex:/^\\d+(?:\\.\\d{1,2})?$/'],
            'payment_mode_id' => 'required|integer|exists:bank_cashes,id',
        ]);

        $result = app(VendorPaymentSettlementService::class)->settleInvoice(
            $invoiceId,
            $request->input('amount'),
            $request->input('payment_mode_id')
        );

        $receiptUrl = route('orders.receipt.pdf', $invoiceId);

        return response()->json([
            'success' => true,
            'receipt_url' => $receiptUrl,
            'redirect' => route('vendors.receipts.view'),
            'invoice_total' => $result['invoice_total'],
            'wht_amount' => $result['wht_amount'],
            'net_payable' => $result['net_payable'],
            'amount_paid' => $result['amount_paid'],
            'balance_due' => $result['balance_due'],
        ]);
    }


    public function generateReceiptPdf($invoiceId)
    {
        // Fetch the latest payment for the given invoice
        $payment = VendorPayments::where('invoice_id', $invoiceId)
            ->latest('created_at')
            ->with(['vendor', 'invoice.items', 'paymentMode'])
            ->firstOrFail();

        // Retrieve the payment mode name (or set 'N/A' if not available)
        $paymentModeName = $payment->paymentMode ? $payment->paymentMode->name : 'N/A';

        // Load the PDF view with the payment and payment mode details
        $pdf = Pdf::loadView('pages.vendors.pdf', [
            'payment' => $payment,
            'payment_mode' => $paymentModeName,
            'settlement' => app(VendorPaymentSettlementService::class)->describeInvoice($payment->invoice),
            'currency_symbol' => config('settings.currency_symbol'),
        ]);

        // Stream the PDF file to the browser
        return $pdf->stream('receipt_' . $payment->id . '.pdf');
    }

    public function viewReceipts()
    {
        // Fetch all payment records with related vendor, invoice, and payment mode details
        $payments = VendorPayments::with(['vendor', 'invoice.items', 'paymentMode'])->get();
        $settlement = app(VendorPaymentSettlementService::class);
        $payments->each(function ($payment) use ($settlement) {
            $payment->setAttribute('settlement_summary', $settlement->describeInvoice($payment->invoice));
        });

        $payment_modes = BankCash::all(); // Fetch available payment modes

        return view('pages.vendors.view-receipt', [
            'title' => 'View Receipts',
            'resource' => 'Receipts',
            'type' => 'table',
            'payments' => $payments,
            'payment_modes' => $payment_modes,  // Passing payment modes to the view
            'currency_symbol' => config('settings.currency_symbol'),
        ]);
    }

    public function pay(Request $request, $paymentId)
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'regex:/^\\d+(?:\\.\\d{1,2})?$/'],
            'payment_mode_id' => 'required|integer|exists:bank_cashes,id',
        ]);

        $result = app(VendorPaymentSettlementService::class)->settlePayment(
            $paymentId,
            $request->input('amount'),
            $request->input('payment_mode_id')
        );

        $receiptUrl = route('orders.receipt.pdf', $result['payment']->invoice_id);

        return response()->json([
            'success' => true,
            'message' => 'Payment successful.',
            'receipt_url' => $receiptUrl,
            'invoice_total' => $result['invoice_total'],
            'wht_amount' => $result['wht_amount'],
            'net_payable' => $result['net_payable'],
            'amount_paid' => $result['amount_paid'],
            'balance_due' => $result['balance_due'],
        ]);
    }
}
