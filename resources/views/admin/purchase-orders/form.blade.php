@extends('layouts.app')

@section('title', $order->exists ? __('purchase_orders.edit_draft') : __('purchase_orders.create'))
@section('top-bar') @include('shared.header') @stop
@section('left-sidebar') @include('shared.sidebar') @stop

@section('content')
<section class="content po-page" @if ($is_rtl) dir="rtl" @endif>
    <div class="container-fluid">
        <nav class="po-breadcrumb" aria-label="{{ __('purchase_orders.breadcrumb') }}"><a href="{{ route('purchase-orders.index') }}">{{ __('purchase_orders.title') }}</a> <span>/</span> {{ $order->exists ? __('purchase_orders.edit_draft') : __('purchase_orders.create') }}</nav>
        <div class="po-page-heading"><div><p class="po-eyebrow">{{ __('purchase_orders.draft_only') }}</p><h1>{{ $order->exists ? __('purchase_orders.edit_draft') : __('purchase_orders.create') }}</h1><p>{{ __('purchase_orders.form_intro') }}</p></div><a href="{{ route('purchase-orders.index') }}" class="btn btn-default">{{ __('purchase_orders.back_to_list') }}</a></div>
        @if ($errors->any()) <div class="alert alert-danger" role="alert"><strong>{{ __('purchase_orders.review_errors') }}</strong><ul class="po-errors">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
        @if (session('success')) <div class="alert alert-success" role="status">{{ __(session('success')) }}</div> @endif
        <form method="POST" action="{{ $order->exists ? route('purchase-orders.update', $order) : route('purchase-orders.store') }}" id="purchase-order-form">
            @csrf @if($order->exists) @method('PUT') @else <input type="hidden" name="submission_token" value="{{ $submissionToken }}"> @endif
            <div class="po-card"><div class="po-card-heading"><div><h2>{{ __('purchase_orders.order_information') }}</h2><span>{{ __('purchase_orders.required_fields') }}</span></div><span class="po-status po-status-draft">{{ __('purchase_orders.draft') }}</span></div>
                <div class="po-form-grid">
                    <label>{{ __('purchase_orders.vendor') }} <em>*</em><select class="form-control" name="vendor_id" id="po-vendor" required><option value="">{{ __('purchase_orders.select_vendor') }}</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}" {{ old('vendor_id', $order->vendor_id) == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>@endforeach</select></label>
                    <label>{{ __('purchase_orders.branch') }} <em>*</em><select class="form-control" name="branch_id" required><option value="">{{ __('purchase_orders.select_branch') }}</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" {{ old('branch_id', $order->branch_id) == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>@endforeach</select></label>
                    <label>{{ __('purchase_orders.order_date') }} <em>*</em><input class="form-control" type="date" name="order_date" value="{{ old('order_date', $order->order_date ? $order->order_date->format('Y-m-d') : '') }}" required></label>
                    <label>{{ __('purchase_orders.expected_delivery') }}<input class="form-control" type="date" name="expected_delivery_date" value="{{ old('expected_delivery_date', $order->expected_delivery_date ? $order->expected_delivery_date->format('Y-m-d') : '') }}"></label>
                    <label>{{ __('purchase_orders.currency') }}<div class="po-currency"><strong>{{ $currencyCode }}</strong><span>{{ $currencySymbol }}</span></div><small>{{ __('purchase_orders.configured_currency_help') }}</small></label>
                </div>
                <div class="po-vendor-panel" id="po-vendor-details" aria-live="polite"><span>{{ __('purchase_orders.vendor_details_hint') }}</span></div>
            </div>
            <div class="po-card"><div class="po-card-heading"><div><h2>{{ __('purchase_orders.items_services') }}</h2><span>{{ __('purchase_orders.items_help') }}</span></div><button type="button" class="btn btn-default" id="po-add-line"><i class="fas fa-plus"></i> {{ __('purchase_orders.add_line') }}</button></div>
                <div class="table-responsive"><table class="table po-table po-lines-table"><thead><tr><th>{{ __('purchase_orders.inventory_item') }}</th><th>{{ __('purchase_orders.description') }}</th><th>{{ __('purchase_orders.unit') }}</th><th class="text-right">{{ __('purchase_orders.quantity') }}</th><th class="text-right">{{ __('purchase_orders.reference_price') }}</th><th class="text-right">{{ __('purchase_orders.agreed_price') }}</th><th class="text-right">{{ __('purchase_orders.line_total') }}</th><th><span class="sr-only">{{ __('purchase_orders.actions') }}</span></th></tr></thead><tbody id="po-lines">
                @php $formLines = old('items', $lines ?: [['inventory_item_id' => '', 'description' => '', 'unit' => '', 'quantity' => '1', 'reference_unit_price' => '', 'agreed_unit_price' => '']]); @endphp
                @foreach($formLines as $i => $line)
                    @php $inventoryId = is_array($line) ? ($line['inventory_item_id'] ?? '') : $line->inventory_item_id; $description = is_array($line) ? ($line['description'] ?? '') : $line->description; $unit = is_array($line) ? ($line['unit'] ?? '') : $line->unit; $quantity = is_array($line) ? ($line['quantity'] ?? '1') : $line->quantity; $referencePrice = is_array($line) ? ($line['reference_unit_price'] ?? '') : $line->reference_unit_price; $agreedPrice = is_array($line) ? ($line['agreed_unit_price'] ?? '') : $line->agreed_unit_price; @endphp
                    <tr class="po-line"><td><select class="form-control po-item-select ms" name="items[{{ $i }}][inventory_item_id]"><option value="">{{ __('purchase_orders.custom_service') }}</option>@foreach($inventoryItems as $item)<option value="{{ $item->id }}" {{ $inventoryId == $item->id ? 'selected' : '' }}>{{ trim($item->description . ' ' . ($item->sub_item ?: '')) }}</option>@endforeach</select></td><td><input class="form-control po-description" name="items[{{ $i }}][description]" value="{{ $description }}" maxlength="1000" aria-label="{{ __('purchase_orders.description') }}"></td><td><input class="form-control po-unit" name="items[{{ $i }}][unit]" value="{{ $unit }}" maxlength="64" aria-label="{{ __('purchase_orders.unit') }}"></td><td><input class="form-control po-quantity text-right" name="items[{{ $i }}][quantity]" value="{{ $quantity }}" inputmode="decimal" required aria-label="{{ __('purchase_orders.quantity') }}"></td><td><input class="form-control po-reference text-right" value="{{ $referencePrice }}" readonly aria-label="{{ __('purchase_orders.reference_price') }}"></td><td><input class="form-control po-price text-right" name="items[{{ $i }}][agreed_unit_price]" value="{{ $agreedPrice }}" inputmode="decimal" required aria-label="{{ __('purchase_orders.agreed_price') }}"></td><td class="text-right po-line-total">{{ $currencySymbol }} 0.00</td><td><button type="button" class="btn btn-link text-danger po-remove-line" aria-label="{{ __('purchase_orders.remove_line') }}"><i class="fas fa-times"></i></button></td></tr>
                @endforeach
                </tbody></table></div>
                <div class="po-totals"><div><span>{{ __('purchase_orders.subtotal') }}</span><strong id="po-subtotal">{{ $currencySymbol }} 0.00</strong></div><div class="po-grand-total"><span>{{ __('purchase_orders.order_total') }}</span><strong id="po-total">{{ $currencySymbol }} 0.00</strong></div><small>{{ __('purchase_orders.total_preview_notice') }}</small></div>
            </div>
            <div class="po-card"><div class="po-card-heading"><div><h2>{{ __('purchase_orders.delivery_notes') }}</h2><span>{{ __('purchase_orders.delivery_notes_help') }}</span></div></div><div class="po-form-grid po-notes-grid"><label>{{ __('purchase_orders.delivery_instructions') }}<textarea class="form-control" name="delivery_instructions" rows="3" maxlength="5000">{{ old('delivery_instructions', $order->delivery_instructions) }}</textarea></label><label>{{ __('purchase_orders.internal_notes') }}<textarea class="form-control" name="internal_notes" rows="3" maxlength="5000">{{ old('internal_notes', $order->internal_notes) }}</textarea></label></div></div>
            <div class="po-form-actions"><a href="{{ route('purchase-orders.index') }}" class="btn btn-default">{{ __('purchase_orders.cancel') }}</a><button type="submit" class="btn btn-primary po-primary"><i class="fas fa-save"></i> {{ __('purchase_orders.save_draft') }}</button></div>
        </form>
    </div>
</section>
@include('admin.purchase-orders.styles')
@push('include-js')
<script>
(function () {
    var form = document.getElementById('purchase-order-form');
    if (!form) return;
    var lines = document.getElementById('po-lines');
    var currency = @json($currencySymbol);
    var currencyPosition = @json($currencyPosition);
    var vendorUrl = @json(route('purchase-orders.vendors.show', ['id' => '__ID__']));
    var itemUrl = @json(route('purchase-orders.inventory-items.show', ['id' => '__ID__']));
    var messages = {vendor: @json(__('purchase_orders.vendor_details_loading')), error: @json(__('purchase_orders.details_unavailable')), item: @json(__('purchase_orders.select_inventory_item'))};
    function num(value) { var parsed = Number(String(value || '').replace(/,/g, '')); return Number.isFinite(parsed) ? parsed : 0; }
    function recalc() {
        var total = 0;
        lines.querySelectorAll('.po-line').forEach(function (row) {
            var amount = Math.round(num(row.querySelector('.po-quantity').value) * num(row.querySelector('.po-price').value) * 100) / 100;
            total += amount;
            var formatted = amount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            row.querySelector('.po-line-total').textContent = String(currencyPosition).toLowerCase() === 'suffix' ? formatted + ' ' + currency : currency + ' ' + formatted;
        });
        var formattedTotal = total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        var displayTotal = String(currencyPosition).toLowerCase() === 'suffix' ? formattedTotal + ' ' + currency : currency + ' ' + formattedTotal;
        document.getElementById('po-subtotal').textContent = displayTotal;
        document.getElementById('po-total').textContent = displayTotal;
    }
    function bindRow(row) {
        row.querySelectorAll('.po-quantity,.po-price').forEach(function (input) { input.addEventListener('input', recalc); });
        row.querySelector('.po-item-select').addEventListener('change', function () {
            var id = this.value;
            if (!id) { row.querySelector('.po-reference').value = ''; recalc(); return; }
            fetch(itemUrl.replace('__ID__', encodeURIComponent(id)), {headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}})
                .then(function (response) { if (!response.ok) throw new Error(); return response.json(); })
                .then(function (item) {
                    row.querySelector('.po-description').value = item.description || item.name || '';
                    row.querySelector('.po-unit').value = item.unit || '';
                    row.querySelector('.po-reference').value = item.reference_unit_price || '';
                    row.querySelector('.po-price').value = item.reference_unit_price || '';
                    recalc();
                }).catch(function () { alert(messages.error); });
        });
        row.querySelector('.po-remove-line').addEventListener('click', function () {
            if (lines.querySelectorAll('.po-line').length > 1) { row.remove(); renumber(); recalc(); }
        });
    }
    function renumber() {
        lines.querySelectorAll('.po-line').forEach(function (row, index) {
            row.querySelectorAll('[name]').forEach(function (field) { field.name = field.name.replace(/items\[\d+\]/, 'items[' + index + ']'); });
        });
    }
    lines.querySelectorAll('.po-line').forEach(bindRow);
    form.querySelector('#po-add-line').addEventListener('click', function () {
        var row = lines.querySelector('.po-line').cloneNode(true);
        row.querySelectorAll('input').forEach(function (field) { if (!field.readOnly) field.value = ''; else field.value = ''; });
        row.querySelector('select').selectedIndex = 0;
        lines.appendChild(row); renumber(); bindRow(row); recalc();
    });
    document.getElementById('po-vendor').addEventListener('change', function () {
        var panel = document.getElementById('po-vendor-details');
        if (!this.value) { panel.innerHTML = '<span>' + messages.vendor + '</span>'; return; }
        panel.innerHTML = '<span>' + messages.vendor + '</span>';
        fetch(vendorUrl.replace('__ID__', encodeURIComponent(this.value)), {headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}})
            .then(function (response) { if (!response.ok) throw new Error(); return response.json(); })
            .then(function (vendor) {
                panel.innerHTML = '';
                [[@json(__('purchase_orders.vendor')), vendor.name], [@json(__('purchase_orders.address')), vendor.address], [@json(__('purchase_orders.phone')), vendor.phone_number], [@json(__('purchase_orders.account')), vendor.account], [@json(__('purchase_orders.terms')), vendor.terms_of_payment], [@json(__('purchase_orders.vendor_branch')), vendor.branch]].forEach(function (pair) {
                    var cell = document.createElement('div'); var label = document.createElement('small'); var value = document.createElement('strong');
                    label.textContent = pair[0]; value.textContent = pair[1] || '—'; cell.appendChild(label); cell.appendChild(value); panel.appendChild(cell);
                });
            }).catch(function () { panel.innerHTML = '<span>' + messages.error + '</span>'; });
    });
    recalc();
    if (document.getElementById('po-vendor').value) document.getElementById('po-vendor').dispatchEvent(new Event('change'));
}());
</script>
@endpush
@stop
