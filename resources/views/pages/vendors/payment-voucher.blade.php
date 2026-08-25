@extends('layouts.form-table-layout')

@section('subtitle', 'Add Payment Voucher')

@section('container')
    <x-breadcrumb :level="'Payment Vouchers'" :location="'Add Payment Voucher'" :as="'form'" />

    <div class="row clearfix">
        <div class="col-xs-12">
            <div class="card">
                <x-form.form-title-wrapper h1="Create Payment Voucher">
                    <form class="form" id="form_validation" method="post" action="{{ $action }}">
                        @csrf
                        <div class="row clearfix">
                            <div class="col-md-6">
                                <label for="vendor_id">Select Vendor</label>
                                <select name="vendor_id" id="vendor_id" class="form-control" required>
                                    <option value="">Select Vendor</option>
                                    @foreach($vendor_id_options as $vendor)
                                        <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 text-right">
                                <p><strong>Branch:</strong> <span id="branch_name"></span></p>
                                <p><strong>Date:</strong> <span id="current_date"></span></p>
                                <p><strong>Voucher No:</strong> <span id="voucher_number"></span></p>
                            </div>
                        </div>
                        <div class="row clearfix mt-3">
                            <div class="col-md-12">
                                <div id="table-print-section">
                                    <h4 id="vendor_name_print"></h4> <!-- Display vendor name for printing -->
                                    <table class="table table-bordered">
                                        <thead>
                                        <tr>
                                            <th>Invoice Number</th>
                                            <th>Date</th>
                                            <th>Exchange Rate</th>
                                            <th>Amount</th>
                                            <th>Balance Due</th>
                                            <th>Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody id="invoice-details-body">
                                        @foreach($invoices as $invoice)
                                            <tr>
                                                <td>{{ $invoice->invoice_number }}</td>
                                                <td>{{ $invoice->invoice_date->format('Y-m-d') }}</td>
                                                <td>{{ $invoice->exchange_rate }}</td>
                                                <td>{{ $invoice->total_amount }}</td>
                                                <td>{{ $invoice->balance_due }}</td>
                                                <td>
                                                    @if($invoice->is_paid)
                                                        <span>Paid</span>
                                                    @else
                                                        <button type="button" class="btn btn-primary"
                                                                data-invoice-id="{{ $invoice->id }}"
                                                                data-amount-due="{{ $invoice->total_amount }}"
                                                                data-balance-due="{{ $invoice->balance_due }}"
                                                                onclick="handlePayButtonClick(this)">
                                                            Pay {{ $invoice->total_amount }}
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-12 mt-3">
                                <button type="button" class="btn btn-primary" onclick="printVoucher()">Print</button>
                            </div>
                        </div>
                    </form>
                </x-form.form-title-wrapper>
            </div>
        </div>
    </div>

    <script>
        // Function to generate a random voucher number
        function generateVoucherNumber(length) {
            const characters = '0123456789';
            let result = '';
            for (let i = 0; i < length; i++) {
                result += characters.charAt(Math.floor(Math.random() * characters.length));
            }
            return result;
        }

        // Function to format the date as YYYY-MM-DD
        function formatDate(date) {
            const year = date.getFullYear();
            let month = date.getMonth() + 1;
            let day = date.getDate();

            if (month < 10) {
                month = '0' + month;
            }
            if (day < 10) {
                day = '0' + day;
            }

            return `${year}-${month}-${day}`;
        }

        // Function to update current date and voucher number
        function updateDateAndVoucher() {
            const currentDate = new Date();
            document.getElementById('current_date').innerText = formatDate(currentDate);
            document.getElementById('voucher_number').innerText = generateVoucherNumber(8);
        }

        // Call updateDateAndVoucher on page load
        updateDateAndVoucher();

        // Print voucher function
        function printVoucher() {
            const vendorName = document.getElementById('vendor_id').options[document.getElementById('vendor_id').selectedIndex].text;
            document.getElementById('vendor_name_print').innerText = `Vendor Name: ${vendorName}`;

            const currentDate = document.getElementById('current_date').innerText;
            const voucherNumber = document.getElementById('voucher_number').innerText;
            const branch = document.getElementById('branch_name').innerText;

            const printContents = `
        <div>
            <p><strong>Branch:</strong> ${branch}</p>
            <p><strong>Date:</strong> ${currentDate}</p>
            <p><strong>Voucher No:</strong> ${voucherNumber}</p>
            <h4 id="vendor_name_print">Vendor Name: ${vendorName}</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Invoice Number</th>
                        <th>Date</th>
                        <th>Exchange Rate</th>
                        <th>Amount</th>
                        <th>Balance Due</th>
                    </tr>
                </thead>
                <tbody id="invoice-details-body">
                    ${document.getElementById('invoice-details-body').innerHTML}
                </tbody>
            </table>
        </div>
    `;

            const originalContents = document.body.innerHTML;

            document.body.innerHTML = printContents;

            window.print();

            document.body.innerHTML = originalContents;
            document.getElementById('vendor_name_print').innerText = '';

            // Restore current date and voucher number after printing
            updateDateAndVoucher();
        }

        // Fetch invoice details on vendor selection
        document.getElementById('vendor_id').addEventListener('change', function() {
            const vendorId = this.value;
            if (vendorId) {
                fetch(`{{ url('/vendors') }}/${vendorId}/invoices`)
                    .then(response => response.json())
                    .then(data => {
                        console.log('Fetched Data:', data);
                        if (data.error) {
                            alert(data.error);
                            document.getElementById('invoice-details-body').innerHTML = '';
                            document.getElementById('branch_name').innerText = '';
                        } else {
                            let invoiceDetailsBody = '';
                            data.invoices.forEach(invoice => {
                                invoiceDetailsBody += `
                            <tr>
                                <td>${invoice.invoice_number}</td>
                                <td>${new Date(invoice.invoice_date).toLocaleDateString()}</td>
                                <td>${invoice.exchange_rate}</td>
                                <td>₦${Number(invoice.total_amount).toLocaleString()}</td>
                                <td>₦${Number(invoice.balance_due).toLocaleString()}</td>
                                <td>
                                    ${invoice.is_paid ?
                                    '<span class="label label-success">Paid</span>' :
                                    `<button type="button" class="btn btn-primary pay-button"
                                            data-invoice-id="${invoice.id}"
                                            data-amount-due="${invoice.total_amount}"
                                            data-balance-due="${invoice.balance_due}"
                                            onclick="handlePayButtonClick(this)">
                                            Pay
                                        </button>`}
                                </td>
                            </tr>
                        `;
                            });
                            document.getElementById('invoice-details-body').innerHTML = invoiceDetailsBody;

                            // Set vendor name for printing
                            const vendorName = document.getElementById('vendor_id').options[document.getElementById('vendor_id').selectedIndex].text;
                            document.getElementById('vendor_name_print').innerText = `Vendor Name: ${vendorName}`;

                            // Fetch and display branch name
                            const branchName = data.branch ? data.branch.name : 'N/A';
                            document.getElementById('branch_name').innerText = `${branchName}`;
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching invoice details:', error);
                        document.getElementById('invoice-details-body').innerHTML = '';
                        document.getElementById('branch_name').innerText = ''; // Clear branch name on error
                    });
            } else {
                document.getElementById('invoice-details-body').innerHTML = '';
                document.getElementById('branch_name').innerText = ''; // Clear branch name if no vendor selected
            }
        });

        function handlePayButtonClick(button) {
            // Remove any existing modal to avoid duplicates
            const existingModal = document.getElementById('paymentModal');
            if (existingModal) {
                existingModal.remove();
            }

            const invoiceId = button.getAttribute('data-invoice-id');
            const totalAmountDue = parseFloat(button.getAttribute('data-amount-due'));
            const totalBalanceDue = parseFloat(button.getAttribute('data-balance-due'));
            // Modal HTML to be appended
            const paymentFormHtml = `
    <div id="paymentModal" class="modal">
        <div class="modal-header">
            <h4>Pay Invoice #${invoiceId}</h4>
            <span class="close" onclick="document.getElementById('paymentModal').remove()">&times;</span>
        </div>
        <div class="modal-content">
            <div class="form-row">
                <label for="total_amount_due">Total Amount</label>
                <input type="text" id="total_amount_due" value="${totalAmountDue.toFixed(2)}" disabled>
            </div>

            <div class="form-row">
                <label for="payment_amount">Amount to Pay</label>
                <input type="number" id="payment_amount" name="payment_amount" max="${totalAmountDue}" value="${totalBalanceDue}" required>
            </div>

            <div class="form-row">
                <label for="payment_mode">Payment Mode</label>
                <select id="payment_mode" name="payment_mode" required>
                    <option value="">Select Payment Mode</option>
                    @foreach($payment_modes as $payment_mode)
            <option value="{{ $payment_mode->id }}">{{ $payment_mode->name }}</option>
                    @endforeach
            </select>
            <span id="payment_mode_error" class="error-message" style="color: red; display: none;"></span>
        </div>
        <button type="button" class="btn btn-primary" onclick="submitPayment(${invoiceId}, ${totalAmountDue})" style="flex: 1 1 100%;">Submit Payment</button>
        </div>
    </div>
    `;

            // Append modal to the body
            document.body.insertAdjacentHTML('beforeend', paymentFormHtml);

            // Add event listener to automatically update the remaining balance when amount to pay is changed
            // document.getElementById('payment_amount').addEventListener('input', function () {
            //     const paymentAmount = parseFloat(this.value) || 0;
            //     const remainingBalance = totalAmountDue - paymentAmount;
            //     document.getElementById('remaining_balance').value = remainingBalance.toFixed(2);
            // });
        }

        function submitPayment(invoiceId, totalAmountDue) {
            const paymentAmount = parseFloat(document.getElementById('payment_amount').value);
            const paymentModeId = document.getElementById('payment_mode').value;
            const paymentModeError = document.getElementById('payment_mode_error'); // Error message element

            // Clear any previous error message
            paymentModeError.style.display = 'none';
            paymentModeError.innerText = '';

            // Validate payment mode selection
            if (!paymentModeId) {
                paymentModeError.innerText = 'Please select a payment mode.';
                paymentModeError.style.display = 'block';
                return;
            }

            fetch(`{{url('/purchases')}}/${invoiceId}/payment`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({
                    amount: paymentAmount,
                    payment_mode_id: paymentModeId,
                    total_amount_due: totalAmountDue
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Open the receipt URL in a new tab
                        window.open(data.receipt_url, '_blank');

                        // Redirect to the view receipts page
                        window.location.href = data.redirect;
                    } else {
                        alert('Payment failed: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error processing payment:', error);
                });
        }

        // Add basic CSS for modal
        const style = document.createElement('style');
        style.innerHTML = `
        .modal {
            display: block;
            position: fixed;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            z-index: 1000;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            width: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h4 {
            margin: 0;
        }

        .close {
            font-size: 2em;
            cursor: pointer;
            background-color: #f44;
            color: white;
            border-radius: 10%;
            width: 30px;
            height: 30px;
            text-align: center;
            line-height: 1;
        }

        .modal-content {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            padding: 20px;
        }

        .form-row {
            flex-basis: calc(50% - 20px); /* Adjust this to control the column width */
            margin-bottom: 10px;
        }

        .form-row label {
            display: block;
            margin-bottom: 5px;
        }

        .modal-content input,
        .modal-content select,
        .modal-content button {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
        }

        .modal-content button {
            padding: 10px;
        }

        .modal-content label,
        .modal-content input,
        .modal-content select,
        .modal-content button {
            width: 100%;
            box-sizing: border-box;
        }
        `;
        document.head.appendChild(style);

    </script>
@endsection
