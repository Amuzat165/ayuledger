@extends('layouts.app')

@section('title', __('purchase_orders.title'))

@section('top-bar') @include('shared.header') @stop
@section('left-sidebar') @include('shared.sidebar') @stop

@section('content')
<section class="content po-page" @if ($is_rtl) dir="rtl" @endif>
    <div class="container-fluid">
        <nav class="po-breadcrumb" aria-label="{{ __('purchase_orders.breadcrumb') }}">{{ __('purchase_orders.purchases') }} <span>/</span> {{ __('purchase_orders.title') }}</nav>
        <div class="po-page-heading">
            <div><p class="po-eyebrow">{{ __('purchase_orders.purchasing') }}</p><h1>{{ __('purchase_orders.title') }}</h1><p>{{ __('purchase_orders.list_intro') }}</p></div>
            <div class="po-heading-actions">@if(config('role_manage.SupplierInvoice.Show'))<a class="btn btn-default" href="{{ route('supplier-invoices.index') }}">{{ __('supplier_invoices.title') }}</a>@endif <a class="btn btn-primary po-primary" href="{{ route('purchase-orders.create') }}"><i class="fas fa-plus"></i> {{ __('purchase_orders.create') }}</a></div>
        </div>
        @if (session('success')) <div class="alert alert-success" role="status">{{ __(session('success')) }}</div> @endif
        <div class="po-card po-filter-card">
            <form method="GET" action="{{ route('purchase-orders.index') }}" class="po-filters">
                <label>{{ __('purchase_orders.search') }}<input class="form-control" type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('purchase_orders.search_placeholder') }}"></label>
                <label>{{ __('purchase_orders.vendor') }}<select class="form-control" name="vendor_id"><option value="">{{ __('purchase_orders.all_vendors') }}</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}" {{ request('vendor_id') == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>@endforeach</select></label>
                <label>{{ __('purchase_orders.branch') }}<select class="form-control" name="branch_id"><option value="">{{ __('purchase_orders.all_branches') }}</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>@endforeach</select></label>
                <label>{{ __('purchase_orders.status') }}<select class="form-control" name="status"><option value="">{{ __('purchase_orders.all_statuses') }}</option><option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>{{ __('purchase_orders.draft') }}</option><option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>{{ __('purchase_orders.approved') }}</option><option value="partially_received" {{ request('status') === 'partially_received' ? 'selected' : '' }}>{{ __('purchase_orders.partially_received') }}</option><option value="received" {{ request('status') === 'received' ? 'selected' : '' }}>{{ __('purchase_orders.received') }}</option><option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>{{ __('purchase_orders.cancelled') }}</option></select></label>
                <label>{{ __('purchase_orders.from') }}<input class="form-control" type="date" name="from" value="{{ request('from') }}"></label>
                <label>{{ __('purchase_orders.to') }}<input class="form-control" type="date" name="to" value="{{ request('to') }}"></label>
                <div class="po-filter-actions"><button class="btn btn-primary" type="submit">{{ __('purchase_orders.apply_filters') }}</button><a class="btn btn-default" href="{{ route('purchase-orders.index') }}">{{ __('purchase_orders.clear') }}</a></div>
            </form>
        </div>
        <div class="po-card">
            <div class="po-card-heading"><div><h2>{{ __('purchase_orders.list_heading') }}</h2><span>{{ $orders->total() }} {{ __('purchase_orders.records') }}</span></div></div>
            <div class="table-responsive"><table class="table po-table"><thead><tr><th>{{ __('purchase_orders.number') }}</th><th>{{ __('purchase_orders.vendor') }}</th><th>{{ __('purchase_orders.branch') }}</th><th>{{ __('purchase_orders.order_date') }}</th><th>{{ __('purchase_orders.expected_delivery') }}</th><th class="text-right">{{ __('purchase_orders.order_total') }}</th><th>{{ __('purchase_orders.status') }}</th><th>{{ __('purchase_orders.actions') }}</th></tr></thead><tbody>
            @forelse($orders as $order)
                <tr><td><a class="po-number" href="{{ route('purchase-orders.show', $order) }}">{{ $order->purchase_order_number }}</a></td><td>{{ $order->vendor_name_snapshot }}</td><td>{{ $order->branch_name_snapshot }}</td><td>{{ optional($order->order_date)->format(config('settings.date_format', 'd/m/Y')) }}</td><td>{{ $order->expected_delivery_date ? $order->expected_delivery_date->format(config('settings.date_format', 'd/m/Y')) : '—' }}</td><td class="text-right po-money">{{ \App\Support\PurchaseOrderCalculator::formatCurrency($order->order_total, $order->currency_symbol, $order->currency_position) }}</td><td><span class="po-status po-status-{{ $order->status }}">{{ __('purchase_orders.' . $order->status) }}</span></td><td class="po-actions"><a class="btn btn-default btn-sm" href="{{ route('purchase-orders.show', $order) }}">{{ __('purchase_orders.view') }}</a>@if($order->status === 'draft') <a class="btn btn-default btn-sm" href="{{ route('purchase-orders.edit', $order) }}">{{ __('purchase_orders.edit') }}</a>@endif</td></tr>
            @empty
                <tr><td colspan="8"><div class="po-empty"><span class="po-empty-icon"><i class="fas fa-file-signature"></i></span><strong>{{ __('purchase_orders.empty_title') }}</strong><span>{{ __('purchase_orders.empty_description') }}</span><a href="{{ route('purchase-orders.create') }}" class="btn btn-primary">{{ __('purchase_orders.create_first') }}</a></div></td></tr>
            @endforelse
            </tbody></table></div>
            <div class="po-pagination">{{ $orders->links() }}</div>
        </div>
    </div>
</section>
@include('admin.purchase-orders.styles')
@stop
