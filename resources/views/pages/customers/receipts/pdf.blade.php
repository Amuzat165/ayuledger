<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('financial.terms.receipt') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            line-height: 1.6;
        }
        .pdf-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header-section {
            margin-bottom: 30px;
            border-bottom: 3px solid {{ $company_color }};
            padding-bottom: 20px;
        }
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .company-info {
            flex: 1;
        }
        .company-logo {
            max-width: 150px;
            max-height: 80px;
            margin-bottom: 10px;
        }
        .company-name {
            font-size: 28px;
            font-weight: bold;
            color: {{ $company_color }};
            margin-bottom: 8px;
        }
        .company-details {
            font-size: 12px;
            color: #666;
            line-height: 1.5;
        }
        .header-image {
            max-width: 300px;
            max-height: 80px;
            margin-bottom: 10px;
        }
        .receipt-title {
            text-align: right;
            font-size: 24px;
            font-weight: bold;
            color: {{ $company_color }};
            text-transform: uppercase;
        }
        .receipt-details {
            margin-bottom: 30px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .detail-label {
            font-weight: 600;
            color: #555;
            width: 25%;
        }
        .detail-value {
            text-align: right;
            width: 25%;
        }
        .customer-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-left: 4px solid {{ $company_color }};
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: {{ $company_color }};
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        thead {
            background-color: {{ $company_color }};
            color: white;
        }
        th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #ddd;
            font-size: 13px;
        }
        tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        tbody tr:hover {
            background-color: #f0f0f0;
        }
        .amount-column {
            text-align: right;
            font-weight: 500;
        }
        .summary-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid {{ $company_color }};
        }
        .summary-row {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 12px;
            font-size: 14px;
        }
        .summary-row.total {
            font-weight: bold;
            font-size: 16px;
            color: {{ $company_color }};
            padding: 10px 0;
        }
        .summary-label {
            width: 40%;
            text-align: right;
            padding-right: 20px;
        }
        .summary-value {
            width: 20%;
            text-align: right;
        }
        .footer-section {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 11px;
            color: #666;
            text-align: center;
        }
        .footer-note {
            margin-top: 15px;
            padding: 15px;
            background-color: #f0f0f0;
            border-radius: 4px;
            font-size: 12px;
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-paid {
            background-color: #d4edda;
            color: #155724;
        }
        .badge-pending {
            background-color: #fff3cd;
            color: #856404;
        }
    </style>
</head>
<body>
<div class="pdf-container">
    <!-- Header Section -->
    @php($hasHeaderImage = $header_image && file_exists(public_path($header_image)))
    <div class="header-section">
        @if($hasHeaderImage)
            <div style="margin-bottom: 12px;">
                <img class="document-banner" src="{{ public_path($header_image) }}" alt="{{ __('financial.labels.header_image') }}" style="display:block;width:auto;max-width:100%;height:auto;max-height:0.95in;object-fit:contain;object-position:center;margin:0 auto;">
            </div>
        @endif
        <div class="header-top">
            <div class="company-info" @if($hasHeaderImage) style="font-size:9px;line-height:1.35;color:#526174;" @endif>
                @if(!$hasHeaderImage && $company_logo && file_exists(public_path($company_logo)))
                    <img src="{{ public_path($company_logo) }}" alt="{{ __('Company Logo') }}" class="company-logo">
                @endif
                <div class="company-name" @if($hasHeaderImage) style="font-size:9px;font-weight:normal;color:inherit;" @endif>{{ $company_name }}</div>
                <div class="company-details">
                    <div>{{ $address_1 }}@if($address_2), {{ $address_2 }}@endif</div>
                    <div>{{ $city }}</div>
                    <div>{{ __('financial.labels.receipt_contact_details', ['phone' => $phone, 'email' => $email]) }}</div>
                </div>
            </div>
            <div style="text-align: right;">
                <div class="receipt-title">{{ __('financial.terms.payment_receipt') }}</div>
            </div>
        </div>

    </div>

    <!-- Receipt Details Table -->
    <table>
        <tr>
            <td style="font-weight: 600; color: #555; width: 25%;">{{ __('financial.labels.receipt_number') }}:</td>
            <td style="text-align: right; width: 25%; font-weight: 500;"><strong>{{ $payment->id }}</strong></td>
            <td style="font-weight: 600; color: #555; width: 25%;">{{ __('financial.labels.invoice_number') }}:</td>
            <td style="text-align: right; width: 25%; font-weight: 500;"><strong>{{ $payment->invoice->invoice_number }}</strong></td>
        </tr>
        <tr>
            <td style="font-weight: 600; color: #555; width: 25%; padding-top: 8px;">{{ __('financial.labels.payment_date') }}:</td>
            <td style="text-align: right; width: 25%; padding-top: 8px;">{{ $payment->created_at->format('F d, Y') }}</td>
            <td style="font-weight: 600; color: #555; width: 25%; padding-top: 8px;">{{ __('financial.labels.payment_mode') }}:</td>
            <td style="text-align: right; width: 25%; padding-top: 8px;">{{ $payment_mode }}</td>
        </tr>
    </table>

    <!-- Customer Information -->
    <div class="customer-section">
        <div class="section-title">{{ __('financial.labels.bill_to') }}</div>
        <div style="font-size: 14px;">
            <strong>{{ $payment->customer->name }}</strong><br>
            @if($payment->customer->address)
                {{ $payment->customer->address }}<br>
            @endif
            @if($payment->customer->phone)
                {{ __('financial.screens.phone_number') }}: {{ $payment->customer->phone }}
            @endif
        </div>
    </div>

    <!-- Payment Summary Table -->
    <table>
        <thead>
        <tr>
            <th>{{ __('financial.terms.description') }}</th>
            <th class="amount-column">{{ __('financial.labels.invoice_total') }}</th>
            <th class="amount-column">{{ __('financial.terms.amount_paid') }}</th>
            <th class="amount-column">{{ __('financial.labels.balance_due') }}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>{{ __('financial.labels.payment_for_invoice', ['number' => $payment->invoice->invoice_number]) }}</td>
            <td class="amount-column">{{ $currency_symbol }}{{ number_format($payment->invoice->total_amount ?? $payment->total_amount, 2) }}</td>
            <td class="amount-column">{{ $currency_symbol }}{{ number_format($payment->amount_paid, 2) }}</td>
            <td class="amount-column">
                <strong>{{ $currency_symbol }}{{ number_format($payment->balance_due, 2) }}</strong>
            </td>
        </tr>
        </tbody>
    </table>

    <!-- Summary Section Table -->
    <table style="margin-top: 30px; width: 50%; margin-left: auto;">
        <tr>
            <td style="text-align: right; padding: 8px; border-bottom: 1px solid #ddd; font-weight: 600; width: 50%;">{{ __('financial.terms.subtotal') }}:</td>
            <td style="text-align: right; padding: 8px; border-bottom: 1px solid #ddd; width: 50%;">{{ $currency_symbol }}{{ number_format($payment->amount_paid, 2) }}</td>
        </tr>
        @if($vat_percentage > 0)
        <tr>
            <td style="text-align: right; padding: 8px; border-bottom: 1px solid #ddd; font-weight: 600;">{{ __('financial.terms.vat') }} ({{ $vat_percentage }}%):</td>
            <td style="text-align: right; padding: 8px; border-bottom: 1px solid #ddd;">{{ $currency_symbol }}{{ number_format(($payment->amount_paid * $vat_percentage) / 100, 2) }}</td>
        </tr>
        @endif
        <tr>
            <td style="text-align: right; padding: 12px; font-weight: bold; font-size: 16px; color: {{ $company_color }};">{{ __('financial.labels.total_amount_paid') }}:</td>
            <td style="text-align: right; padding: 12px; font-weight: bold; font-size: 16px; color: {{ $company_color }};">{{ $currency_symbol }}{{ number_format($payment->amount_paid, 2) }}</td>
        </tr>
        <tr>
            <td style="text-align: right; padding: 8px; font-weight: 600;">{{ __('financial.labels.balance_due') }}:</td>
            <td style="text-align: right; padding: 8px;">
                @if($payment->balance_due == 0)
                    <span class="badge badge-paid">{{ __('financial.labels.paid') }}</span>
                @else
                    {{ $currency_symbol }}{{ number_format($payment->balance_due, 2) }}
                @endif
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <div class="footer-section">
        <p>{{ __('financial.labels.thank_you_for_payment') }}</p>
        <p>{{ __('financial.labels.keep_receipt_for_records') }}</p>
        <div class="footer-note">
            {{ __('financial.labels.receipt_contact_details', ['phone' => $phone, 'email' => $email]) }}
        </div>
    </div>
</div>
</body>
</html>
