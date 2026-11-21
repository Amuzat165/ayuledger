@extends('layouts.app')
@section('title', __('supplier_payments.history_title'))
@section('top-bar') @include('shared.header') @stop
@section('left-sidebar') @include('shared.sidebar') @stop
@section('content')
<section class="content po-page" @if ($is_rtl) dir="rtl" @endif><div class="container-fluid">
    <nav class="po-breadcrumb" aria-label="{{ __('supplier_invoices.breadcrumb') }}"><a href="{{ route('supplier-invoices.index') }}">{{ __('supplier_invoices.title') }}</a> <span>/</span> {{ __('supplier_payments.history_title') }}</nav>
    <div class="po-page-heading"><div><p class="po-eyebrow">{{ __('purchase_orders.purchasing') }}</p><h1>{{ __('supplier_payments.history_title') }}</h1><p>{{ __('supplier_payments.history_intro') }}</p></div></div>
    <div class="po-card po-filter-card">
        <form method="GET" action="{{ route('supplier-invoice-payments.index') }}" class="po-filters">
            <label>{{ __('supplier_payments.supplier') }}<input class="form-control" type="search" name="supplier" value="{{ $filters['supplier'] ?? '' }}"></label>
            <label>{{ __('supplier_invoices.supplier_invoice_number') }}<input class="form-control" type="search" name="supplier_invoice_number" value="{{ $filters['supplier_invoice_number'] ?? '' }}"></label>
            <label>{{ __('supplier_payments.internal_invoice_number') }}<input class="form-control" type="search" name="system_invoice_number" value="{{ $filters['system_invoice_number'] ?? '' }}"></label>
            <label>{{ __('supplier_payments.payment_reference') }}<input class="form-control" type="search" name="payment_reference" value="{{ $filters['payment_reference'] ?? '' }}"></label>
            <label>{{ __('supplier_payments.date_from') }}<input class="form-control" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></label>
            <label>{{ __('supplier_payments.date_to') }}<input class="form-control" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></label>
            <label>{{ __('supplier_payments.payment_method') }}<input class="form-control" type="search" name="payment_method" value="{{ $filters['payment_method'] ?? '' }}"></label>
            <label>{{ __('supplier_invoices.branch') }}<input class="form-control" type="search" name="branch" value="{{ $filters['branch'] ?? '' }}"></label>
            <div class="po-filter-actions"><button class="btn btn-primary" type="submit">{{ __('supplier_payments.apply_filters') }}</button><a class="btn btn-default" href="{{ route('supplier-invoice-payments.index') }}">{{ __('supplier_payments.reset_filters') }}</a></div>
        </form>
    </div>
    <div class="po-card"><div class="po-card-heading"><div><h2>{{ __('supplier_payments.history_title') }}</h2><span>{{ $payments->total() }} {{ __('supplier_invoices.records') }}</span></div></div>
        <div class="table-responsive"><table class="table po-table"><thead><tr>
            <th>{{ __('supplier_payments.payment_reference') }}</th><th>{{ __('supplier_payments.supplier') }}</th><th>{{ __('supplier_invoices.supplier_invoice_number') }}</th><th>{{ __('supplier_payments.internal_invoice_number') }}</th><th>{{ __('supplier_invoices.purchase_order') }}</th><th>{{ __('supplier_payments.payment_date') }}</th><th class="text-right">{{ __('supplier_payments.payment_amount') }}</th><th>{{ __('supplier_payments.payment_method') }}</th><th>{{ __('supplier_payments.recorded_by') }}</th><th>{{ __('supplier_invoices.actions') }}</th>
        </tr></thead><tbody>
        @forelse($payments as $payment)
            @php $invoice = $payment->supplierInvoice; @endphp
            <tr>
                <td><strong class="po-number">{{ $payment->payment_reference }}</strong></td>
                <td>{{ $invoice->vendor_name_snapshot ?: optional($invoice->vendor)->name ?: __('supplier_payments.not_available') }}</td>
                <td>{{ $invoice->supplier_invoice_number }}</td>
                <td>{{ $invoice->internal_invoice_number }}</td>
                <td>{{ optional($invoice->purchaseOrder)->purchase_order_number ?: __('supplier_payments.not_available') }}</td>
                <td>{{ optional($payment->payment_date)->format(config('settings.date_format', 'd/m/Y')) }}</td>
                <td class="text-right po-money">{{ \App\Support\PurchaseOrderCalculator::formatCurrency($payment->payment_amount, $payment->currency_symbol, $payment->currency_position) }}</td>
                <td>{{ $payment->payment_mode_name ?: __('supplier_payments.not_available') }}</td>
                <td>{{ optional($payment->creator)->name ?: __('supplier_payments.not_available') }}</td>
                <td class="po-actions">
                    @if(config('role_manage.SupplierPayment.Pdf'))<a class="btn btn-default btn-sm" href="{{ route('supplier-invoice-payments.receipt', $payment->id) }}" target="_blank" rel="noopener noreferrer">{{ __('supplier_payments.payment_receipt') }}</a>@endif
                    @if(config('role_manage.SupplierInvoice.Show'))<a class="btn btn-default btn-sm" href="{{ route('supplier-invoices.show', $invoice->id) }}">{{ __('supplier_payments.view_supplier_invoice') }}</a>@endif
                    @if(!config('role_manage.SupplierPayment.Pdf') && !config('role_manage.SupplierInvoice.Show')){{ __('supplier_payments.not_available') }}@endif
                </td>
            </tr>
        @empty
            <tr><td colspan="10"><div class="po-empty"><strong>{{ collect($filters)->filter(function ($value) { return $value !== null && $value !== ''; })->isEmpty() ? __('supplier_payments.no_history') : __('supplier_payments.no_matching_payments') }}</strong></div></td></tr>
        @endforelse
        </tbody></table></div><div class="po-pagination">{{ $payments->links() }}</div>
    </div>
</div></section>
@include('admin.purchase-orders.styles')
@stop