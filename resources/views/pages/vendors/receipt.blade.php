<!DOCTYPE html>
<html lang="{{ app()->getLocale() === 'main' ? 'en' : (app()->getLocale() === 'zh' ? 'zh-CN' : app()->getLocale()) }}" dir="{{ \App\Support\LocaleManager::direction(app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('financial.terms.invoice') }} {{ __('financial.terms.receipt') }}</title>
    <style>
        .receipt-container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            font-family: Arial, sans-serif;
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .receipt-header h1 {
            margin: 0;
            font-size: 24px;
        }
        .receipt-details {
            margin-bottom: 20px;
        }
        .receipt-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .receipt-details th, .receipt-details td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .receipt-footer {
            text-align: right;
            margin-top: 20px;
        }
        .btn-back{
            background-color: #0D47A1;
            color: white;
            border: 1px;
            border-radius: 5px;
            text-decoration: none;
            padding: 4px;
        }
    </style>
</head>
<body>
<div class="receipt-container">
    <div class="block-header">
        <a class="btn-back" href="{{ route('vouchers.index') }}">{{ __('financial.labels.back') }}</a>
    </div>
    <div class="receipt-header">
        <h1>Specledgers</h1>
    </div>
    <div class="receipt-details">
        <table>
            <tr>
                <th>{{ __('financial.labels.invoice_number') }}</th>
                <td>{{ $invoice->invoice_number }}</td>
            </tr>
            <tr>
                <th>{{ __('financial.labels.vendor_name') }}</th>
                <td>{{ $vendor->name }}</td>
            </tr>
            <tr>
                <th>{{ __('financial.labels.vendor_address') }}</th>
                <td>{{ $vendor->address }}</td>
            </tr>
            <tr>
                <th>{{ __('financial.labels.branch') }}</th>
                <td>{{ $vendor->branch ? $vendor->branch->name : '' }}</td>
            </tr>
            <tr>
                <th>{{ __('financial.labels.date') }}</th>
                <td>{{ $invoice->invoice_date }}</td>
            </tr>
        </table>
    </div>
    <div class="receipt-details">
        <table>
            <thead>
            <tr>
                <th>{{ __('financial.labels.item') }}</th>
                <th>{{ __('financial.terms.description') }}</th>
                <th>{{ __('financial.terms.quantity') }}</th>
                <th>{{ __('financial.labels.rate') }}</th>
                <th>{{ __('financial.terms.total_amount') }}</th>
                <th>{{ __('financial.labels.balance_due') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($invoiceItems as $item)
                <tr>
                    <td>{{ $item->inventory->type->name }}</td>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->rate }}</td>
                    <td>{{ $item->amount }}</td>
                    <td>{{ $item->amount }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
            <tr>
                <th colspan="4">{{ __('financial.labels.vendor_total') }}</th>
                <td>{{ $currency_symbol }}{{ number_format((float) $totalAmount, 2) }}</td>
                <td>{{ $currency_symbol }}{{ number_format((float) $totalAmount, 2) }}</td>
            </tr>
            @if($invoice->wht_enabled_snapshot)
                <tr>
                    <th colspan="4">{{ __('financial.labels.withholding_tax') }} ({{ number_format((float) $invoice->wht_rate_snapshot, 2) }}%)</th>
                    <td>{{ $currency_symbol }}{{ number_format((float) $invoice->withholding_tax_amount, 2) }}</td>
                    <td></td>
                </tr>
            @endif
            <tr>
                <th colspan="4">{{ __('financial.labels.balance_due') }}</th>
                <td>{{ $currency_symbol }}{{ number_format((float) $balanceDue, 2) }}</td>
                <td>{{ $currency_symbol }}{{ number_format((float) $balanceDue, 2) }}</td>
            </tr>
            </tfoot>
        </table>
    </div>
    <div class="receipt-footer">
        <button onclick="window.print()">{{ __('financial.labels.print_receipt') }}</button>
    </div>
</div>
</body>
</html>

<script>
    // Open receipt in a new tab for printing
    window.onload = function () {
        window.open('', '_blank').document.write(document.documentElement.innerHTML);
        window.print();
        window.close();
    }
</script>
