<!DOCTYPE html>
<html lang="{{ app()->getLocale() === 'main' ? 'en' : (app()->getLocale() === 'zh' ? 'zh-CN' : app()->getLocale()) }}" dir="{{ \App\Support\LocaleManager::direction(app()->getLocale()) }}">
<head>
    <title>{{ __('financial.terms.receipt') }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif; direction: inherit; text-align: start;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: start;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
<h1>{{ __('financial.terms.receipt') }}</h1>

<p><strong>{{ __('financial.labels.invoice_number') }}:</strong> {{ $payment->invoice->invoice_number }}</p>
<p><strong>{{ __('financial.terms.vendor') }}:</strong> {{ $payment->vendor->name }}</p>
<p><strong>{{ __('financial.labels.date') }}:</strong> {{ $payment->created_at->format('Y-m-d') }}</p>
<p><strong>{{ __('financial.labels.payment_mode') }}:</strong> {{ $payment_mode }}</p>

<table>
    <thead>
    <tr>
        <th>{{ __('financial.labels.invoice_number') }}</th>
        <th>{{ __('financial.labels.payment_mode') }}</th>
        <th>{{ __('financial.labels.invoice_total') }}</th>
        <th>{{ __('financial.terms.wht') }}</th>
        <th>{{ __('financial.labels.net_amount_payable') }}</th>
        <th>{{ __('financial.labels.amount_paid_cumulative') }}</th>
        <th>{{ __('financial.labels.balance_due') }}</th>
        <th>{{ __('financial.labels.payment_date') }}</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>{{ $payment->invoice->invoice_number }}</td>
        <td>{{ $payment_mode }}</td>
        <td>{{ $currency_symbol }}{{ number_format((float) $settlement['invoice_total'], 2) }}</td>
        <td>{{ $settlement['settlement_available'] ? $currency_symbol . number_format((float) $settlement['wht_amount'], 2) : __('financial.labels.review_required') }}</td>
        <td>{{ $settlement['settlement_available'] ? $currency_symbol . number_format((float) $settlement['net_payable'], 2) : __('financial.labels.review_required') }}</td>
        <td>{{ $currency_symbol }}{{ number_format((float) $payment->amount_paid, 2) }}</td>
        <td>{{ $settlement['settlement_available'] ? $currency_symbol . number_format((float) $settlement['balance_due'], 2) : __('financial.labels.review_required') }}</td>
        <td>{{ $payment->created_at->format('Y-m-d') }}</td>
    </tr>
    </tbody>
</table>

</body>
</html>
