<?php

namespace App\Http\Controllers;

use App\BankCash;
use App\Models\SupplierInvoice;
use App\Models\SupplierInvoicePayment;
use App\Services\SupplierInvoicePaymentService;
use App\Support\AmountInWordsFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SupplierInvoicePaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $this->authorizePayment('Show');

        $filters = $request->validate([
            'supplier' => ['nullable', 'string', 'max:191'],
            'supplier_invoice_number' => ['nullable', 'string', 'max:120'],
            'system_invoice_number' => ['nullable', 'string', 'max:32'],
            'payment_reference' => ['nullable', 'string', 'max:32'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'payment_method' => ['nullable', 'string', 'max:191'],
            'branch' => ['nullable', 'string', 'max:191'],
        ]);

        $query = SupplierInvoicePayment::with([
            'supplierInvoice.vendor',
            'supplierInvoice.purchaseOrder',
            'supplierInvoice.branch',
            'paymentMode',
            'creator',
        ]);

        if (!empty($filters['supplier'])) {
            $term = trim($filters['supplier']);
            $query->whereHas('supplierInvoice', function ($invoice) use ($term) {
                $invoice->where('vendor_name_snapshot', 'like', '%' . $term . '%');
            });
        }
        if (!empty($filters['supplier_invoice_number'])) {
            $term = trim($filters['supplier_invoice_number']);
            $query->whereHas('supplierInvoice', function ($invoice) use ($term) {
                $invoice->where('supplier_invoice_number', 'like', '%' . $term . '%');
            });
        }
        if (!empty($filters['system_invoice_number'])) {
            $term = trim($filters['system_invoice_number']);
            $query->whereHas('supplierInvoice', function ($invoice) use ($term) {
                $invoice->where('internal_invoice_number', 'like', '%' . $term . '%');
            });
        }
        if (!empty($filters['payment_reference'])) {
            $query->where('payment_reference', 'like', '%' . trim($filters['payment_reference']) . '%');
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('payment_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('payment_date', '<=', $filters['date_to']);
        }
        if (!empty($filters['payment_method'])) {
            $query->where('payment_mode_name', 'like', '%' . trim($filters['payment_method']) . '%');
        }
        if (!empty($filters['branch'])) {
            $term = trim($filters['branch']);
            $query->whereHas('supplierInvoice', function ($invoice) use ($term) {
                $invoice->where('branch_name_snapshot', 'like', '%' . $term . '%');
            });
        }

        $payments = $query->orderByDesc('payment_date')->orderByDesc('id')->paginate(25)->withQueryString();

        return view('admin.supplier-invoice-payments.index', compact('payments', 'filters'));
    }
    public function create($invoiceId)
    {
        $this->authorizePayment('Create');
        $invoice = SupplierInvoice::with(['vendor', 'purchaseOrder'])->findOrFail($invoiceId);
        abort_unless($invoice->isPayable() && (float) $invoice->amount_remaining > 0, 422);
        return view('admin.supplier-invoices.payment-form', [
            'invoice' => $invoice,
            'paymentModes' => BankCash::orderBy('name')->get(),
            'submissionToken' => (string) Str::uuid(),
        ]);
    }

    public function store(Request $request, $invoiceId, SupplierInvoicePaymentService $payments)
    {
        $this->authorizePayment('Create');
        $data = $request->validate([
            'payment_amount' => ['required', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'payment_date' => ['required', 'date_format:Y-m-d'],
            'payment_mode_id' => ['required', 'integer'],
            'submission_token' => ['required', 'uuid'],
        ]);
        $payment = $payments->pay((int) $invoiceId, $data['payment_amount'], $data['payment_date'], (int) $data['payment_mode_id'], $data['submission_token'], $request->user()->id);
        if (config('role_manage.SupplierPayment.Pdf')) {
            return redirect()->route('supplier-invoice-payments.receipt', $payment);
        }
        return redirect()->route('supplier-invoices.show', $payment->supplier_invoice_id)->with('success', __('supplier_payments.messages.saved'));
    }

    public function receipt($paymentId)
    {
        $this->authorizePayment('Pdf');
        $payment = SupplierInvoicePayment::with([
            'supplierInvoice.vendor', 'supplierInvoice.purchaseOrder', 'paymentMode', 'creator',
        ])->findOrFail($paymentId);
        $invoice = $payment->supplierInvoice;
        $settings = config('settings', []);
        return Pdf::loadView('admin.supplier-invoices.payment-receipt-pdf', [
            'payment' => $payment,
            'invoice' => $invoice,
            'vendor' => $invoice->vendor,
            'purchaseOrder' => $invoice->purchaseOrder,
            'amountInWords' => app(AmountInWordsFormatter::class)->format($payment->payment_amount, $payment->currency_code),
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
        ])->setPaper('A4')->stream($payment->payment_reference . '.pdf');
    }

    private function authorizePayment(string $action): void
    {
        abort_unless((bool) config('role_manage.SupplierPayment.' . $action), 403);
    }
}
