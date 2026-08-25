<!DOCTYPE html>
<html>
<head>
    <title>Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
<h1>Receipt</h1>

<p><strong>Invoice Number:</strong> {{ $payment->invoice->invoice_number }}</p>
<p><strong>Vendor:</strong> {{ $payment->vendor->name }}</p>
<p><strong>Date:</strong> {{ $payment->created_at->format('Y-m-d') }}</p>
<p><strong>Payment Mode:</strong> {{ $payment->paymentMode->name }}</p>

<table>
    <thead>
    <tr>
        <th>Invoice Number</th>
        <th>Payment Mode</th>
        <th>Total Amount</th>
        <th>Amount Paid</th>
        <th>Balance Due</th>
        <th>Payment Date</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>{{ $payment->invoice->invoice_number }}</td>
        <td>{{ $payment->paymentMode->name }}</td>
        <td>{{ number_format($payment->total_amount, 2) }}</td>
        <td>{{ number_format($payment->amount_paid, 2) }}</td>
        <td>{{ number_format($payment->balance_due, 2) }}</td>
        <td>{{ $payment->created_at->format('Y-m-d') }}</td>
    </tr>
    </tbody>
</table>

</body>
</html>
