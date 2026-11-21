@extends('layouts.app')
@section('content')
<div class="container-fluid"><h1>{{ __('financial.labels.customer_deposits') }}</h1>
    <p><a class="btn btn-sm btn-default" href="{{ route('customers.receipts.view') }}">{{ __('financial.labels.back_to_receipts') }}</a></p>
    @if(session('success'))<div class="alert alert-success">{{ __(session('success')) }}</div>@endif
    <div class="table-responsive"><table class="table table-bordered table-striped">
        <thead><tr><th>{{ __('financial.labels.deposit') }}</th><th>{{ __('financial.terms.customer') }}</th><th>{{ __('financial.labels.source_receipt') }}</th><th>{{ __('financial.labels.source_invoice') }}</th><th>{{ __('financial.labels.original_amount') }}</th><th>{{ __('financial.labels.available_balance') }}</th><th>{{ __('financial.labels.applications_history') }}</th><th>{{ __('financial.labels.actions') }}</th></tr></thead><tbody>
        @forelse($deposits as $deposit)<tr>
            <td>AYU-DEP-{{ str_pad((string) $deposit->id, 6, '0', STR_PAD_LEFT) }}</td><td>{{ optional($deposit->customer)->name }}</td>
            <td>{{ optional($deposit->sourceReceipt)->receipt_number }}</td><td>{{ optional($deposit->sourceInvoice)->invoice_number ?: '—' }}</td>
            <td>{{ $currency }}{{ number_format($deposit->original_amount, 2) }}</td><td>{{ $currency }}{{ number_format($deposit->available_balance, 2) }}</td>
            <td>@forelse($deposit->applications as $application)<div>{{ $currency }}{{ number_format($application->amount_applied, 2) }} → {{ optional($application->invoice)->invoice_number }}; {{ __('financial.labels.invoice_balance_after') }} {{ $currency }}{{ number_format($application->invoice_balance_after, 2) }}; {{ __('financial.labels.deposit_remaining') }} {{ $currency }}{{ number_format($application->deposit_balance_after, 2) }}; {{ $application->applied_at->format('Y-m-d H:i') }}; {{ optional($application->operator)->name ?: '—' }}</div>@empty {{ __('financial.labels.none') }} @endforelse</td>
            <td>@if((float) $deposit->available_balance > 0)<a class="btn btn-sm btn-primary" href="{{ route('customer-deposits.apply.form', $deposit->id) }}">{{ __('financial.labels.apply_deposit') }}</a>@endif</td>
        </tr>@empty<tr><td colspan="8">{{ __('financial.labels.no_customer_deposits') }}</td></tr>@endforelse
        </tbody></table></div>
</div>
@endsection
