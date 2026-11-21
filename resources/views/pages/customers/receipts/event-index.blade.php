@extends('layouts.table-layout')

@section('title', __('financial.screens.receipts'))

@section('container')
<div class="row"><div class="col-xs-12"><div class="card">
    <h3>{{ __('financial.screens.payment_receipts') }}</h3>
    <p><a class="btn btn-sm btn-primary" href="{{ route('customer-deposits.index') }}">{{ __('financial.screens.view_customer_deposits') }}</a></p>
    <div class="table-responsive"><table class="table table-bordered">
        <thead><tr><th>#</th><th>{{ __('financial.terms.receipt') }}</th><th>{{ __('financial.labels.date') }}</th><th>{{ __('financial.terms.customer') }}</th><th>{{ __('financial.terms.invoice') }}</th><th>{{ __('financial.terms.amount_paid') }}</th><th>{{ __('financial.labels.payment_method') }}</th><th>{{ __('financial.labels.balance_remaining') }}</th><th>PDF</th></tr></thead>
        <tbody>
        @forelse($receipts as $receipt)
            @php($allocation = $receipt->allocations->first())
            @if($allocation)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $receipt->receipt_number }}</td>
                <td>{{ $receipt->received_at->format('Y-m-d H:i') }}</td>
                <td>{{ optional($receipt->customer)->name }}</td>
                <td>{{ optional($allocation->invoice)->invoice_number }}</td>
                <td>{{ $currency }}{{ number_format($receipt->amount_received, 2) }}</td>
                <td>{{ $receipt->payment_mode_name ?: __('financial.labels.not_available') }}</td>
                <td>{{ $currency }}{{ number_format($allocation->balance_after, 2) }}</td>
                <td><a class="btn btn-sm btn-primary" target="_blank" href="{{ route('customer.payment_receipts.pdf', $receipt->id) }}">PDF</a></td>
            </tr>
            @endif
        @empty
            <tr><td colspan="9">{{ __('financial.labels.no_new_payment_receipts') }}</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <h4>{{ __('financial.screens.legacy_payment_summaries') }}</h4>
    <p>{{ __('financial.screens.legacy_payment_summaries_description') }}</p>
    <div class="table-responsive"><table class="table table-bordered">
        <thead><tr><th>{{ __('financial.terms.invoice') }}</th><th>{{ __('financial.terms.customer') }}</th><th>{{ __('financial.labels.recorded_amount_paid') }}</th><th>{{ __('financial.labels.recorded_balance') }}</th><th>{{ __('financial.labels.payment_method') }}</th></tr></thead>
        <tbody>
        @forelse($legacyPayments as $legacy)
            <tr>
                <td>{{ optional($legacy->invoice)->invoice_number }}</td>
                <td>{{ optional($legacy->customer)->name }}</td>
                <td>{{ $currency }}{{ number_format($legacy->amount_paid, 2) }}</td>
                <td>{{ $currency }}{{ number_format($legacy->balance_due, 2) }}</td>
                <td>{{ optional($legacy->paymentMode)->name ?: __('financial.labels.not_available') }}</td>
            </tr>
        @empty
            <tr><td colspan="5">{{ __('financial.screens.no_legacy_payment_summaries') }}</td></tr>
        @endforelse
        </tbody>
    </table></div>
</div></div></div>
@endsection
