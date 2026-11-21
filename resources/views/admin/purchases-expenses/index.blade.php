@extends('layouts.app')

@section('title', __('root.purchases_hub.title'))

@section('top-bar')
    @include('shared.header')
@stop

@section('left-sidebar')
    @include('shared.sidebar')
@stop

@section('content')
<section @if ($is_rtl) dir="rtl" @endif class="content purchases-hub">
    <div class="container-fluid">
        <div class="purchases-intro">
            <p class="purchases-eyebrow">{{ __('root.purchases_hub.eyebrow') }}</p>
            <h1>{{ __('root.purchases_hub.title') }}</h1>
            <p>{{ __('root.purchases_hub.description') }}</p>
        </div>

        <label class="purchases-search"><i class="fas fa-search"></i><input id="purchases-search" type="search" placeholder="{{ __('root.purchases_hub.search_placeholder') }}" aria-label="{{ __('Search purchases and expenses') }}"></label>

        @php
            $purchaseCategories = [
                ['title' => __('root.purchases_hub.vendors'), 'class' => 'vendors', 'items' => [
                    ['vendors.create', __('root.purchases_hub.add_vendor'), __('root.purchases_hub.add_vendor_desc'), 'fa-user-plus'],
                    ['vendors.index', __('root.purchases_hub.view_vendors'), __('root.purchases_hub.view_vendors_desc'), 'fa-briefcase'],
                ]],
                ['title' => __('root.purchases_hub.purchases_payments'), 'class' => 'purchases', 'items' => [
                    ['purchase-orders.create', __('root.purchases_hub.create_purchase_order'), __('root.purchases_hub.create_purchase_order_desc'), 'fa-file-signature'],
                    ['purchase-orders.index', __('purchase_orders.title'), __('purchase_orders.list_intro'), 'fa-clipboard-list'],
                    ['supplier-invoices.index', __('supplier_invoices.title'), __('supplier_invoices.list_intro'), 'fa-shopping-cart'],

                    ['supplier-invoices.index', __('root.purchases_hub.make_supplier_payment'), __('root.purchases_hub.make_supplier_payment_desc'), 'fa-money-check-alt'],
                    ['supplier-invoice-payments.index', __('root.purchases_hub.view_payments'), __('supplier_payments.history_intro'), 'fa-receipt'],
                ]],
                ['title' => __('root.purchases_hub.expenses'), 'class' => 'expenses', 'items' => [
                    ['expenses.create', __('root.purchases_hub.record_expense'), __('root.purchases_hub.record_expense_desc'), 'fa-plus-circle'],
                    ['expenses.index', __('root.purchases_hub.view_expenses'), __('root.purchases_hub.view_expenses_desc'), 'fa-list-alt'],
                ]],
            ];
        @endphp

        <div class="purchases-grid" id="purchases-grid">
            @foreach ($purchaseCategories as $category)
                <section class="purchase-category {{ $category['class'] }}">
                    <div class="purchase-heading"><h2>{{ $category['title'] }}</h2><span>{{ count($category['items']) }} {{ __('root.purchases_hub.links_count') }}</span></div>
                    <div class="purchase-cards">
                        @foreach ($category['items'] as $item)
                            <a class="purchase-card" href="{{ route($item[0]) }}" data-purchase-name="{{ strtolower($item[1] . ' ' . $item[2]) }}">
                                <span class="purchase-icon"><i class="fas {{ $item[3] }}"></i></span>
                                <span class="purchase-copy"><strong>{{ $item[1] }}</strong><small>{{ $item[2] }}</small></span>
                                <i class="fas fa-arrow-right purchase-arrow"></i>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
        <p class="purchases-empty" id="purchases-empty">{{ __('root.purchases_hub.search_no_match') }}</p>
    </div>
</section>
@stop

@push('include-css')
<style>
    .purchases-hub { background: #f6f9fa; min-height: calc(100vh - 66px); }
    .purchases-intro { margin: 8px 0 25px; }
    .purchases-eyebrow { color: #0f766e; font-size: 11px; font-weight: 700; letter-spacing: 1.2px; margin: 0 0 8px; text-transform: uppercase; }
    .purchases-intro h1 { color: #183b56; font-size: 30px; font-weight: 600; margin: 0 0 8px; }
    .purchases-intro p:last-child { color: #6b7c87; font-size: 14px; margin: 0; }
    .purchases-search { background: #fff; border: 1px solid #d9e3e8; border-radius: 5px; display: block; max-width: 560px; padding: 0 15px; }
    .purchases-search i { color: #0f766e; }
    .purchases-search input { border: 0; color: #183b56; height: 46px; outline: 0; padding: 0 12px; width: calc(100% - 30px); }
    .purchases-grid { display: grid; gap: 25px; grid-template-columns: repeat(2, minmax(0, 1fr)); margin-top: 30px; }
    .purchase-category { min-width: 0; }
    .purchase-category.expenses { grid-column: 1 / -1; }
    .purchase-heading { align-items: baseline; border-bottom: 1px solid #d9e3e8; display: flex; justify-content: space-between; margin-bottom: 12px; padding-bottom: 9px; }
    .purchase-heading h2 { color: #183b56; font-size: 15px; font-weight: 600; margin: 0; }
    .purchase-heading span { color: #91a0a7; font-size: 11px; }
    .purchase-cards { display: grid; gap: 10px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .purchase-card { align-items: center; background: #fff; border: 1px solid #d9e3e8; border-radius: 5px; display: flex; min-height: 92px; padding: 14px; text-decoration: none !important; transition: border-color .15s ease, transform .15s ease; }
    .purchase-card:hover { border-color: #0f766e; transform: translateY(-2px); }
    .purchase-icon { align-items: center; background: #e4f2f0; border-radius: 4px; color: #0f766e; display: flex; flex: 0 0 34px; height: 34px; justify-content: center; }
    .purchase-copy { display: flex; flex: 1; flex-direction: column; margin: 0 10px; min-width: 0; }
    .purchase-copy strong { color: #183b56; font-size: 12px; font-weight: 600; }
    .purchase-copy small { color: #7b8b94; font-size: 11px; line-height: 1.35; margin-top: 5px; }
    .purchase-arrow { color: #9aabb2; font-size: 11px; }
    .purchases-empty { color: #7b8b94; display: none; margin-top: 35px; text-align: center; }
    @media (max-width: 900px) { .purchases-grid { grid-template-columns: 1fr; } .purchase-category.expenses { grid-column: auto; } }
    @media (max-width: 540px) { .purchase-cards { grid-template-columns: 1fr; } .purchases-intro h1 { font-size: 25px; } }
</style>
@endpush

@push('include-js')
<script>
    (function () {
        var search = document.getElementById('purchases-search');
        var empty = document.getElementById('purchases-empty');
        if (!search) return;
        search.addEventListener('input', function () {
            var term = search.value.toLowerCase().trim();
            var visible = 0;
            document.querySelectorAll('.purchase-category').forEach(function (category) {
                var cards = category.querySelectorAll('.purchase-card');
                var categoryVisible = 0;
                cards.forEach(function (card) {
                    var match = card.dataset.purchaseName.indexOf(term) !== -1;
                    card.style.display = match ? 'flex' : 'none';
                    categoryVisible += match ? 1 : 0;
                });
                category.style.display = categoryVisible ? '' : 'none';
                visible += categoryVisible;
            });
            empty.style.display = visible ? 'none' : 'block';
        });
    }());
</script>
@endpush
