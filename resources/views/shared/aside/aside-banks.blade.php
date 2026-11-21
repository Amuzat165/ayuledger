<li @if (config('role_manage.BankCash.All')==0 and config('role_manage.BankCash.TrashShow')==0 and
  config('role_manage.BankCash.Create')==0) class="dis-none" @endif @if (Request::url()===route('bank_cash') or
  Request::url()===route('bank_cash.create') or Request::url()===route('bank_cash.trashed') or
  Request::url()===route('bank_cash.active.search') or Request::url()===route('bank_cash.trashed.search'))
  class="active " @endif>
  <a class="menu-toggle " href="javascript:void(0);">
    <i class="fas fa-university"></i>
    <span>{{ __('financial.screens.bank_accounts') }}</span>
  </a>
  <ul class="ml-menu">
    <li @if (Request::url()===route('bank_cash') or Request::url()===route('bank_cash.active.search')) class="active"
      @endif>
      <a @if (config('role_manage.BankCash.All')==0) class="dis-none" @endif href="{{ route('bank_cash') }}">{{
        __('financial.labels.all') }}</a>
    </li>
    <li @if (Request::url()===route('bank_cash.create')) class="active" @endif>
      <a @if (config('role_manage.BankCash.Create')==0) class="dis-none" @endif
        href="{{ route('bank_cash.create') }}">{{ __('financial.screens.create') }}</a>
    </li>
    <li @if (Request::url()===route('bank_cash.trashed') or Request::url()===route('bank_cash.trashed.search'))
      class="active" @endif>
      <a @if (config('role_manage.BankCash.TrashShow')==0) class="dis-none" @endif
        href="{{ route('bank_cash.trashed') }}">{{ __('financial.screens.trash') }}</a>
    </li>
  </ul>
</li>