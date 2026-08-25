@extends('layouts.table-layout')

@section('title', $title)

@section('container')
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="row">
        <div class="col-xs-12">
            <div class="card">
                <x-table.table-search-bar :all="count($payments)" :trash="0" />
                <form class="actionForm" action="" method="get">
                    @csrf
                    <x-table.table-pagination-with-selected />
                    <x-table.table-wrapper :thead="['#', 'Invoice Number', 'Vendor', 'Invoice Date', 'Amount Paid', 'Balance Due', 'Status']">
                        @forelse ($payments as $payment)
                            @php
                                $sn = $loop->iteration;
                                $amount_paid = number_format($payment->amount_paid, 2);
                                $balance_due = number_format($payment->balance_due, 2);
                                $status = ($payment->balance_due == 0) ? 'Paid' : 'Pending';
                            @endphp
                            <tr>
                                <x-table.table-checkbox :sn="$sn" :item="$payment" />
                                <td style="font-size: 12px;">{{ $sn }}</td>
                                <td style="font-size: 12px;">{{ $payment->invoice->invoice_number }}</td>
                                <td style="font-size: 12px;">{{ $payment->vendor->name }}</td>
                                <td style="font-size: 12px;">{{ $payment->invoice->invoice_date }}</td>
                                <td style="font-size: 12px;">₦{{ $amount_paid }}</td>
                                <td style="font-size: 12px;">₦{{ $balance_due }}</td>
                                <td style="font-size: 15px;">
                                    @if ($status == 'Paid')
                                        <span class="label label-success">
                                            Paid
                                        </span>
                                    @else
                                        <button type="button" class="btn btn-primary"
                                                data-payment-id="{{ $payment->id }}"
                                                data-balance-due="{{ $payment->balance_due }}"
                                                onclick="openPaymentModal(this)">
                                            Pay
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <x-table.table-no-records />
                                </td>
                            </tr>
                        @endforelse
                    </x-table.table-wrapper>
                    <x-table.table-pagination-with-selected />
                </form>
            </div>
        </div>
    </div>

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
                <h4>Pay Invoice #${paymentId}</h4>
                <span class="close" onclick="document.getElementById('paymentModal').remove()">&times;</span>
            </div>
            <div class="modal-content">
                <div class="form-row">
                    <label for="balance_due">Balance Due</label>
                    <input type="text" id="balance_due" value="₦${balanceDue.toFixed(2)}" disabled>
                </div>
                <div class="form-row">
                    <label for="payment_amount">Amount to Pay</label>
                    <input type="number" id="payment_amount" name="payment_amount" max="${balanceDue}" value="${balanceDue}" required>
                </div>
                <div class="form-row">
                    <label for="payment_mode">Payment Mode</label>
                    <select id="payment_mode" name="payment_mode" required>
                        <option value="">Select Payment Mode</option>
                        @foreach($payment_modes as $payment_mode)
            <option value="{{ $payment_mode->id }}">{{ $payment_mode->name }}</option>
                        @endforeach
            </select>
            <div id="paymentError" style="color: red;"></div>
        </div>
        <button type="button" class="btn btn-primary" onclick="submitPayment(${paymentId}, ${balanceDue})">Submit Payment</button>
            </div>
        </div>
    `;

            document.body.insertAdjacentHTML('beforeend', paymentFormHtml);
        }

        function submitPayment(paymentId, balanceDue) {
            const paymentAmount = parseFloat(document.getElementById('payment_amount').value);
            const paymentModeId = document.getElementById('payment_mode').value;
            const paymentError = document.getElementById('paymentError');

            paymentError.innerText = '';

            if (!paymentModeId) {
                paymentError.innerText = 'Please select a valid payment mode.';
                return;
            }

            if (paymentAmount > balanceDue) {
                paymentError.innerText = 'The payment amount cannot exceed the balance due.';
                return;
            }
            fetch(`/vendors/${paymentId}/pay`, {
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
                        window.open(data.receipt_url, '_blank');
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        paymentError.innerText = 'Payment failed: ' + data.error;
                    }
                })
                .catch(error => {
                    console.error('Error processing payment:', error);
                    paymentError.innerText = 'An error occurred while processing the payment.';
                });
        }

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
        .close {
            font-size: 2em;
            cursor: pointer;
            background-color: #f44;
            color: white;
            border-radius: 10%;
            width: 30px;
            height: 30px;
            text-align: center;
        }
        .modal-content {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding: 20px;
        }
        .form-row {
            flex-basis: calc(50% - 20px);
            margin-bottom: 10px;
        }
        .modal-content input,
        .modal-content select,
        .modal-content button {
            width: 100%;
            padding: 10px;
        }
        `;
        document.head.appendChild(style);
    </script>
@endsection
