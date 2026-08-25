@php
$props = (object)[
'menu' => __('aside-lang.customers.resource'),
'menu_items' => [
['customers.create','aside-lang.customers.create.customer'],
['customers.index','aside-lang.customers.index'],
['customers.invoices.create','aside-lang.customers.create.invoice'],
['customers.invoices.index','aside-lang.customers.view'],
['customers.receipts.create','aside-lang.customers.create.receipt'],
['customers.receipts.view','aside-lang.customers.show']
],
'is_active' => $Bh::activeMenuItem(['customers.*']),
];
@endphp

{{-- Customers --}}
<li @class(['active'=> $props->is_active])>
  <a class="menu-toggle" href="javascript:void(0);">
    <i class="fas fa-users"></i>
    <span>{{ $props->menu }} </span>
  </a>
  <ul class="ml-menu">
    @foreach ($props->menu_items as $item)
    <li @class(['active'=> $Bh::activeMenuItem($item[0])])>
      <a href="{{ route($item[0]) }}" style="white-space:nowrap;">{{ __($item[1]) }}</a>
    </li>
    @endforeach
  </ul>
</li>

{{-- /Customers --}}
