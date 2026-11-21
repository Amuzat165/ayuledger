<!doctype html>
<html lang="{{ app()->getLocale() === 'main' ? 'en' : (app()->getLocale() === 'zh' ? 'zh-CN' : app()->getLocale()) }}" dir="{{ \App\Support\LocaleManager::direction(app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('supplier_invoices.title') }} {{ $invoice->internal_invoice_number }}</title>
    <style>
        @page { size: A4; margin: 28px 34px; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #fff; color: #243247; font-size: 10px; line-height: 1.45; font-family: DejaVu Sans, sans-serif; direction: inherit; text-align: start; }
        .document-header { border-bottom: 2px solid {{ $company_color ?? '#176b63' }}; margin-bottom: 18px; padding-bottom: 12px; }
        .document-title { color: #173c50; font-size: 20px; margin: 0 0 12px; }
        .section-title { border-bottom: 1px solid #d8e1e5; color: #173c50; font-size: 11px; margin: 16px 0 7px; padding-bottom: 4px; }
        table { border-collapse: collapse; width: 100%; }
        .metadata td { background: #f4f7f8; border: 1px solid #e0e7ea; padding: 7px 9px; vertical-align: top; width: 50%; }
        .label { color: #66788a; display: block; font-size: 8px; margin-bottom: 2px; text-transform: uppercase; }
        .value { font-size: 10px; font-weight: bold; overflow-wrap: anywhere; }
        .items th { background: #edf2f4; color: #30475b; font-weight: bold; text-align: start; }
        .items th, .items td { border-bottom: 1px solid #e0e7ea; padding: 7px 6px; vertical-align: top; }
        .numeric { text-align: right !important; white-space: nowrap; }
        .summary { margin: 14px 0 0 auto; max-width: 360px; }
        .summary td { border-bottom: 1px solid #e3e8eb; padding: 6px 8px; }
        .summary td:last-child { text-align: right; white-space: nowrap; }
        .summary .emphasis td { background: #f4f7f8; font-size: 11px; font-weight: bold; }
        .words { background: #f7f8f3; border-left: 3px solid {{ $company_color ?? '#176b63' }}; margin: 8px 0 0 auto; max-width: 360px; padding: 8px 9px; }
        .review { background: #fff8e5; border: 1px solid #ead9a5; margin-top: 12px; padding: 8px; }
        .document-footer { border-top: 1px solid #d8e1e5; color: #66788a; font-size: 8px; margin-top: 20px; padding-top: 8px; text-align: center; }
    </style>
</head>
<body>
@include('pages.customers.documents.partials.company-header')
<h1 class="document-title">{{ __('supplier_invoices.title') }}</h1>

<table class="metadata">
    <tr>
        <td><span class="label">{{ __('supplier_invoices.supplier') }}</span><span class="value">{{ $invoice->vendor_name_snapshot }}</span>
            @if($vendor && ($vendor->address || $vendor->phone_number))
                <div>{{ $vendor->address }}</div><div>{{ $vendor->phone_number }}</div>
            @endif
        </td>
        <td><span class="label">{{ __('supplier_invoices.supplier_invoice_number') }}</span><span class="value">{{ $invoice->supplier_invoice_number }}</span></td>
    </tr>
    <tr>
        <td><span class="label">{{ __('supplier_invoices.internal_number') }}</span><span class="value">{{ $invoice->internal_invoice_number }}</span></td>
        <td><span class="label">{{ __('supplier_invoices.purchase_order') }}</span><span class="value">{{ optional($purchaseOrder)->purchase_order_number }}</span></td>
    </tr>
    <tr>
        <td><span class="label">{{ __('supplier_invoices.branch') }}</span><span class="value">{{ $invoice->branch_name_snapshot }}</span></td>
        <td><span class="label">{{ __('supplier_invoices.currency') }}</span><span class="value">{{ $invoice->currency_code }} ({{ $invoice->currency_symbol }})</span></td>
    </tr>
    <tr>
        <td><span class="label">{{ __('supplier_invoices.invoice_date') }}</span><span class="value">{{ optional($invoice->invoice_date)->format(config('settings.date_format', 'd/m/Y')) }}</span></td>
        <td><span class="label">{{ __('supplier_invoices.due_date') }}</span><span class="value">{{ $invoice->due_date ? $invoice->due_date->format(config('settings.date_format', 'd/m/Y')) : '-' }}</span></td>
    </tr>
    <tr>
        <td colspan="2"><span class="label">{{ __('supplier_invoices.status') }}</span><span class="value">{{ __('supplier_invoices.' . $invoice->status) }}</span></td>
    </tr>
</table>

<h2 class="section-title">{{ __('supplier_invoices.items') }}</h2>
<table class="items">
    <thead><tr>
        <th>{{ __('financial.terms.description') }}</th>
        <th>{{ __('supplier_invoices.unit') }}</th>
        <th class="numeric">{{ __('supplier_invoices.quantity') }}</th>
        <th class="numeric">{{ __('supplier_invoices.supplier_unit_price') }}</th>
        <th class="numeric">{{ __('supplier_invoices.line_total') }}</th>
    </tr></thead>
    <tbody>
    @foreach($invoice->items as $item)
        <tr>
            <td>{{ $item->description_snapshot }}</td>
            <td>{{ $item->unit_snapshot }}</td>
            <td class="numeric">{{ \App\Support\PurchaseOrderCalculator::formatQuantity($item->quantity) }}</td>
            <td class="numeric">{{ \App\Support\PurchaseOrderCalculator::formatCurrency($item->supplier_unit_price, $invoice->currency_symbol, $invoice->currency_position) }}</td>
            <td class="numeric">{{ \App\Support\PurchaseOrderCalculator::formatCurrency($item->line_total, $invoice->currency_symbol, $invoice->currency_position) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table class="summary">
    <tr><td>{{ __('supplier_invoices.subtotal') }}</td><td>{{ $invoice->subtotal === null ? '-' : \App\Support\PurchaseOrderCalculator::formatCurrency($invoice->subtotal, $invoice->currency_symbol, $invoice->currency_position) }}</td></tr>
    @if($invoice->wht_enabled_snapshot || ($invoice->wht_amount !== null && (float) $invoice->wht_amount > 0))
        <tr><td>{{ __('supplier_invoices.wht') }}</td><td>{{ $invoice->wht_amount === null ? '-' : \App\Support\PurchaseOrderCalculator::formatCurrency($invoice->wht_amount, $invoice->currency_symbol, $invoice->currency_position) }}</td></tr>
    @endif
    <tr class="emphasis"><td>{{ __('supplier_invoices.total') }}</td><td>{{ $invoice->total_amount === null ? '-' : \App\Support\PurchaseOrderCalculator::formatCurrency($invoice->total_amount, $invoice->currency_symbol, $invoice->currency_position) }}</td></tr>
    <tr><td>{{ __('supplier_invoices.amount_paid') }}</td><td>{{ $invoice->amount_paid === null ? '-' : \App\Support\PurchaseOrderCalculator::formatCurrency($invoice->amount_paid, $invoice->currency_symbol, $invoice->currency_position) }}</td></tr>
    <tr class="emphasis"><td>{{ __('supplier_invoices.amount_remaining') }}</td><td>{{ $invoice->amount_remaining === null ? '-' : \App\Support\PurchaseOrderCalculator::formatCurrency($invoice->amount_remaining, $invoice->currency_symbol, $invoice->currency_position) }}</td></tr>
</table>

@if($amountInWords)
    <div class="words"><strong>{{ __('financial.labels.amount_in_words') }}:</strong> {{ $amountInWords }}</div>
@endif
@if($invoice->review_reason)
    <div class="review">{{ $invoice->review_reason }}</div>
@endif
@include('pages.customers.documents.partials.company-footer', ['document_footer_message' => __('financial.labels.invoice_for_records')])
</body>
</html>
