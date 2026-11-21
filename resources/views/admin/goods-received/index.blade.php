@extends('layouts.app')
@section('title', __('goods_received.title'))
@section('top-bar') @include('shared.header') @stop
@section('left-sidebar') @include('shared.sidebar') @stop
@section('content')
<section class="content po-page" @if ($is_rtl) dir="rtl" @endif><div class="container-fluid">
<nav class="po-breadcrumb" aria-label="{{ __('goods_received.breadcrumb') }}"><a href="{{ route('purchase-orders.index') }}">{{ __('goods_received.purchase_order') }}</a> <span>/</span> {{ __('goods_received.title') }}</nav>
<div class="po-page-heading"><div><p class="po-eyebrow">{{ __('goods_received.purchase_order') }}</p><h1>{{ __('goods_received.title') }}</h1></div><a class="btn btn-default" href="{{ route('purchase-orders.index') }}">{{ __('goods_received.back') }}</a></div>
<div class="po-card"><div class="table-responsive"><table class="table po-table"><thead><tr><th>{{ __('goods_received.receipt_number') }}</th><th>{{ __('goods_received.purchase_order') }}</th><th>{{ __('goods_received.supplier') }}</th><th>{{ __('goods_received.branch') }}</th><th>{{ __('goods_received.received_date') }}</th><th>{{ __('goods_received.status') }}</th><th>{{ __('goods_received.actions') }}</th></tr></thead><tbody>
@forelse($receipts as $receipt)<tr><td>{{ $receipt->receipt_number ?: __('goods_received.draft') . ' #' . $receipt->id }}</td><td>{{ optional($receipt->purchaseOrder)->purchase_order_number ?: '—' }}</td><td>{{ optional($receipt->vendor)->name ?: '—' }}</td><td>{{ optional($receipt->branch)->name ?: '—' }}</td><td>{{ optional($receipt->received_date)->format(config('settings.date_format', 'd/m/Y')) }}</td><td><span class="po-status po-status-{{ $receipt->status === 'confirmed' ? 'received' : 'draft' }}">{{ __('goods_received.' . $receipt->status) }}</span></td><td><a class="btn btn-default btn-sm" href="{{ route('goods-received.show', $receipt) }}">{{ __('goods_received.view') }}</a></td></tr>
@empty<tr><td colspan="7"><div class="po-empty"><strong>{{ __('goods_received.empty') }}</strong></div></td></tr>@endforelse
</tbody></table></div><div class="po-pagination">{{ $receipts->links() }}</div></div></div></section>
@include('admin.purchase-orders.styles')
@stop
