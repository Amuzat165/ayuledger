@extends('layouts.form-table-layout')

@section('subtitle', __('financial.screens.create_payment_voucher'))

@section('container')
    <x-breadcrumb :level="__('financial.terms.payment')" :location="__('financial.screens.create_payment_voucher')" :as="'form'" />

    <div class="row clearfix">
        <div class="col-xs-12">
            <div class="card">
                <x-form.form-title-wrapper :h1="__('financial.screens.create_payment_voucher')">
                    <form class="form" id="form_validation" method="post" action="{{ $action }}">
                        @csrf
                        <div class="row clearfix">
                            <div class="col-md-6">
                                <label for="vendor_id">{{ __('financial.labels.select_vendor') }}</label>
                                <select name="vendor_id" id="vendor_id" class="form-control" required>
                                    <option value="">{{ __('financial.labels.select_vendor') }}</option>
                                    @foreach($vendor_id_options as $vendor)
                                        <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 text-right">
                                <p><strong>{{ __('financial.labels.branch') }}:</strong> <span id="branch_name"></span></p>
                                <p><strong>{{ __('financial.labels.date') }}:</strong> <span id="current_date"></span></p>
                                <p><strong>{{ __('financial.screens.voucher') }}:</strong> <span id="voucher_number"></span></p>
                            </div>
                        </div>
                        <div class="row clearfix mt-3">
                            <div class="col-md-12">
                                <div id="table-print-section">
                                    <h4 id="vendor_name_print"></h4> <!-- Display vendor name for printing -->
                                    <table class="table table-bordered">
                                        <thead>
                                        <tr>
                                            <th>{{ __('financial.labels.invoice_number') }}</th><th>{{ __('financial.labels.date') }}</th><th>{{ __('financial.labels.exchange_rate') }}</th>
                                            <th>{{ __('financial.labels.invoice_total') }}</th><th>{{ __('financial.terms.wht') }}</th><th>{{ __('financial.labels.net_amount_payable') }}</th>
                                            <th>{{ __('financial.terms.amount_paid') }}</th><th>{{ __('financial.labels.balance_due') }}</th><th>{{ __('financial.labels.actions') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody id="invoice-details-body">
                                        @foreach($invoices as $invoice)
                                            @php($summary = $invoice->settlement_summary)
                                            <tr>
                                                <td>{{ $invoice->invoice_number }}</td>
                                                <td>{{ $invoice->invoice_date }}</td>
                                                <td>{{ $invoice->exchange_rate }}</td>
                                                <td>{{ $currency_symbol }}{{ number_format((float) $summary['invoice_total'], 2) }}</td>
                                                <td>{{ $summary['settlement_available'] ? $currency_symbol . number_format((float) $summary['wht_amount'], 2) : __('financial.labels.review_required') }}</td>
                                                <td>{{ $summary['settlement_available'] ? $currency_symbol . number_format((float) $summary['net_payable'], 2) : __('financial.labels.review_required') }}</td>
                                                <td>{{ $summary['amount_paid'] === null ? '—' : $currency_symbol . number_format((float) $summary['amount_paid'], 2) }}</td>
                                                <td>{{ $summary['settlement_available'] ? $currency_symbol . number_format((float) $summary['balance_due'], 2) : __('financial.labels.review_required') }}</td>
                                                <td>
                                                    @if(!$summary['settlement_available'])
                                                        <span title="{{ $summary['review_message'] }}">{{ __('financial.labels.review_required') }}</span>
                                                    @elseif($summary['balance_due'] === '0.00')
                                                        <span>{{ __('financial.labels.paid') }}</span>
                                                    @else
                                                        <button type="button" class="btn btn-primary"
                                                                data-invoice-id="{{ $invoice->id }}"
                                                                data-invoice-total="{{ $summary['invoice_total'] }}"
                                                                data-wht="{{ $summary['wht_amount'] }}"
                                                                data-net-payable="{{ $summary['net_payable'] }}"
                                                                data-amount-paid="{{ $summary['amount_paid'] }}"
                                                                data-balance-due="{{ $summary['balance_due'] }}"
                                                                onclick="handlePayButtonClick(this)">
                                                            {{ __('Pay') }} {{ $currency_symbol }}{{ number_format((float) $summary['balance_due'], 2) }}
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
                                <button type="button" class="btn btn-primary" onclick="printVoucher()">{{ __('financial.labels.print') }}</button>
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
            document.getElementById('vendor_name_print').innerText = `${@json(__('financial.labels.vendor_name'))}: ${vendorName}`;

            const currentDate = document.getElementById('current_date').innerText;
            const voucherNumber = document.getElementById('voucher_number').innerText;
            const branch = document.getElementById('branch_name').innerText;

            const printContents = `
        <div>
            <p><strong>${@json(__('financial.labels.branch'))}:</strong> ${branch}</p>
            <p><strong>${@json(__('financial.labels.date'))}:</strong> ${currentDate}</p>
            <p><strong>${@json(__('financial.screens.voucher'))}:</strong> ${voucherNumber}</p>
            <h4 id="vendor_name_print">${@json(__('financial.labels.vendor_name'))}: ${vendorName}</h4>
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
                                const summary = invoice.settlement_summary;
                                const reviewRequired = !summary.settlement_available;
                                invoiceDetailsBody += `
                            <tr>
                                <td>${invoice.invoice_number}</td>
                                <td>${new Date(invoice.invoice_date).toLocaleDateString()}</td>
                                <td>${invoice.exchange_rate}</td>
                                <td>${formatMoney(summary.invoice_total)}</td>
                                <td>${reviewRequired ? @json(__('financial.labels.review_required')) : formatMoney(summary.wht_amount)}</td>
                                <td>${reviewRequired ? @json(__('financial.labels.review_required')) : formatMoney(summary.net_payable)}</td>
                                <td>${summary.amount_paid === null ? '—' : formatMoney(summary.amount_paid)}</td>
                                <td>${reviewRequired ? @json(__('financial.labels.review_required')) : formatMoney(summary.balance_due)}</td>
                                <td>
                                    ${reviewRequired ?
                                    '<span title="' + summary.review_message + '">' + @json(__('financial.labels.review_required')) + '</span>' : (summary.balance_due === '0.00' ?
                                    '<span class="label label-success">' + @json(__('financial.labels.paid')) + '</span>' :
                                    `<button type="button" class="btn btn-primary pay-button"
                                            data-invoice-id="${invoice.id}"
                                            data-invoice-total="${summary.invoice_total}"
                                            data-wht="${summary.wht_amount}"
                                            data-net-payable="${summary.net_payable}"
                                            data-amount-paid="${summary.amount_paid}"
                                            data-balance-due="${summary.balance_due}"
                                            onclick="handlePayButtonClick(this)">
                                            ${@json(__('financial.labels.pay'))} ${formatMoney(summary.balance_due)}
                                        </button>`)}
                                </td>
                            </tr>
                        `;
                            });
                            document.getElementById('invoice-details-body').innerHTML = invoiceDetailsBody;

                            // Set vendor name for printing
                            const vendorName = document.getElementById('vendor_id').options[document.getElementById('vendor_id').selectedIndex].text;
                            document.getElementById('vendor_name_print').innerText = `${@json(__('financial.labels.vendor_name'))}: ${vendorName}`;

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

        const currencySymbol = @json($currency_symbol);
        const formatMoney = value => `${currencySymbol}${Number(value).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

        function handlePayButtonClick(button) {
            // Remove any existing modal to avoid duplicates
            const existingModal = document.getElementById('paymentModal');
            if (existingModal) {
                existingModal.remove();
            }

            const invoiceId = button.getAttribute('data-invoice-id');
            const invoiceTotal = button.getAttribute('data-invoice-total');
            const wht = button.getAttribute('data-wht');
            const netPayable = button.getAttribute('data-net-payable');
            const amountPaid = button.getAttribute('data-amount-paid');
            const totalBalanceDue = button.getAttribute('data-balance-due');
            // Modal HTML to be appended
            const paymentFormHtml = `
    <div id="paymentModal" class="modal">
        <div class="modal-header">
            <h4>${@json(__('financial.labels.pay_invoice'))} #${invoiceId}</h4>
            <span class="close" onclick="document.getElementById('paymentModal').remove()">&times;</span>
        </div>
        <div class="modal-content">
            <div class="form-row">
                <label>${@json(__('financial.labels.invoice_total'))}</label><input type="text" value="${formatMoney(invoiceTotal)}" disabled>
                <label>${@json(__('financial.terms.wht'))}</label><input type="text" value="${formatMoney(wht)}" disabled>
                <label>${@json(__('financial.labels.net_amount_payable'))}</label><input type="text" value="${formatMoney(netPayable)}" disabled>
                <label>${@json(__('financial.labels.amount_paid_cumulative'))}</label><input type="text" value="${formatMoney(amountPaid)}" disabled>
                <label>${@json(__('financial.labels.balance_due'))}</label><input type="text" value="${formatMoney(totalBalanceDue)}" disabled>
            </div>

            <div class="form-row">
                <label for="payment_amount">${@json(__('financial.labels.amount_to_pay'))}</label>
                <input type="number" id="payment_amount" name="payment_amount" min="0.01" step="0.01" max="${totalBalanceDue}" value="${totalBalanceDue}" required>
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
        <button type="button" class="btn btn-primary" onclick="submitPayment(${invoiceId})" style="flex: 1 1 100%;">${@json(__('financial.labels.submit_payment'))}</button>
        </div>
    </div>
    `;

            // Append modal to the body
            document.body.insertAdjacentHTML('beforeend', paymentFormHtml);

        }

        function submitPayment(invoiceId) {
            const paymentAmount = document.getElementById('payment_amount').value;
            const paymentModeId = document.getElementById('payment_mode').value;
            const paymentModeError = document.getElementById('payment_mode_error'); // Error message element

            // Clear any previous error message
            paymentModeError.style.display = 'none';
            paymentModeError.innerText = '';

            // Validate payment mode selection
            if (!paymentModeId) {
                paymentModeError.innerText = @json(__('financial.labels.select_payment_mode'));
                paymentModeError.style.display = 'block';
                return;
            }

            fetch(`{{url('/purchases')}}/${invoiceId}/payment`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({
                    amount: paymentAmount,
                    payment_mode_id: paymentModeId
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
                        const message = data.message || data.error || Object.values(data.errors || {}).flat().join(' ') || @json(__('ui.messages.operation_failed'));
                        alert(@json(__('ui.messages.operation_failed')) + ': ' + message);
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
