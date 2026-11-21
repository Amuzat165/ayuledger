@extends('layouts.app')
@section('content')
<div class="container-fluid"><h1>{{ __('financial.labels.apply_customer_deposit') }}</h1>
    @if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="card"><div class="card-body"><p>{{ __('financial.labels.deposit_for_customer', ['reference' => 'AYU-DEP-' . str_pad((string) $deposit->id, 6, '0', STR_PAD_LEFT), 'customer' => $deposit->customer->name]) }}</p><p>{{ __('financial.labels.available_balance') }}: {{ $currency }}{{ number_format($balance, 2) }}</p>
        <form method="POST" action="{{ route('customer-deposits.apply', $deposit->id) }}">@csrf
            <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
            <div class="form-group"><label for="invoice_id">{{ __('financial.terms.invoice') }}</label><select class="form-control" name="invoice_id" id="invoice_id" required><option value="">{{ __('financial.labels.select_invoice') }}</option>@foreach($invoices as $invoice)<option value="{{ $invoice->id }}" {{ old('invoice_id') == $invoice->id ? 'selected' : '' }}>{{ $invoice->invoice_number }} — {{ $currency }}{{ number_format(app(\App\Services\CustomerInvoiceBalanceService::class)->balanceFor($invoice), 2) }}</option>@endforeach</select></div>
            <div class="form-group"><label for="amount">{{ __('financial.terms.total_amount') }}</label><input class="form-control" type="number" min="0.01" step="0.01" name="amount" id="amount" value="{{ old('amount') }}" required></div>
            <button class="btn btn-primary" type="submit">{{ __('financial.labels.apply_deposit') }}</button> <a class="btn btn-secondary" href="{{ route('customer-deposits.index') }}">{{ __('financial.labels.cancel') }}</a>
        </form>
    </div></div>
</div>
@endsection
