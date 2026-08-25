<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Receipt</title>
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
        <a class="btn-back" href="{{ route('customers.invoices.index') }}">Back</a>
    </div>
    <div class="receipt-header">
        <h1>Specledgers</h1>
    </div>
    <div class="receipt-details">
        <table>
            <tr>
                <th>Invoice Number</th>
                <td>{{ $invoice->invoice_number }}</td>
            </tr>
            <tr>
                <th>Customer Name</th>
                <td>{{ $customer->name }}</td>
            </tr>
            <tr>
                <th>Customer Address</th>
                <td>{{ $customer->address }}</td>
            </tr>
            <tr>
                <th>Branch</th>
                <td>{{ $customer->branch ? $customer->branch->name : '' }}</td>
            </tr>

            <tr>
                <th>Date</th>
                <td>{{ $invoice->invoice_date }}</td>
            </tr>
        </table>
    </div>
    <div class="receipt-details">
        <table>
            <thead>
            <tr>
                <th>Item</th>
                <th>Description</th>
                <th>Quantity</th>
                <th>Rate</th>
                <th>Amount</th>
                <th>Balance Due</th>
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
                <th colspan="4">Total</th>
                <td>{{ $totalAmount }}</td>
                <td>{{ $totalAmount }}</td>
            </tr>
            </tfoot>
        </table>
    </div>
    <div class="receipt-footer">
        <button onclick="window.print()">Print Receipt</button>
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
