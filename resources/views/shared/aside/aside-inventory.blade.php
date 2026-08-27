{{-- Inventory & Assets --}}
@php
    $menu_items = [
        ['inventories.create', __('aside-lang.inventory.create')],
        ['inventories.index', __('aside-lang.inventory.index')],
        ['reports.accounts.fixed_asset_schedule', __('aside-lang.assets.schedule')],
    ];
@endphp

@foreach ($menu_items as $item)
    <li @class(['active' => $Bh::activeMenuItem([$item[0]])])>
        <a href="{{ route($item[0]) }}" style="white-space:nowrap;">{{ $item[1] }}</a>
    </li>
@endforeach

