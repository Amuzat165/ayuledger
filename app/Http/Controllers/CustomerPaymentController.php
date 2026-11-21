<?php

namespace App\Http\Controllers;

use App\BankCash;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\CustomerPayments;
use App\Models\CustomerPaymentReceipt;
use App\Support\AmountInWordsFormatter;
use Illuminate\Support\Facades\DB;
use App\Services\CustomerPaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CustomerPaymentController extends Controller
{
    public function getInvoices($customerId)
    {
        $customer = Customer::with('branch')->findOrFail($customerId);
        $invoices = $customer->invoices()->get()->map(function ($invoice) {
            $invoice->total_amount = $invoice->total_due;
            try {
                $invoice->balance_due = app(CustomerPaymentService::class)->balanceFor($invoice);
                $invoice->payment_eligible = true;
                $invoice->is_paid = (float) $invoice->balance_due === 0.0;
            } catch (ValidationException $e) {
                $invoice->balance_due = null;
                $invoice->payment_eligible = false;
                $invoice->is_paid = false;
                $invoice->payment_note = 'Historical invoice requires review';
            }
            return $invoice;
        });
        return response()->json(['invoices' => $invoices, 'branch' => $customer->branch]);
    }

    public function submitPayment(Request $request, $invoiceId)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric'],
            'payment_mode_id' => ['required', 'integer'],
            'idempotency_key' => ['required', 'uuid'],
        ]);
        $invoice = CustomerInvoice::findOrFail($invoiceId);
        $receipt = app(CustomerPaymentService::class)->pay($invoice->id, $invoice->customer_id, $data['amount'], $data['payment_mode_id'], $data['idempotency_key'], $request->user()->id);
        return response()->json([
            'success' => true, 'receipt_number' => $receipt->receipt_number,
            'receipt_url' => route('customer.payment_receipts.pdf', $receipt->id),
            'redirect' => route('customers.receipts.view'),
        ]);
    }

    public function pay(Request $request, $paymentId)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric'],
            'payment_mode_id' => ['required', 'integer'],
            'idempotency_key' => ['required', 'uuid'],
        ]);
        // Legacy row is used only to locate invoice/customer; its financial values are ignored.
        $legacy = CustomerPayments::findOrFail($paymentId);
        $receipt = app(CustomerPaymentService::class)->pay($legacy->invoice_id, $legacy->customer_id, $data['amount'], $data['payment_mode_id'], $data['idempotency_key'], $request->user()->id);
        return response()->json([
            'success' => true, 'receipt_number' => $receipt->receipt_number,
            'receipt_url' => route('customer.payment_receipts.pdf', $receipt->id),
        ]);
    }

    public function viewReceipts()
    {
        $currency = config('settings.currency_symbol', config('settings.currency_code', ''));
        return view('pages.customers.receipts.event-index', [
            'title' => 'View Receipts',
            'receipts' => CustomerPaymentReceipt::with(['customer', 'paymentMode', 'allocations.invoice'])->latest('received_at')->get(),
            'legacyPayments' => CustomerPayments::with(['customer', 'invoice', 'paymentMode'])->get(),
            'payment_modes' => BankCash::all(), 'currency' => $currency,
        ]);
    }

    public function receiptPdf($receiptId)
    {
        $receipt = CustomerPaymentReceipt::with(['customer', 'paymentMode', 'allocations.invoice', 'deposit'])->findOrFail($receiptId);
        $allocation = $receipt->allocations->first();
        abort_unless($allocation, 404);
        $settings = config('settings', []);
        $invoice = $allocation->invoice;
        $cumulativeAllocated = DB::table('customer_payment_allocations as a')
            ->join('customer_payment_receipts as r', 'r.id', '=', 'a.receipt_id')
            ->where('a.invoice_id', $allocation->invoice_id)
            ->where('a.status', 'allocated')
            ->where('r.status', 'received')
            ->where(function ($query) use ($receipt) {
                $query->where('r.received_at', '<', $receipt->received_at)
                    ->orWhere(function ($sameTimestamp) use ($receipt) {
                        $sameTimestamp->where('r.received_at', '=', $receipt->received_at)
                            ->where('r.id', '<=', $receipt->id);
                    });
            })
            ->sum('a.allocated_amount');
        $currencyCode = $settings['currency_code'] ?? '';
        $formatter = app(AmountInWordsFormatter::class);
        return Pdf::loadView('pages.customers.receipts.event-pdf', [
            'receipt' => $receipt,
            'allocation' => $allocation,
            'currency' => $settings['currency_symbol'] ?? $currencyCode,
            'currency_code' => $currencyCode,
            'amount_in_words' => $formatter->format($receipt->amount_received, $currencyCode),
            'total_paid_to_date' => number_format((float) $cumulativeAllocated, 2, '.', ''),
            'company_name' => $settings['company_name'] ?? 'AYUGLOBAL',
            'contact_person' => $settings['contract_person'] ?? '',
            'company_logo' => $settings['company_logo'] ?? null,
            'header_image' => $settings['header_image'] ?? null,
            'company_color' => $settings['company_color'] ?? '#176b63',
            'address_1' => $settings['address_1'] ?? '',
            'address_2' => $settings['address_2'] ?? '',
            'city' => $settings['city'] ?? '',
            'state' => $settings['state'] ?? '',
            'zip_code' => $settings['zip_code'] ?? '',
            'phone' => $settings['phone'] ?? '',
            'email' => $settings['email'] ?? '',
        ])->setPaper('A4')->stream($receipt->receipt_number . '.pdf');
    }
}
