<li @if ( config('role_manage.LedgerType.All')==0 and config('role_manage.LedgerType.TrashShow')==0 and
  config('role_manage.LedgerType.Create')==0 and config('role_manage.LedgerGroup.All')==0 and
  config('role_manage.LedgerGroup.TrashShow')==0 and config('role_manage.LedgerGroup.Create')==0 and
  config('role_manage.LedgerName.All')==0 and config('role_manage.LedgerName.TrashShow')==0 and
  config('role_manage.LedgerName.Create')==0 ) class="dis-none" @endif @if (
  Request::url()===route('income_expense_type') or Request::url()===route('income_expense_type.create') or
  Request::url()===route('income_expense_type.trashed') or Request::url()===route('income_expense_type.active.search')
  or Request::url()===route('income_expense_type.trashed.search') or Request::url()===route('income_expense_group') or
  Request::url()===route('income_expense_group.create') or Request::url()===route('income_expense_group.trashed') or
  Request::url()===route('income_expense_group.active.search') or
  Request::url()===route('income_expense_group.trashed.search') or Request::url()===route('income_expense_head') or
  Request::url()===route('income_expense_head.create') or Request::url()===route('income_expense_head.trashed') or
  Request::url()===route('income_expense_head.active.search') or
  Request::url()===route('income_expense_head.trashed.search') ) class="active" @endif>
  <a class="menu-toggle" href="javascript:void(0);">
    <i class="fas fa-file-invoice-dollar"></i>
    <span>{{ __('financial.terms.chart_of_accounts') }} </span>
  </a>
  <ul class="ml-menu">
    {{-- Ledger Type Start --}}
    <li @if (config('role_manage.LedgerType.All')==0 and config('role_manage.LedgerType.TrashShow')==0 and
      config('role_manage.LedgerType.Create')==0) class="dis-none" @endif
      @if      (Request::url()===route('income_expense_type') or Request::url()===route('income_expense_type.create') or
      Request::url()===route('income_expense_type.trashed') or
      Request::url()===route('income_expense_type.active.search') or
      Request::url()===route('income_expense_type.trashed.search')) class="active " @endif>
      <a class="menu-toggle " href="javascript:void(0);">
        <span>{{ __('financial.screens.account_type') }} </span>
      </a>
      <ul class="ml-menu">
        <li @if (Request::url()===route('income_expense_type') or
          Request::url()===route('income_expense_type.active.search')) class="active" @endif>
          <a @if (config('role_manage.LedgerType.All')==0) class="dis-none" @endif
            href="{{ route('income_expense_type') }}">{{ __('financial.labels.all') }}</a>
        </li>
      </ul>
    </li>

    {{-- Income Expense Type End --}}

    {{-- Ledger Group Start --}}
    <li @if (config('role_manage.LedgerGroup.All')==0 and config('role_manage.LedgerGroup.TrashShow')==0 and
      config('role_manage.LedgerGroup.Create')==0) class="dis-none" @endif
       @if      (Request::url()===route('income_expense_group') or Request::url()===route('income_expense_group.create') or
      Request::url()===route('income_expense_group.trashed') or
      Request::url()===route('income_expense_group.active.search') or
      Request::url()===route('income_expense_group.trashed.search')) class="active " @endif>
      <a class="menu-toggle " href="javascript:void(0);">
        <span>{{ __('financial.screens.account_group') }} </span>
      </a>
      <ul class="ml-menu">
        <li @if (Request::url()===route('income_expense_group') or
          Request::url()===route('income_expense_group.active.search')) class="active" @endif>
          <a @if (config('role_manage.LedgerGroup.All')==0) class="dis-none" @endif
            href="{{ route('income_expense_group') }}">{{ __('financial.labels.all') }}</a>
        </li>
        <li @if (Request::url()===route('income_expense_group.create')) class="active" @endif>
          <a @if (config('role_manage.LedgerGroup.Create')==0) class="dis-none" @endif
            href="{{ route('income_expense_group.create') }}">{{
            __('financial.screens.create') }}</a>
        </li>
        <li @if (Request::url()===route('income_expense_group.trashed') or
          Request::url()===route('income_expense_group.trashed.search')) class="active" @endif>
          <a @if (config('role_manage.LedgerGroup.TrashShow')==0) class="dis-none" @endif
            href="{{ route('income_expense_group.trashed') }}">{{
            __('financial.screens.trash') }}</a>
        </li>
      </ul>
    </li>

    {{-- Ledger Group End --}}

    {{-- Ledger Name Start --}}
    <li @if (config('role_manage.LedgerName.All')==0 and config('role_manage.LedgerName.TrashShow')==0 and
      config('role_manage.LedgerName.Create')==0) class="dis-none" @endif
      @if      (Request::url()===route('income_expense_head') or Request::url()===route('income_expense_head.create') or
      Request::url()===route('income_expense_head.trashed') or
      Request::url()===route('income_expense_head.active.search') or
      Request::url()===route('income_expense_head.trashed.search')) class="active " @endif>
      <a class="menu-toggle " href="javascript:void(0);">
        <span>{{ __('financial.screens.account_name') }} </span>
      </a>
      <ul class="ml-menu">
        <li @if (Request::url()===route('income_expense_head') or
          Request::url()===route('income_expense_head.active.search')) class="active" @endif>
          <a @if (config('role_manage.LedgerName.All')==0) class="dis-none" @endif
            href="{{ route('income_expense_head') }}">{{ __('financial.labels.all') }}</a>
        </li>
        <li @if (Request::url()===route('income_expense_head.create')) class="active" @endif>
          <a @if (config('role_manage.LedgerName.Create')==0) class="dis-none" @endif
            href="{{ route('income_expense_head.create') }}">{{ __('financial.screens.create')
            }}</a>
        </li>
        <li @if (Request::url()===route('income_expense_head.trashed') or
          Request::url()===route('income_expense_head.trashed.search')) class="active" @endif>
          <a @if (config('role_manage.LedgerName.TrashShow')==0) class="dis-none" @endif
            href="{{ route('income_expense_head.trashed') }}">{{
            __('financial.screens.trash') }}</a>
        </li>
      </ul>
    </li>
    {{-- Ledger Name End --}}
      <li @if (config('role_manage.BankCash.All')==0 and config('role_manage.BankCash.TrashShow')==0 and
  config('role_manage.BankCash.Create')==0) class="dis-none" @endif @if (Request::url()===route('bank_cash') or
  Request::url()===route('bank_cash.create') or Request::url()===route('bank_cash.trashed') or
  Request::url()===route('bank_cash.active.search') or Request::url()===route('bank_cash.trashed.search'))
          class="active " @endif>
          <a class="menu-toggle " href="javascript:void(0);">
{{--              <i class="fas fa-university"></i>--}}
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
  </ul>
</li>
