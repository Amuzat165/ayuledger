<!doctype html>
<html lang="{{ app()->getLocale() === 'main' ? 'en' : (app()->getLocale() === 'zh' ? 'zh-CN' : app()->getLocale()) }}" dir="{{ \App\Support\LocaleManager::direction(app()->getLocale()) }}">
<head><meta charset="utf-8"><title>{{ __('financial.terms.payment_receipt') }} {{ $receipt->receipt_number }}</title>
<style>
    @page{margin:28px 34px}*{box-sizing:border-box}body{font-family:DejaVu Sans,sans-serif;color:#243247;font-size:11px;line-height:1.45;direction:inherit;text-align:start;margin:0}.document-header{border-bottom:2px solid {{ $company_color ?? '#176b63' }};padding-bottom:14px;margin-bottom:20px}.document-banner{display:block;width:100%;max-height:90px;object-fit:contain;margin-bottom:12px}.company-row{display:flex;align-items:center;gap:14px}.company-logo{max-width:105px;max-height:60px;object-fit:contain}.company-name{font-size:18px;font-weight:bold;color:{{ $company_color ?? '#176b63' }}}.company-copy{color:#526174}.document-title{margin:0 0 14px;font-size:22px;letter-spacing:.5px;color:#173c50}.metadata,.summary{width:100%;border-collapse:collapse}.metadata td{width:50%;padding:8px 10px;background:#f4f7f8;border:1px solid #e0e7ea}.label{display:block;font-size:9px;text-transform:uppercase;color:#66788a;margin-bottom:3px}.value{font-size:12px;font-weight:bold}.section-title{font-size:12px;color:#173c50;margin:18px 0 8px;border-bottom:1px solid #d8e1e5;padding-bottom:5px}.summary{border-collapse:collapse}.summary td{padding:7px 9px;border-bottom:1px solid #e3e8eb}.summary td:last-child{text-align:right;white-space:nowrap}.summary .emphasis td{font-weight:bold;font-size:13px;background:#f4f7f8}.words{margin-top:8px;padding:9px 10px;background:#f7f8f3;border-left:3px solid {{ $company_color ?? '#176b63' }}}.paid-badge{display:inline-block;color:#176b63;background:#e8f5ef;padding:5px 9px;border-radius:3px;font-weight:bold;margin-top:8px}.balance-due{font-size:15px;font-weight:bold;color:#173c50}.document-footer{border-top:1px solid #d8e1e5;margin-top:24px;padding-top:9px;text-align:center;font-size:9px;color:#66788a}
</style></head>
<body>
@include('pages.customers.documents.partials.company-header')
<h1 class="document-title">{{ __('financial.terms.payment_receipt') }}</h1>
<table class="metadata"><tr>
    <td><span class="label">{{ __('financial.labels.receipt_number') }}</span><span class="value">{{ $receipt->receipt_number }}</span></td>
    <td><span class="label">{{ __('financial.labels.payment_date') }}</span><span class="value">{{ $receipt->received_at->format('Y-m-d H:i') }}</span></td>
</tr><tr>
    <td><span class="label">{{ __('financial.terms.customer') }}</span><span class="value">{{ optional($receipt->customer)->name }}</span></td>
    <td><span class="label">{{ __('financial.terms.invoice') }}</span><span class="value">{{ optional($allocation->invoice)->invoice_number }}</span></td>
</tr><tr>
    <td colspan="2"><span class="label">{{ __('financial.labels.payment_method') }}</span><span class="value">{{ $receipt->payment_mode_name ?: __('financial.labels.not_available') }}</span></td>
</tr></table>

@if(optional($allocation->invoice)->financial_snapshot_version)
    @php($invoice = $allocation->invoice)
    <h2 class="section-title">{{ __('financial.labels.invoice_total') }}</h2>
    <table class="summary">
        <tr><td>{{ __('financial.terms.subtotal') }}</td><td>{{ $currency }}{{ number_format($invoice->subtotal, 2) }}</td></tr>
        @if((float) $invoice->vat_percentage > 0)<tr><td>{{ __('financial.terms.vat') }} ({{ number_format((float) $invoice->vat_percentage, 2) }}%)</td><td>{{ $currency }}{{ number_format($invoice->vat_amount, 2) }}</td></tr>@endif
        <tr class="emphasis"><td>{{ __('financial.labels.invoice_total') }}</td><td>{{ $currency }}{{ number_format($invoice->total_due, 2) }}</td></tr>
        <tr><td>{{ __('financial.terms.discount') }}@if($invoice->discount_percentage !== null && (float) $invoice->discount_percentage > 0) ({{ number_format((float) $invoice->discount_percentage, 2) }}%)@endif</td><td>{{ $currency }}{{ number_format($invoice->discount_amount ?? 0, 2) }}</td></tr>
        <tr class="emphasis"><td>{{ __('financial.labels.amount_due') }}</td><td>{{ $currency }}{{ number_format($invoice->balance_due ?? $invoice->total_due, 2) }}</td></tr>
    </table>
@endif

<h2 class="section-title">{{ __('financial.labels.payment_received') }}</h2>
<table class="summary">
    <tr class="emphasis"><td>{{ __('financial.terms.amount_paid') }}</td><td>{{ $currency }}{{ number_format($receipt->amount_received, 2) }}</td></tr>
    <tr><td>{{ __('financial.labels.applied_to_invoice') }}</td><td>{{ $currency }}{{ number_format($allocation->allocated_amount, 2) }}</td></tr>
    @if((float) $receipt->deposit_created_amount > 0)<tr><td>{{ __('financial.labels.customer_credit') }}</td><td>{{ $currency }}{{ number_format($receipt->deposit_created_amount, 2) }}</td></tr>@endif
    <tr><td>{{ __('financial.labels.total_paid_to_date') }}</td><td>{{ $currency }}{{ number_format($total_paid_to_date, 2) }}</td></tr>
    <tr class="emphasis"><td>{{ __('financial.labels.balance_remaining') }}</td><td class="balance-due">{{ $currency }}{{ number_format($allocation->balance_after, 2) }}</td></tr>
</table>
<div class="words"><strong>{{ __('financial.labels.amount_in_words') }}:</strong> {{ $amount_in_words }}</div>
@if((float) $allocation->balance_after <= 0)<div class="paid-badge">{{ __('financial.labels.paid_in_full') }}</div>@endif
@include('pages.customers.documents.partials.company-footer', ['document_footer_message' => __('financial.labels.thank_you_for_payment')])
</body></html>
