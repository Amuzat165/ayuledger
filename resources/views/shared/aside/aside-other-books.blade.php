@php
    $payrollProps = (object)[
        'menu' => __('aside-lang.payrolls.resource'),
        'menu_items' => [
            ['payrolls.create', 'Create'],
            ['payrolls.index', 'View']
        ],
        'is_active' => $Bh::activeMenuItem(['payrolls.*']),
    ];

    $budgetProps = (object)[
        'menu' => __('aside-lang.budget.resource'),
        'menu_items' => [
            ['budgets.index', 'Budget']
        ],
        'is_active' => $Bh::activeMenuItem(['budget.*']),
    ];
@endphp

{{-- Other Books --}}
<li @class(['active' => Request::is('other_books*')])>
    <a class="menu-toggle" href="javascript:void(0);">
        <i class="fas fa-book"></i>
        <span>{{ __('Other Books') }}</span>
    </a>
    <ul class="ml-menu">
        {{-- Payroll --}}
        <li @class(['active' => $payrollProps->is_active])>
            <a class="menu-toggle" href="javascript:void(0);">
{{--                <i class="fas fa-file-invoice-dollar"></i>--}}
                <span>{{ $payrollProps->menu }} </span>
            </a>
            <ul class="ml-menu">
                @foreach ($payrollProps->menu_items as $item)
                    <li @class(['active' => $Bh::activeMenuItem($item[0])])>
                        <a href="{{ route($item[0]) }}" style="white-space:nowrap;">{{ __($item[1]) }}</a>
                    </li>
                @endforeach
            </ul>
        </li>
        {{-- /Payroll --}}

        {{-- Budget --}}
        <li @class(['active' => $budgetProps->is_active])>
            <a class="menu-toggle" href="javascript:void(0);">
{{--                <i class="fas fa-piggy-bank"></i>--}}
                <span>{{ $budgetProps->menu }} </span>
            </a>
            <ul class="ml-menu">
                @foreach ($budgetProps->menu_items as $item)
                    <li @class(['active' => $Bh::activeMenuItem($item[0])])>
                        <a href="{{ route($item[0]) }}" style="white-space:nowrap;">{{ __($item[1]) }}</a>
                    </li>
                @endforeach
            </ul>
        </li>
        {{-- /Budget --}}
        @php
            $props = (object)[
                'menu' => __('aside-lang.general-journal.resource'),
                'menu_items' => [
                    ['general-journal.create', 'Create'],
                    ['general-journal.index', 'View']
                ],
                'is_active' => $Bh::activeMenuItem(['general-journal.*']),
            ];
        @endphp

        {{-- General Journal --}}
        <li @class(['active'=> $props->is_active])>
            <a class="menu-toggle" href="javascript:void(0);">
{{--                <i class="fas fa-journal-whills"></i>--}}
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
        {{-- /General Journal --}}
        <li @if (config('role_manage.CrVoucher.All')==0 and config('role_manage.CrVoucher.TrashShow')==0 and
  config('role_manage.CrVoucher.Create')==0 and config('role_manage.DrVoucher.All')==0 and
  config('role_manage.DrVoucher.TrashShow')==0 and config('role_manage.DrVoucher.Create')==0 and
  config('role_manage.JnlVoucher.All')==0 and config('role_manage.JnlVoucher.TrashShow')==0 and
  config('role_manage.JnlVoucher.Create')==0 and config('role_manage.ContraVoucher.All')==0 and
  config('role_manage.ContraVoucher.TrashShow')==0 and config('role_manage.ContraVoucher.Create')==0) class="dis-none"
            @endif @if (Request::url()===route('dr_voucher') or Request::url()===route('dr_voucher.create') or
  Request::url()===route('dr_voucher.trashed') or Request::url()===route('dr_voucher.active.search') or
  Request::url()===route('dr_voucher.trashed.search') or Request::url()===route('cr_voucher') or
  Request::url()===route('cr_voucher.create') or Request::url()===route('cr_voucher.trashed') or
  Request::url()===route('cr_voucher.active.search') or Request::url()===route('cr_voucher.trashed.search') or
  Request::url()===route('jnl_voucher') or Request::url()===route('jnl_voucher.create') or
  Request::url()===route('jnl_voucher.trashed') or Request::url()===route('jnl_voucher.active.search') or
  Request::url()===route('jnl_voucher.trashed.search') or Request::url()===route('contra_voucher') or
  Request::url()===route('contra_voucher.create') or Request::url()===route('contra_voucher.trashed') or
  Request::url()===route('contra_voucher.active.search') or Request::url()===route('contra_voucher.trashed.search'))
                class="active " @endif>
            <a class="menu-toggle" href="javascript:void(0);">
                {{-- <i class="material-icons">account_balance_wallet</i> --}}
{{--                <i class="fas fa-ticket-alt"></i>--}}
                <span>{{ __('root.menu.voucher') }}</span>
            </a>

            <ul class="ml-menu">

                {{-- Cr Voucher Start --}}

                <li @if (config('role_manage.CrVoucher.All')==0 and config('role_manage.CrVoucher.TrashShow')==0 and
      config('role_manage.CrVoucher.Create')==0) class="dis-none" @endif @if (Request::url()===route('cr_voucher') or
      Request::url()===route('cr_voucher.create') or Request::url()===route('cr_voucher.trashed') or
      Request::url()===route('cr_voucher.active.search') or Request::url()===route('cr_voucher.trashed.search'))
                    class="active " @endif>
                    <a class="menu-toggle " href="javascript:void(0);">
                        <i class="fas fa-arrow-right"></i>
                        <span>{{ __('root.menu.voucher_credit') }}</span>
                    </a>

                    <ul class="ml-menu">
                        <li @if (Request::url()===route('cr_voucher') or Request::url()===route('cr_voucher.active.search'))
                                class="active" @endif>
                            <a @if (config('role_manage.CrVoucher.All')==0) class="dis-none" @endif href="{{ route('cr_voucher') }}">{{
            __('root.menu.voucher_all') }}</a>
                        </li>
                        <li @if (Request::url()===route('cr_voucher.create')) class="active" @endif>
                            <a @if (config('role_manage.CrVoucher.Create')==0) class="dis-none" @endif
                            href="{{ route('cr_voucher.create') }}">{{ __('root.menu.voucher_create') }}</a>
                        </li>
                        <li @if (Request::url()===route('cr_voucher.trashed') or Request::url()===route('cr_voucher.trashed.search'))
                                class="active" @endif>
                            <a @if (config('role_manage.CrVoucher.TrashShow')==0) class="dis-none" @endif
                            href="{{ route('cr_voucher.trashed') }}">{{ __('root.menu.voucher_trashed')
            }}</a>
                        </li>
                    </ul>
                </li>

                {{-- cr Voucher End --}}

                {{-- Dr Voucher Start --}}

                <li @if (config('role_manage.DrVoucher.All')==0 and config('role_manage.DrVoucher.TrashShow')==0 and
      config('role_manage.DrVoucher.Create')==0) class="dis-none" @endif @if (Request::url()===route('dr_voucher') or
      Request::url()===route('dr_voucher.create') or Request::url()===route('dr_voucher.trashed') or
      Request::url()===route('dr_voucher.active.search') or Request::url()===route('dr_voucher.trashed.search'))
                    class="active " @endif>
                    <a class="menu-toggle " href="javascript:void(0);">
                        <i class="fas fa-arrow-left"></i>
                        <span>{{ __('root.menu.debit') }}</span>
                    </a>

                    <ul class="ml-menu">
                        <li @if (Request::url()===route('dr_voucher') or Request::url()===route('dr_voucher.active.search'))
                                class="active" @endif>
                            <a @if (config('role_manage.DrVoucher.All')==0) class="dis-none" @endif href="{{ route('dr_voucher') }}">{{
            __('root.menu.debit_all') }}</a>
                        </li>
                        <li @if (Request::url()===route('dr_voucher.create')) class="active" @endif>
                            <a @if (config('role_manage.DrVoucher.Create')==0) class="dis-none" @endif
                            href="{{ route('dr_voucher.create') }}">{{ __('root.menu.debit_create') }}</a>
                        </li>
                        <li @if (Request::url()===route('dr_voucher.trashed') or Request::url()===route('dr_voucher.trashed.search'))
                                class="active" @endif>
                            <a @if (config('role_manage.DrVoucher.TrashShow')==0) class="dis-none" @endif
                            href="{{ route('dr_voucher.trashed') }}">{{ __('root.menu.debit_trashed') }}</a>
                        </li>
                    </ul>
                </li>

                {{-- Dr Voucher End --}}


                {{-- Jnl Voucher Start --}}

                <li @if (config('role_manage.JnlVoucher.All')==0 and config('role_manage.JnlVoucher.TrashShow')==0 and
      config('role_manage.JnlVoucher.Create')==0) class="dis-none" @endif @if (Request::url()===route('jnl_voucher') or
      Request::url()===route('jnl_voucher.create') or Request::url()===route('jnl_voucher.trashed') or
      Request::url()===route('jnl_voucher.active.search') or Request::url()===route('jnl_voucher.trashed.search'))
                    class="active " @endif>
                    <a class="menu-toggle " href="javascript:void(0);">
                        <i class="fas fa-arrows-alt-h"></i>
                        <span>{{ __('root.menu.journal') }}</span>
                    </a>

                    <ul class="ml-menu">
                        <li @if (Request::url()===route('jnl_voucher') or Request::url()===route('jnl_voucher.active.search'))
                                class="active" @endif>
                            <a @if (config('role_manage.JnlVoucher.All')==0) class="dis-none" @endif href="{{ route('jnl_voucher') }}">{{
            __('root.menu.journal_all') }}</a>
                        </li>
                        <li @if (Request::url()===route('jnl_voucher.create')) class="active" @endif>
                            <a @if (config('role_manage.JnlVoucher.Create')==0) class="dis-none" @endif
                            href="{{ route('jnl_voucher.create') }}">{{ __('root.menu.journal_create')
            }}</a>
                        </li>
                        <li @if (Request::url()===route('jnl_voucher.trashed') or Request::url()===route('jnl_voucher.trashed.search'))
                                class="active" @endif>
                            <a @if (config('role_manage.JnlVoucher.TrashShow')==0) class="dis-none" @endif
                            href="{{ route('jnl_voucher.trashed') }}">{{ __('root.menu.journal_trashed')
            }}</a>
                        </li>
                    </ul>
                </li>

                {{-- Jnl Voucher End --}}

                {{-- contra_voucher Start --}}

                <li @if (config('role_manage.ContraVoucher.All')==0 and config('role_manage.ContraVoucher.TrashShow')==0 and
      config('role_manage.ContraVoucher.Create')==0) class="dis-none" @endif
                @if      (Request::url()===route('contra_voucher') or Request::url()===route('contra_voucher.create') or
                Request::url()===route('contra_voucher.trashed') or Request::url()===route('contra_voucher.active.search') or
                Request::url()===route('contra_voucher.trashed.search')) class="active " @endif>
                    <a class="menu-toggle " href="javascript:void(0);">
                        <i class="fas fa-arrows-alt-h"></i>
                        <span>{{ __('root.menu.contra') }}</span>
                    </a>

                    <ul class="ml-menu">
                        <li @if (Request::url()===route('contra_voucher') or Request::url()===route('contra_voucher.active.search'))
                                class="active" @endif>
                            <a @if (config('role_manage.ContraVoucher.All')==0) class="dis-none" @endif
                            href="{{ route('contra_voucher') }}">{{ __('root.menu.contra_all') }}</a>
                        </li>
                        <li @if (Request::url()===route('contra_voucher.create')) class="active" @endif>
                            <a @if (config('role_manage.ContraVoucher.Create')==0) class="dis-none" @endif
                            href="{{ route('contra_voucher.create') }}">{{ __('root.menu.contra_create')
            }}</a>
                        </li>
                        <li @if (Request::url()===route('contra_voucher.trashed') or
          Request::url()===route('contra_voucher.trashed.search')) class="active" @endif>
                            <a @if (config('role_manage.ContraVoucher.TrashShow')==0) class="dis-none" @endif
                            href="{{ route('contra_voucher.trashed') }}">{{ __('root.menu.contra_trashed')
            }}</a>
                        </li>
                    </ul>
                </li>

                {{-- contra_voucher End --}}

            </ul>
        </li>
    </ul>
</li>

{{-- /Other Books --}}
