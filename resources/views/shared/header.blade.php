<style>
    .accounting-header { background: #183b56; min-height: 66px; box-shadow: 0 1px 0 rgba(24,59,86,.15); }
    .accounting-header .container-fluid { display: flex; align-items: center; justify-content: space-between; min-height: 66px; }
    .accounting-header .navbar-header { display: flex; align-items: center; }
    .accounting-header .navbar-brand { color: #fff; font-size: 17px; font-weight: 600; margin-left: 14px; }
    .accounting-header .header-tools { align-items: center; display: flex; gap: 14px; }
    .accounting-header .global-search { background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.22); border-radius: 4px; color: #fff; height: 36px; padding: 0 12px 0 34px; width: 220px; }
    .accounting-header .global-search::placeholder { color: rgba(255,255,255,.72); }
    .accounting-header .search-wrap { position: relative; }
    .accounting-header .search-wrap i { color: rgba(255,255,255,.72); left: 12px; position: absolute; top: 10px; }
    .accounting-header .quick-create { background: #f0b44d; border: 0; border-radius: 4px; color: #183b56; font-size: 12px; font-weight: 600; padding: 10px 13px; }
    .accounting-header .notification { color: #fff; font-size: 16px; padding: 7px; }
    .accounting-header .breadcrumbs { color: rgba(255,255,255,.7); font-size: 11px; margin-left: 14px; }
    .accounting-header .dropdown-menu { min-width: 190px; }
    /* The global .dropdown-menu rule lifts menus into the navbar; keep the language list below its selector. */
    .accounting-header .header-tools .bootstrap-select > .dropdown-menu { margin-top: 0 !important; }
    .accounting-header .rtl-supported-navbar[dir="rtl"] .header-tools .bootstrap-select > .dropdown-menu {
        right: 0;
        left: auto;
        text-align: right;
    }
    @media (max-width: 700px) { .accounting-header .global-search { width: 120px; } .accounting-header .breadcrumbs { display: none; } .accounting-header .quick-create { font-size: 0; } .accounting-header .quick-create i { font-size: 13px; } }
</style>

<nav class="navbar accounting-header">
    <div class="container-fluid">
        <div @if($is_rtl || app()->getLocale() === 'ar') dir="rtl" @endif class="rtl-supported-navbar">
            <div class="navbar-header">
                <a href="javascript:void(0);" class="bars"></a>
                <a class="navbar-brand" href="{{ route('dashboard') }}" title="{{ __('root.common.home') }}">
                    <?php echo Config::get('settings.company_name'); ?>
                </a>
                <span class="breadcrumbs">/ {{ ucfirst(str_replace('-', ' ', request()->segment(1) ?: 'dashboard')) }}</span>
            </div>
            <div class="header-tools">
                <form action="{{ route('locale.update') }}" method="POST" aria-label="{{ __('header.language_selection') }}">
                    @csrf
                    <label class="sr-only" for="app-locale">{{ __('header.language_selection') }}</label>
                    <select id="app-locale" name="locale" class="form-control input-sm" onchange="this.form.submit()">
                        @foreach (\App\Support\LocaleManager::supportedLocales() as $localeCode => $localeName)
                        <option value="{{ $localeCode }}" @if (app()->getLocale() === $localeCode) selected @endif>{{ $localeName }}</option>
                        @endforeach
                    </select>
                </form>
                <div class="search-wrap"><i class="fas fa-search"></i><input class="global-search" type="search" placeholder="{{ trim(__('root.common.search')) }}" aria-label="{{ trim(__('root.common.search')) }}"></div>
                <div class="dropdown">
                    <button class="quick-create dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-plus"></i> {{ __('header.quick_create') }}</button>
                    <ul class="dropdown-menu dropdown-menu-right">
                        <li><a href="{{ route('customers.invoices.create') }}">{{ __('header.create_invoice') }}</a></li>
                        <li><a href="{{ route('customers.receipts.create') }}">{{ __('header.receive_payment') }}</a></li>
                        <li><a href="{{ route('expenses.create') }}">{{ __('header.record_expense') }}</a></li>
                        <li><a href="{{ route('jnl_voucher.create') }}">{{ __('header.journal_entry') }}</a></li>
                        <li><a href="{{ route('customers.create') }}">{{ __('header.add_customer') }}</a></li>
                        <li><a href="{{ route('vendors.create') }}">{{ __('header.add_vendor') }}</a></li>
                    </ul>
                </div>
                <a class="notification" href="javascript:void(0);" title="{{ __('header.notifications') }}" aria-label="{{ __('header.notifications') }}"><i class="fas fa-bell"></i></a>
                <a href="javascript:void(0);" class="dropdown-toggle text-white" data-toggle="dropdown" role="button"
                    aria-haspopup="true" aria-expanded="true">
                    {{-- <i class="material-icons">more_vert</i> --}}
                    <figure>
                        <img src="/avatar-flat.png" alt="{{ __('header.options') }}" width="32" style="border-radius:100%" title="{{ __('header.account') }}" />
                    </figure>
                </a>
                <ul class="dropdown-menu @if(!$is_rtl) pull-right @endif">
                    <li><a href="{{ route('profile') }}">
                            {{-- <i class="material-icons">person</i> --}}
                            <i class="fas fa-user"></i> &nbsp;
                            {{ __('header.profile') }}</a>
                    </li>
                    <li role="separator" class="divider"></li>
                    <li><a href="javascript:void(0); document.getElementById('logout-form').submit();"><i
                                class="fas fa-sign-out-alt"></i> &nbsp; {{ __('header.sign_out') }}</a></li>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="dis-none">
                        @csrf
                    </form>
                </ul>
            </div>
        </div>

        {{-- <div class="collapse navbar-collapse" id="navbar-collapse">
            <ul class="nav navbar-nav navbar-right">
                <!-- #END# Tasks -->
                <div class=" height-28 btn-group user-helper-dropdown m-t-18 text-white bor-1 pointer-cursor p-both-0">

                    <i class="material-icons" data-toggle="dropdown" aria-haspopup="true"
                        aria-expanded="true">keyboard_arrow_down</i>
                    <ul class="dropdown-menu">
                        <li><a href="{{ route('profile') }}"><i class="material-icons">person</i>Profile</a></li>
                        <li role="separator" class="divider"></li>
                        <li><a href="javascript:void(0); document.getElementById('logout-form').submit();"><i
                                    class=" fas fa-sign-out-alt"></i> Sign Out</a></li>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="dis-none">
                            @csrf
                        </form>
                    </ul>
                </div>
            </ul>
        </div> --}}

    </div>
</nav>