@extends('layouts.app')
@section('title', __('supplier_payments.make_payment'))
@section('top-bar') @include('shared.header') @stop
@section('left-sidebar') @include('shared.sidebar') @stop
@section('content')
<section class="content po-page" @if ($is_rtl) dir="rtl" @endif><div class="container-fluid">
    <nav class="po-breadcrumb" aria-label="{{ __('supplier_invoices.breadcrumb') }}"><a href="{{ route('supplier-invoices.show', $invoice) }}">{{ $invoice->internal_invoice_number }}</a> <span>/</span>{{ __('supplier_payments.make_payment') }}</nav>
    <div class="po-page-heading"><div><p class="po-eyebrow">{{ __('supplier_payments.make_payment') }}</p><h1>{{ $invoice->internal_invoice_number }}</h1><p>{{ $invoice->vendor_name_snapshot }} · {{ $invoice->supplier_invoice_number }}</p></div></div>
    @if($errors->any())<div class="alert alert-danger" role="alert">{{ __('supplier_payments.validation_help') }}<ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="po-summary-grid"><div class="po-summary-card"><small>{{ __('supplier_invoices.purchase_order') }}</small><strong>{{ optional($invoice->purchaseOrder)->purchase_order_number }}</strong></div><div class="po-summary-card"><small>{{ __('supplier_invoices.currency') }}</small><strong>{{ $invoice->currency_code }}</strong></div><div class="po-summary-card"><small>{{ __('supplier_invoices.amount_remaining') }}</small><strong>{{ \App\Support\PurchaseOrderCalculator::formatCurrency($invoice->amount_remaining, $invoice->currency_symbol, $invoice->currency_position) }}</strong></div></div>
    <form method="POST" action="{{ route('supplier-invoice-payments.store', $invoice->id) }}" class="po-card">@csrf
        <input type="hidden" name="submission_token" value="{{ $submissionToken }}">
        <div class="row"><div class="col-md-4 form-group"><label for="payment_amount">{{ __('supplier_payments.payment_amount') }} *</label><div class="form-line"><input id="payment_amount" name="payment_amount" type="number" class="form-control" inputmode="decimal" min="0.01" step="0.01" required value="{{ old('payment_amount') }}" max="{{ $invoice->amount_remaining }}"></div></div><div class="col-md-4 form-group"><label for="payment_date">{{ __('supplier_payments.payment_date') }} *</label><input id="payment_date" name="payment_date" type="date" class="form-control" required value="{{ old('payment_date', now()->toDateString()) }}"></div><div class="col-md-4 form-group"><label for="payment_mode_id">{{ __('supplier_payments.payment_method') }} *</label><select id="payment_mode_id" name="payment_mode_id" class="form-control" required><option value="">{{ __('supplier_payments.select_method') }}</option>@foreach($paymentModes as $mode)<option value="{{ $mode->id }}" {{ (string) old('payment_mode_id') === (string) $mode->id ? 'selected' : '' }}>{{ $mode->name }}</option>@endforeach</select></div></div>
        <div class="po-heading-actions"><button type="submit" class="btn btn-primary po-primary">{{ __('supplier_payments.make_payment') }}</button><a class="btn btn-default" href="{{ route('supplier-invoices.show', $invoice) }}">{{ __('supplier_invoices.cancel') }}</a></div>
    </form>
</div></section>
@include('admin.purchase-orders.styles')
@stop
