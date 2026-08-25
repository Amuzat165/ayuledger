{{-- Vendors --}}
<li>
  <a class="menu-toggle " href="javascript:void(0);">
    <i class="fas fa-briefcase"></i>
    <span>{{ __('aside-lang.vendors.resource') }} </span>
  </a>
  <ul class="ml-menu">
    <li>
      <a href="{{ route('vendors.create') }}">{{
        __('aside-lang.vendors.create') }}</a>
    </li>
    <li>
      <a href="{{ route('vendors.index') }}">{{
        __('aside-lang.vendors.index.vendors') }}</a>
    </li>
      <li>
          <a href="{{ route('vouchers.create') }}">{{
        __('aside-lang.vendors.index.orders') }}</a>
      </li>
      <li>
          <a href="{{ route('vouchers.index') }}">{{
        __('aside-lang.vendors.index.view-purchase') }}</a>
      </li>
    <li>
        <a href="{{route('payments.create')}}">{{
        __('aside-lang.vendors.index.vouchers') }}</a>
    </li>
      <li>
          <a href="{{route('vendors.receipts.view')}}">{{
        __('aside-lang.vendors.index.view-receipt') }}</a>
      </li>
      <li>
          <a href="{{route('expenses.create')}}">{{
        __('aside-lang.vendors.index.expenses') }}</a>
      </li>
      <li>
          <a href="{{route('expenses.index')}}">{{
        __('aside-lang.vendors.index.expenses-view') }}</a>
      </li>
    <li>
      <a href="#">{{
        __('aside-lang.vendors.index.invocies') }}</a>
    </li>
  </ul>
</li>

{{-- /Vendors --}}
