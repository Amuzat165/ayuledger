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
                <x-table.table-search-bar :all="count($payments)" :trash="0" />
                <form class="actionForm" action="" method="get">
                    @csrf
                    <x-table.table-pagination-with-selected />
                    <x-table.table-wrapper :thead="['#', __('financial.labels.invoice_number'), __('financial.terms.vendor'), __('financial.labels.invoice_date'), __('financial.labels.invoice_total'), __('financial.terms.wht'), __('financial.labels.net_amount_payable'), __('financial.terms.amount_paid'), __('financial.labels.balance_due'), __('financial.labels.status')]">
                        @forelse ($payments as $payment)
                            @php
                                $sn = $loop->iteration;
                                $amount_paid = number_format($payment->amount_paid, 2);
                                $summary = $payment->settlement_summary;
                                $status = !$summary['settlement_available'] ? __('financial.labels.review_required') : ($summary['balance_due'] === '0.00' ? __('financial.labels.paid') : __('financial.labels.pending'));
                            @endphp
                            <tr>
                                <x-table.table-checkbox :sn="$sn" :item="$payment" />
                                <td style="font-size: 12px;">{{ $sn }}</td>
                                <td style="font-size: 12px;">{{ $payment->invoice->invoice_number }}</td>
                                <td style="font-size: 12px;">{{ $payment->vendor->name }}</td>
                                <td style="font-size: 12px;">{{ $payment->invoice->invoice_date }}</td>
                                <td style="font-size: 12px;">{{ $currency_symbol }}{{ number_format((float) $summary['invoice_total'], 2) }}</td>
                                <td style="font-size: 12px;">{{ $summary['settlement_available'] ? $currency_symbol . number_format((float) $summary['wht_amount'], 2) : __('financial.labels.review_required') }}</td>
                                <td style="font-size: 12px;">{{ $summary['settlement_available'] ? $currency_symbol . number_format((float) $summary['net_payable'], 2) : __('financial.labels.review_required') }}</td>
                                <td style="font-size: 12px;">{{ $currency_symbol }}{{ $amount_paid }}</td>
                                <td style="font-size: 12px;">{{ $summary['settlement_available'] ? $currency_symbol . number_format((float) $summary['balance_due'], 2) : __('financial.labels.review_required') }}</td>
                                <td style="font-size: 15px;">
                                    @if ($status == __('financial.labels.review_required'))
                                        <span title="{{ $summary['review_message'] }}">{{ __('financial.labels.review_required') }}</span>
                                    @elseif ($status == __('financial.labels.paid'))
                                        <span class="label label-success">
                                            {{ __('financial.labels.paid') }}
                                        </span>
                                    @else
                                        <button type="button" class="btn btn-primary"
                                                data-payment-id="{{ $payment->id }}"
                                                data-invoice-total="{{ $summary['invoice_total'] }}"
                                                data-wht="{{ $summary['wht_amount'] }}"
                                                data-net-payable="{{ $summary['net_payable'] }}"
                                                data-amount-paid="{{ $summary['amount_paid'] }}"
                                                data-balance-due="{{ $summary['balance_due'] }}"
                                                onclick="openPaymentModal(this)">
                                            {{ __('financial.labels.pay') }}
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10">
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
            const invoiceTotal = button.getAttribute('data-invoice-total');
            const wht = button.getAttribute('data-wht');
            const netPayable = button.getAttribute('data-net-payable');
            const amountPaid = button.getAttribute('data-amount-paid');
            const balanceDue = button.getAttribute('data-balance-due');

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
                    <label>${@json(__('financial.labels.invoice_total'))}</label><input type="text" value="${formatMoney(invoiceTotal)}" disabled>
                    <label>${@json(__('financial.terms.wht'))}</label><input type="text" value="${formatMoney(wht)}" disabled>
                    <label>${@json(__('financial.labels.net_amount_payable'))}</label><input type="text" value="${formatMoney(netPayable)}" disabled>
                    <label>${@json(__('financial.labels.amount_paid_cumulative'))}</label><input type="text" value="${formatMoney(amountPaid)}" disabled>
                    <label for="balance_due">${@json(__('financial.labels.balance_due'))}</label><input type="text" id="balance_due" value="${formatMoney(balanceDue)}" disabled>
                </div>
                <div class="form-row">
                    <label for="payment_amount">${@json(__('financial.labels.amount_to_pay'))}</label>
                    <input type="number" id="payment_amount" name="payment_amount" min="0.01" step="0.01" max="${balanceDue}" value="${balanceDue}" required>
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
        <button type="button" class="btn btn-primary" onclick="submitPayment(${paymentId})">{{ __('financial.labels.submit_payment') }}</button>
            </div>
        </div>
    `;

            document.body.insertAdjacentHTML('beforeend', paymentFormHtml);
        }

        const currencySymbol = @json($currency_symbol);
        const formatMoney = value => `${currencySymbol}${Number(value).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

        function submitPayment(paymentId) {
            const paymentAmount = document.getElementById('payment_amount').value;
            const paymentModeId = document.getElementById('payment_mode').value;
            const paymentError = document.getElementById('paymentError');

            paymentError.innerText = '';

            if (!paymentModeId) {
                paymentError.innerText = @json(__('financial.labels.invalid_payment_mode'));
                return;
            }

            fetch(`/vendors/${paymentId}/pay`, {
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
                        window.open(data.receipt_url, '_blank');
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        paymentError.innerText = @json(__('financial.labels.payment_failed_prefix')) + (data.message || data.error || Object.values(data.errors || {}).flat().join(' ') || @json(__('financial.labels.please_review_payment'))) ;
                    }
                })
                .catch(error => {
                    console.error('Error processing payment:', error);
                    paymentError.innerText = @json(__('financial.labels.payment_processing_error'));
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
