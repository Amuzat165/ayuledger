<!doctype html>
<html lang="{{ app()->getLocale() === 'main' ? 'en' : (app()->getLocale() === 'zh' ? 'zh-CN' : app()->getLocale()) }}" dir="{{ \App\Support\LocaleManager::direction(app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ __('financial.terms.invoice') }} {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 28px 34px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #243247; font-size: 11px; line-height: 1.45; direction: inherit; text-align: start; margin: 0; }
        .document-header { border-bottom: 2px solid {{ $company_color ?? '#176b63' }}; padding-bottom: 14px; margin-bottom: 20px; }
        .document-banner { display: block; width: 100%; max-height: 90px; object-fit: contain; margin-bottom: 12px; }
        .company-row { display: flex; align-items: center; gap: 14px; }
        .company-logo { max-width: 105px; max-height: 60px; object-fit: contain; }
        .company-name { font-size: 18px; font-weight: bold; color: {{ $company_color ?? '#176b63' }}; }
        .company-copy { color: #526174; }
        .document-title { margin: 0; font-size: 22px; color: #173c50; }
        .metadata, .items, .summary { width: 100%; border-collapse: collapse; }
        .metadata { margin-bottom: 17px; }
        .metadata td { width: 50%; padding: 8px 10px; background: #f4f7f8; border: 1px solid #e0e7ea; }
        .label { display: block; font-size: 9px; text-transform: uppercase; color: #66788a; margin-bottom: 3px; }
        .value { font-size: 12px; font-weight: bold; }
        .section-title { font-size: 12px; color: #173c50; margin: 18px 0 8px; border-bottom: 1px solid #d8e1e5; padding-bottom: 5px; }
        .items th { background: #edf2f4; color: #30475b; text-align: start; }
        .items th, .items td { padding: 8px; border-bottom: 1px solid #e0e7ea; }
        .numeric { text-align: right; white-space: nowrap; }
        .summary { max-width: 440px; margin: 16px 0 0 auto; }
        .summary td { padding: 7px 9px; border-bottom: 1px solid #e3e8eb; }
        .summary td:last-child { text-align: right; white-space: nowrap; }
        .summary .emphasis td { font-weight: bold; font-size: 13px; background: #f4f7f8; }
        .words { margin: 8px 0 0 auto; max-width: 440px; padding: 9px 10px; background: #f7f8f3; border-left: 3px solid {{ $company_color ?? '#176b63' }}; }
        .document-footer { border-top: 1px solid #d8e1e5; margin-top: 24px; padding-top: 9px; text-align: center; font-size: 9px; color: #66788a; }
        .toolbar { margin-bottom: 14px; text-align: end; }
        .toolbar a, .toolbar button { display: inline-block; padding: 7px 11px; border: 1px solid #8798a5; border-radius: 3px; background: white; color: #243247; text-decoration: none; font: inherit; }
        .invoice-created-shell { min-height: 100vh; padding: 28px 20px 44px; background: #f2f5f7; }
        .invoice-created-page { width: 100%; max-width: 1040px; margin: 0 auto; padding: 30px 38px 26px; background: #fff; border: 1px solid #e2e8ec; border-radius: 8px; box-shadow: 0 10px 30px rgba(26,48,66,.08); }
        .invoice-created-page .toolbar { display: flex; justify-content: flex-end; gap: 9px; margin: 0 0 22px; }
        .invoice-created-page .toolbar a, .invoice-created-page .toolbar button { padding: 9px 14px; border: 1px solid #cbd5df; border-radius: 5px; background: #fff; color: #34495e; font-size: 12px; cursor: pointer; }
        .invoice-created-page .toolbar button { border-color: #155eef; background: #155eef; color: #fff; }
        .invoice-created-page .document-header { margin-bottom: 24px; }
        .invoice-created-page .document-banner-container { margin-bottom: 12px !important; }
        .invoice-created-page .document-banner { width: auto; max-width: 100%; max-height: 108px; margin: 0 auto; object-fit: contain; object-position: center; }
        .invoice-created-page .company-row { font-size: 12px !important; line-height: 1.5 !important; margin: 0 auto 16px !important; }
        .invoice-created-page .company-copy { color: #64748b; }
        .invoice-created-page .company-name { margin-bottom: 4px; font-size: 15px !important; font-weight: 700 !important; }
        .invoice-created-page .document-title { margin: 0 0 17px; font-size: 25px; font-weight: 700; }
        .invoice-created-page .metadata { margin-bottom: 22px; }
        .invoice-created-page .metadata td { padding: 12px 14px; background: #f8fafb; border-color: #e4eaee; }
        .invoice-created-page .label { font-size: 10px; letter-spacing: .03em; }
        .invoice-created-page .value { font-size: 13px; line-height: 1.5; }
        .invoice-created-page .section-title { margin-top: 21px; font-size: 14px; font-weight: 700; }
        .invoice-created-page .items th, .invoice-created-page .items td { padding: 10px; }
        .invoice-created-page .summary { max-width: 460px; margin-top: 19px; }
        .invoice-created-page .summary td { padding: 9px 11px; }
        .invoice-created-page .summary .emphasis td { font-size: 14px; }
        .invoice-created-page .words { margin-top: 12px; padding: 11px 13px; border-radius: 0 5px 5px 0; }
        .invoice-created-page .document-footer { margin-top: 25px; padding-top: 11px; font-size: 10px; }
        @media (max-width: 700px) {
            .invoice-created-shell { padding: 10px 8px 24px; }
            .invoice-created-page { padding: 18px 14px 20px; border-radius: 6px; }
            .invoice-created-page .toolbar { justify-content: flex-start; flex-wrap: wrap; }
            .invoice-created-page .document-title { font-size: 22px; }
            .invoice-created-page .items { display: block; overflow-x: auto; white-space: nowrap; }
            .invoice-created-page .summary { max-width: 100%; }
        }
        @media print {
            .toolbar { display: none; }
            .invoice-created-shell { min-height: 0; padding: 0; background: #fff; }
            .invoice-created-page { max-width: none; margin: 0; padding: 0; border: 0; border-radius: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
@php($createdInvoicePreview = $invoice_creation_document ?? false)
@if($createdInvoicePreview)
<div class="invoice-created-shell"><main class="invoice-created-page">
<div class="toolbar">
    <a href="{{ url('/customers/invoices') }}">{{ __('financial.labels.back_to_invoices') }}</a>
    <button onclick="window.print()">{{ __('financial.labels.print_invoice') }}</button>
</div>
@include('pages.customers.documents.partials.company-header', ['invoice_creation_document' => $invoice_creation_document ?? false])
@else
@include('pages.customers.documents.partials.company-header')
<div class="toolbar">
    <a href="{{ url('/customers/invoices') }}">{{ __('financial.labels.back_to_invoices') }}</a>
    <button onclick="window.print()">{{ __('financial.labels.print_invoice') }}</button>
</div>
@endif
<h1 class="document-title">{{ __('financial.terms.invoice') }}</h1>
<table class="metadata">
    <tr>
        <td><span class="label">{{ __('financial.labels.invoice_number') }}</span><span class="value">{{ $invoice->invoice_number }}</span></td>
        <td><span class="label">{{ __('financial.labels.invoice_date') }}</span><span class="value">{{ $invoice->invoice_date }}</span></td>
    </tr>
    <tr>
        <td><span class="label">{{ __('financial.labels.customer_name') }}</span><span class="value">{{ $customer->name }}</span></td>
        <td><span class="label">{{ __('financial.labels.branch') }}</span><span class="value">{{ $customer->branch ? $customer->branch->name : __('financial.labels.not_available') }}</span></td>
    </tr>
    <tr><td colspan="2"><span class="label">{{ __('financial.screens.address') }}</span><span class="value">{{ $customer->address }}</span></td></tr>
</table>

<h2 class="section-title">{{ __('financial.labels.invoice_items') }}</h2>
<table class="items">
    <thead>
        <tr>
            <th>{{ __('financial.labels.item') }}</th>
            <th>{{ __('financial.terms.description') }}</th>
            <th class="numeric">{{ __('financial.terms.quantity') }}</th>
            <th class="numeric">{{ __('financial.labels.rate') }}</th>
            <th class="numeric">{{ __('financial.terms.total_amount') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoiceItems as $item)
            <tr>
                <td>{{ optional(optional($item->inventory)->type)->name ?: __('financial.labels.not_available') }}</td>
                <td>{{ $item->description }}</td>
                <td class="numeric">{{ $item->quantity }}</td>
                <td class="numeric">{{ $currency }}{{ number_format($item->rate, 2) }}</td>
                <td class="numeric">{{ $currency }}{{ number_format($item->amount, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

@php($invoiceSubtotal = $invoice->subtotal ?? $totalAmount)
@php($invoiceTotal = $invoice->total_due ?? $totalAmount)
<table class="summary">
    <tr><td>{{ __('financial.terms.subtotal') }}</td><td>{{ $currency }}{{ number_format($invoiceSubtotal, 2) }}</td></tr>
    @if($invoice->vat_percentage !== null && $invoice->vat_percentage > 0)
        <tr><td>{{ __('financial.terms.vat') }} ({{ number_format((float) $invoice->vat_percentage, 2) }}%)</td><td>{{ $currency }}{{ number_format($invoice->vat_amount ?? 0, 2) }}</td></tr>
    @endif
    <tr class="emphasis"><td>{{ __('financial.labels.invoice_total') }}</td><td>{{ $currency }}{{ number_format($invoiceTotal, 2) }}</td></tr>
    @if($invoice->discount_percentage !== null && $invoice->discount_amount !== null && (float) $invoice->discount_amount > 0)
        <tr><td>{{ __('financial.terms.discount') }} ({{ number_format((float) $invoice->discount_percentage, 2) }}%)</td><td>{{ $currency }}{{ number_format($invoice->discount_amount, 2) }}</td></tr>
    @endif
    <tr class="emphasis"><td>{{ __('financial.labels.amount_due') }}</td><td>{{ $currency }}{{ number_format($invoice->balance_due ?? $invoiceTotal, 2) }}</td></tr>
</table>
<div class="words"><strong>{{ __('financial.labels.amount_in_words') }}:</strong> {{ $amount_in_words }}</div>
@include('pages.customers.documents.partials.company-footer', ['document_footer_message' => __('financial.labels.invoice_for_records')])
@if($createdInvoicePreview)
</main></div>
@endif
</body>
</html>
