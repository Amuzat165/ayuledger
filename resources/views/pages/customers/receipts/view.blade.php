@extends('layouts.table-layout')

@section('title', $title)

@section('container')
    @if(session('success'))
        <div class="alert alert-success">
            {{ __(session('success')) }}
        </div>
    @endif

    <div class="row">
        <div class="col-xs-12">
            <div class="card">
                <x-table.table-search-bar :all="count($payments)" :trash="0"/>
                <form class="actionForm" action="" method="get">
                    @csrf
                    <x-table.table-pagination-with-selected/>
                    <x-table.table-wrapper
                        :thead="['#', __('financial.labels.invoice_number'), __('financial.terms.customer'), __('financial.labels.invoice_date'), __('financial.terms.amount_paid'), __('financial.labels.balance_due'), __('financial.labels.status'), __('financial.labels.actions')]">
                        @forelse ($payments as $payment)
                            @php
                                $sn = $loop->iteration;
                                $amount_paid = number_format($payment->amount_paid, 2);
                                $balance_due = number_format($payment->balance_due, 2);
                                $status = ($payment->balance_due == 0) ? __('financial.labels.paid') : __('financial.labels.pending');
                            @endphp
                            <tr>
                                <x-table.table-checkbox :sn="$sn" :item="$payment"/>
                                <td style="font-size: 12px;">{{ $sn }}</td>
                                <td style="font-size: 12px;"><strong>{{ $payment->invoice->invoice_number }}</strong></td>
                                <td style="font-size: 12px;">{{ $payment->customer->name }}</td>
                                <td style="font-size: 12px;">{{ $payment->invoice->invoice_date }}</td>
                                <td style="font-size: 12px;">₦{{ $amount_paid }}</td>
                                <td style="font-size: 12px;">₦{{ $balance_due }}</td>
                                <td style="font-size: 15px;">
                                    @if ($status == __('financial.labels.paid'))
                                        <span class="label label-success">
                                            {{ __('financial.labels.paid') }}
                                        </span>
                                    @else
                                        <span class="label label-warning">
                                            {{ __('financial.labels.pending') }}
                                        </span>
                                    @endif
                                </td>
                                <td style="font-size: 12px;">
                                    <div class="btn-group-actions">
                                        <a href="{{ route('customers.invoices.pdf', $payment->invoice->id) }}"
                                           class="btn btn-sm btn-primary"
                                           title="{{ __('financial.labels.download_pdf') }}"
                                           target="_blank">
                                            <i class="fa fa-file-pdf-o"></i> PDF
                                        </a>
                                        @if ($status != __('financial.labels.paid'))
                                            <button type="button" class="btn btn-sm btn-success"
                                                    data-payment-id="{{ $payment->id }}"
                                                    data-balance-due="{{ $payment->balance_due }}"
                                                    onclick="openPaymentModal(this)">
                                                <i class="fa fa-money"></i> {{ __('financial.labels.pay') }}
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <x-table.table-no-records/>
                                </td>
                            </tr>
                        @endforelse
                    </x-table.table-wrapper>
                    <x-table.table-pagination-with-selected/>
                </form>
            </div>
        </div>
    </div>

    <style>
        .btn-group-actions {
            display: flex;
            gap: 4px;
        }
    </style>

    <script>
        function openPaymentModal(button) {
            const paymentId = button.getAttribute('data-payment-id');
            const balanceDue = parseFloat(button.getAttribute('data-balance-due'));

            const existingModal = document.getElementById('paymentModal');
            if (existingModal) {
                existingModal.remove();
            }

            const paymentFormHtml = `
        <div id="paymentModal" class="modal">
            <div class="modal-header">
                <h4>${@json(__('financial.labels.pay_invoice'))} #${paymentId}</h4>
                <span class="close" onclick="document.getElementById('paymentModal').remove()">&times;</span>
            </div>
            <div class="modal-content">
                <div class="form-row">
                    <label for="balance_due">${@json(__('financial.labels.balance_due'))}</label>
                    <input type="text" id="balance_due" value="₦${balanceDue.toFixed(2)}" disabled>
                </div>
                <div class="form-row">
                    <label for="payment_amount">${@json(__('financial.labels.amount_to_pay'))}</label>
                    <input type="number" id="payment_amount" name="payment_amount" max="${balanceDue}" value="${balanceDue}" required>
                </div>
                <div class="form-row">
                    <label for="payment_mode">${@json(__('financial.labels.payment_mode'))}</label>
                    <select id="payment_mode" name="payment_mode" required>
                        <option value="">${@json(__('financial.labels.select_payment_mode'))}</option>
                        @foreach($payment_modes as $payment_mode)
            <option value="{{ $payment_mode->id }}">{{ $payment_mode->name }}</option>
                        @endforeach
            </select>
            <div id="paymentError" style="color: red;"></div>
        </div>
        <button type="button" class="btn btn-primary" onclick="submitPayment(${paymentId}, ${balanceDue})">{{ __('financial.labels.submit_payment') }}</button>
            </div>
        </div>
    `;

            document.body.insertAdjacentHTML('beforeend', paymentFormHtml);
        }


        function submitPayment(paymentId, balanceDue) {
            const paymentAmount = parseFloat(document.getElementById('payment_amount').value);
            const paymentModeId = document.getElementById('payment_mode').value;
            const paymentError = document.getElementById('paymentError'); // Get the error message div

            // Clear previous error messages
            paymentError.innerText = '';

            if (!paymentModeId) {
                paymentError.innerText = @json(__('financial.labels.invalid_payment_mode'));
                return;
            }

            if (paymentAmount > balanceDue) {
                paymentError.innerText = @json(__('financial.labels.payment_amount_exceeds_balance'));
                return;
            }

            // If validation passes, proceed with payment submission
            fetch(`/payments/${paymentId}/pay`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({
                    amount: paymentAmount,
                    payment_mode_id: paymentModeId,
                    balance_due: balanceDue
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Open the receipt in a new window
                        window.open(data.receipt_url, '_blank');

                        // Delay for 2 seconds to give time for the receipt to open, then reload the page
                        setTimeout(() => {
                            window.location.reload(); // Refresh the page to show updated status
                        }, 2000);
                    } else {
                        paymentError.innerText = @json(__('financial.labels.payment_failed_prefix')) + data.error; // Show error message in the div
                    }
                })
                .catch(error => {
                    console.error('Error processing payment:', error);
                    paymentError.innerText = @json(__('financial.labels.payment_processing_error')); // Show error in the div
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
