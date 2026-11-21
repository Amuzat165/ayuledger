@extends('layouts.form-table-layout')

@section('subtitle', __('financial.labels.create_receipt'))

@section('container')
    <x-breadcrumb :level="__('financial.screens.receipts')" :location="__('financial.labels.create_receipt')" :as="'form'"/>

    <div class="row clearfix">
        <div class="col-xs-12">
            <div class="card">
                <x-form.form-title-wrapper :h1="__('financial.labels.create_receipt')">
                    <form class="form" id="form_validation" method="post" action="#">
                        @csrf
                        <div class="row clearfix">
                            <div class="col-md-6">
                                <label for="customer_id">{{ __('financial.labels.select_customer') }}</label>
                                <select name="customer_id" id="customer_id" class="form-control" required>
                                    <option value="">{{ __('financial.labels.select_customer') }}</option>
                                    @foreach($customer_id_options as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 text-right">
                                <p><strong>{{ __('financial.labels.branch') }}:</strong> <span id="branch_name"></span></p>
                                <p><strong>{{ __('financial.labels.date') }}:</strong> <span id="current_date"></span></p>
                                <p><strong>{{ __('financial.labels.receipt_number') }}:</strong> <span id="receipt_number"></span></p>
                            </div>
                        </div>
                        <div class="row clearfix mt-3">
                            <div class="col-md-12">
                                <div id="table-print-section">
                                    <h4 id="customer_name_print"></h4> <!-- Display customer name for printing -->
                                    <table class="table table-bordered">
                                        <thead>
                                        <tr>
                                            <th>{{ __('financial.labels.invoice_number') }}</th>
                                            <th>{{ __('financial.labels.date') }}</th>
                                            <th>{{ __('financial.labels.exchange_rate') }}</th>
                                            <th>{{ __('financial.terms.total_amount') }}</th>
                                            <th>{{ __('financial.labels.balance_due') }}</th>
                                            <th>{{ __('financial.labels.actions') }}</th>
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
                                                    @if(!$invoice->payment_eligible)
                                                        <span>{{ __('financial.labels.historical_invoice_requires_review') }}</span>
                                                    @elseif($invoice->is_paid)
                                                        <span>{{ __('financial.labels.paid') }}</span>
                                                    @else
                                                        <button type="button" class="btn btn-primary"
                                                                data-invoice-id="{{ $invoice->id }}"
                                                                data-amount-due="{{ $invoice->total_amount }}"
                                                                data-balance-due="{{ $invoice->balance_due }}"
                                                                onclick="handlePayButtonClick(this)">
                                                            {{ __('financial.labels.pay') }} {{ $invoice->total_amount }}
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
                                <button type="button" class="btn btn-primary" onclick="printReceipt()">{{ __('financial.labels.print') }}</button>
                            </div>
                        </div>
                    </form>
                </x-form.form-title-wrapper>
            </div>
        </div>
    </div>

    <script>
        const currencySymbol = @json($currency_symbol);
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

        function generateIdempotencyKey() {
            if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
                const r = Math.random() * 16 | 0;
                return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
            });
        }

        // Function to update current date and receipt number
        function updateDateAndReceipt() {
            const currentDate = new Date();
            document.getElementById('current_date').innerText = formatDate(currentDate);
            document.getElementById('receipt_number').innerText = '{{ __('financial.labels.assigned_after_payment') }}';
        }

        // Call updateDateAndReceipt on page load
        updateDateAndReceipt();

        // Print receipt function
        // Print receipt function
        function printReceipt() {
            const customerName = document.getElementById('customer_id').options[document.getElementById('customer_id').selectedIndex].text;
            document.getElementById('customer_name_print').innerText = `${@json(__('financial.labels.customer_name'))}: ${customerName}`;

            const currentDate = document.getElementById('current_date').innerText;
            const receiptNumber = document.getElementById('receipt_number').innerText;
            const branch = document.getElementById('branch_name').innerText;

            const printContents = `
        <div>
            <p><strong>${@json(__('financial.labels.branch'))}:</strong> ${branch}</p>
            <p><strong>${@json(__('financial.labels.date'))}:</strong> ${currentDate}</p>
            <p><strong>${@json(__('financial.labels.receipt_number'))}:</strong> ${receiptNumber}</p>
            <h4 id="customer_name_print">${@json(__('financial.labels.customer_name'))}: ${customerName}</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>${@json(__('financial.labels.invoice_number'))}</th>
                        <th>${@json(__('financial.labels.date'))}</th>
                        <th>${@json(__('financial.labels.exchange_rate'))}</th>
                        <th>${@json(__('financial.terms.total_amount'))}</th>
                        <th>${@json(__('financial.labels.balance_due'))}</th>
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
            document.getElementById('customer_name_print').innerText = '';

            // Restore current date and receipt number after printing
            updateDateAndReceipt();
        }

        // Fetch invoice details on customer selection
        // Fetch invoice details on customer selection
        document.getElementById('customer_id').addEventListener('change', function () {
            const customerId = this.value;
            if (customerId) {
                fetch(`{{ url('/customers') }}/${customerId}/invoices`)
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
                                <td>${invoice.payment_eligible ? currencySymbol + Number(invoice.total_amount).toLocaleString() : '—'}</td>
                                <td>${invoice.payment_eligible ? currencySymbol + Number(invoice.balance_due).toLocaleString() : @json(__('financial.labels.review_required'))}</td>
                                <td>
                                    ${!invoice.payment_eligible ?
                                    '<span class="label label-warning">' + @json(__('financial.labels.historical_invoice_requires_review')) + '</span>' : invoice.is_paid ?
                                    '<span class="label label-success">' + @json(__('financial.labels.paid')) + '</span>' :
                                    `<button type="button" class="btn btn-primary pay-button"
                                            data-invoice-id="${invoice.id}"
                                            data-amount-due="${invoice.total_amount}"
                                            data-balance-due="${invoice.balance_due}"
                                            onclick="handlePayButtonClick(this)">
                                            ${@json(__('financial.labels.pay'))}
                                        </button>`}
                                </td>
                            </tr>
                        `;
                            });
                            document.getElementById('invoice-details-body').innerHTML = invoiceDetailsBody;

                            // Set customer name for printing
                            const customerName = document.getElementById('customer_id').options[document.getElementById('customer_id').selectedIndex].text;
                            document.getElementById('customer_name_print').innerText = `${@json(__('financial.labels.customer_name'))}: ${customerName}`;

                            // Fetch and display branch name
                            const branchName = data.branch ? data.branch.name : 'N/A';
                            document.getElementById('branch_name').innerText = `${branchName}`;
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching invoice details:', error);
                        document.getElementById('invoice-details-body').innerHTML = '';
                        document.getElementById('branch_name').innerText = '';
                    });
            } else {
                document.getElementById('invoice-details-body').innerHTML = '';
                document.getElementById('branch_name').innerText = '';
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
            const idempotencyKey = generateIdempotencyKey();
            // Modal HTML to be appended
            const paymentFormHtml = `
    <div id="paymentModal" class="modal">
        <div class="modal-header">
            <h4>${@json(__('financial.labels.pay_invoice'))} #${invoiceId}</h4>
            <span class="close" onclick="document.getElementById('paymentModal').remove()">&times;</span>
        </div>
        <div class="modal-content">
            <input type="hidden" id="payment_idempotency_key" value="${idempotencyKey}">
            <div class="form-row">
                <label for="total_amount_due">${@json(__('financial.terms.total_amount'))}</label>
                <input type="text" id="total_amount_due" value="${currencySymbol}${totalAmountDue.toFixed(2)}" disabled>
            </div>

            <div class="form-row">
                <label for="payment_amount">${@json(__('financial.labels.amount_to_pay'))}</label>
            <input type="number" id="payment_amount" name="payment_amount" min="0.01" step="0.01" value="${totalBalanceDue}" required>
            </div>

            <div class="form-row">
                <label for="payment_mode">${@json(__('financial.labels.payment_mode'))}</label>
                <select id="payment_mode" name="payment_mode" required>
                    <option value="">${@json(__('financial.labels.select_payment_mode'))}</option>
                    @foreach($payment_modes as $payment_mode)
            <option value="{{ $payment_mode->id }}">{{ $payment_mode->name }}</option>
                    @endforeach
            </select>
            <span id="payment_mode_error" class="error-message" style="color: red; display: none;"></span>
        </div>
        <button type="button" class="btn btn-primary" onclick="submitPayment(${invoiceId}, ${totalAmountDue})" style="flex: 1 1 100%;">${@json(__('financial.labels.submit_payment'))}</button>
        </div>
    </div>
    `;

            document.body.insertAdjacentHTML('beforeend', paymentFormHtml);

            // document.getElementById('payment_amount').addEventListener('input', function () {
            //     const paymentAmount = parseFloat(this.value) || 0;
            //     const remainingBalance = totalAmountDue - paymentAmount;
            //     document.getElementById('remaining_balance').value = remainingBalance.toFixed(2);
            // });
        }

        function submitPayment(invoiceId, totalAmountDue) {
            const paymentAmount = parseFloat(document.getElementById('payment_amount').value);
            const paymentModeId = document.getElementById('payment_mode').value;
            const paymentModeError = document.getElementById('payment_mode_error');

            // Clear any previous error message
            paymentModeError.style.display = 'none';
            paymentModeError.innerText = '';

            // Validate payment mode selection
            if (!paymentModeId) {
                paymentModeError.innerText = @json(__('financial.labels.select_payment_mode'));
                paymentModeError.style.display = 'block';
                return;
            }

            fetch(`{{url('/invoices')}}/${invoiceId}/pay`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({
                    amount: paymentAmount,
                    payment_mode_id: paymentModeId,
                    idempotency_key: document.getElementById('payment_idempotency_key').value
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
                        alert(@json(__('ui.messages.operation_failed')) + ': ' + (data.error || data.message || ''));
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
