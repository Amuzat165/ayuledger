@inject('Bh', 'App\Helpers\BladeHelper')

<style>
    :root { --accounting-ink: #183b56; --accounting-teal: #0f766e; --accounting-line: #d9e3e8; --accounting-muted: #6b7c87; }
    .accounting-sidebar { background: #f8fbfc; border-right: 1px solid var(--accounting-line); box-shadow: none; width: 210px; }
    .accounting-sidebar .sidebar-brand { padding: 22px 20px 18px; border-bottom: 1px solid var(--accounting-line); }
    .accounting-sidebar .sidebar-brand strong { color: var(--accounting-ink); font-size: 18px; }
    .accounting-sidebar .sidebar-brand small { display: block; color: var(--accounting-muted); font-size: 11px; margin-top: 4px; }
    .accounting-sidebar .module-label { padding: 18px 20px 7px; color: var(--accounting-muted); font-size: 10px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; }
    
    /* Main menu items - pure white text and icons */
    .accounting-sidebar .list > li > a { 
        color: white !important; 
        padding: 12px 20px; 
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.8) !important;
    }
    .accounting-sidebar .list > li > a i { 
        color: white !important; 
        width: 24px; 
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.8) !important;
    }
    .accounting-sidebar .list > li > a span { 
        color: white !important; 
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.8) !important;
    }
    
    /* Ensure white on visited and all pseudo-states */
    .accounting-sidebar .list > li > a:visited,
    .accounting-sidebar .list > li > a:hover,
    .accounting-sidebar .list > li > a:focus { 
        color: white !important; 
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.8) !important;
    }
    .accounting-sidebar .list > li > a:visited i,
    .accounting-sidebar .list > li > a:hover i,
    .accounting-sidebar .list > li > a:focus i { 
        color: white !important; 
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.8) !important;
    }
    
    /* Active menu items - black text */
    .accounting-sidebar .list > li.active > a { 
        background: #e4f2f0 !important; 
        color: black !important; 
        font-weight: 600; 
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3) !important; 
    }
    .accounting-sidebar .list > li.active > a i { 
        color: black !important; 
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3) !important; 
    }
    .accounting-sidebar .list > li.active > a span { 
        color: black !important; 
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3) !important; 
    }
    
    .accounting-sidebar, .accounting-sidebar span { filter: none !important; opacity: 1 !important; }
    .accounting-sidebar .list > li > a, .accounting-sidebar .ml-menu li > a { -webkit-font-smoothing: antialiased; transform: none !important; }
    .accounting-sidebar .list > li.active > .ml-menu,
    .accounting-sidebar .inventory-assets-menu { display: block; }
    .accounting-sidebar .inventory-assets-menu { list-style: none; margin: 0; padding: 0; }
    .accounting-sidebar .inventory-assets-menu > li { display: list-item !important; height: auto !important; visibility: visible !important; }
    .accounting-sidebar .inventory-assets-menu > li > a { display: flex !important; height: auto !important; visibility: visible !important; color: #526873 !important; }
    .accounting-sidebar .ml-menu { background: transparent; }
    .accounting-sidebar .ml-menu li a { color: #526873; padding: 8px 18px 8px 53px; font-size: 13px; text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5); }
    .accounting-sidebar .ml-menu li.active > a { color: var(--accounting-teal); font-weight: 600; text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5); }
    .accounting-sidebar .ml-menu li.active > a, .accounting-sidebar .ml-menu li > a:hover, .accounting-sidebar .ml-menu li > a:focus { background: transparent !important; color: var(--accounting-teal) !important; }
    .accounting-sidebar .ml-menu li.active > a > i,
    .accounting-sidebar .ml-menu li.active > a > span,
    .accounting-sidebar .ml-menu li > a.toggled > i,
    .accounting-sidebar .ml-menu li > a.toggled > span { color: var(--accounting-teal) !important; opacity: 1 !important; filter: none !important; -webkit-font-smoothing: antialiased; }
    .accounting-sidebar .legal { border-top: 1px solid var(--accounting-line); color: var(--accounting-muted); padding: 16px 20px; font-size: 11px; }
    body.accounting-collapsed .accounting-sidebar { width: 100px; }
    body.accounting-collapsed .accounting-sidebar .sidebar-brand strong, body.accounting-collapsed .accounting-sidebar .sidebar-brand small, body.accounting-collapsed .accounting-sidebar .module-label, body.accounting-collapsed .accounting-sidebar .list span, body.accounting-collapsed .accounting-sidebar .legal { display: none; }
    body.accounting-collapsed .accounting-sidebar .list > li > a { text-align: center; padding: 14px 0; }
    body.accounting-collapsed .accounting-sidebar .list > li > a i { width: auto; }
    body.accounting-collapsed .accounting-sidebar .ml-menu { display: none !important; }
    @media (min-width: 768px) {
        section.content { margin-left: 220px; }
        body.accounting-collapsed section.content { margin-left: 100px; }
        section.content[dir="rtl"] { margin: 100px 220px 0 15px; }
        body.accounting-collapsed section.content[dir="rtl"] { margin: 100px 91px 0 15px; }
    }
    @media (max-width: 767px) { .accounting-sidebar { transform: translateX(-100%); transition: transform .2s ease; z-index: 1001; } body.accounting-drawer-open .accounting-sidebar { transform: translateX(0); } }
</style>

  <aside id="leftsidebar" class="sidebar accounting-sidebar">
    <div class="sidebar-brand"><strong>{{ Config::get('settings.company_name') }}</strong><small>{{ __('financial.screens.financial_workspace') }}</small></div>
    <div class="menu">
      <ul class="list">
        <li @if (request()->routeIs('dashboard')) class="active" @endif>
          <a href="{{ route('dashboard') }}" title="{{ __('financial.screens.dashboard') }}"><i class="fas fa-home"></i><span>{{ __('financial.screens.dashboard') }}</span></a>
        </li>
        <li @if (request()->routeIs('administration.hub')) class="active" @endif><a href="{{ route('administration.hub') }}" title="{{ __('financial.screens.administration') }}"><i class="fas fa-sliders-h"></i><span>{{ __('financial.screens.administration') }}</span></a></li>
        @if(auth()->check() && auth()->user()->role && strcasecmp(auth()->user()->role->name, 'Super Admin') === 0)
        <li @if (request()->routeIs('superadmin.*')) class="active" @endif>
          <a href="{{ route('superadmin.dashboard') }}" title="Super Admin Console"><i class="fas fa-shield-alt"></i><span>Super Admin Console</span></a>
        </li>
        @endif
        <li @if (request()->routeIs('reports.hub') or request()->routeIs('reports.*')) class="active" @endif>
          <a href="{{ route('reports.hub') }}" title="{{ __('financial.screens.reports') }}"><i class="fas fa-file-alt"></i><span>{{ __('financial.screens.reports') }}</span></a>
        </li>
        <li class="module-label">{{ __('financial.screens.workspace') }}</li>
        <li @if (request()->routeIs('sales.hub')) class="active" @endif><a href="{{ route('sales.hub') }}" title="{{ __('financial.screens.sales') }}"><i class="fas fa-chart-line"></i><span>{{ __('financial.screens.sales') }}</span></a></li>
        <li @if (request()->routeIs('purchases.expenses.hub')) class="active" @endif><a href="{{ route('purchases.expenses.hub') }}" title="{{ __('financial.screens.purchases_expenses') }}"><i class="fas fa-shopping-bag"></i><span>{{ __('financial.screens.purchases_expenses') }}</span></a></li>
        <li @if (request()->routeIs('accounting.hub')) class="active" @endif><a href="{{ route('accounting.hub') }}" title="{{ __('financial.screens.accounting') }}"><i class="fas fa-book-open"></i><span>{{ __('financial.screens.accounting') }}</span></a></li>
        <li @if (request()->routeIs('banking.cash.hub')) class="active" @endif><a href="{{ route('banking.cash.hub') }}" title="{{ __('financial.screens.banking_cash') }}"><i class="fas fa-university"></i><span>{{ __('financial.screens.banking_cash') }}</span></a></li>
        <li @if (request()->routeIs('inventory.assets.hub')) class="active" @endif><a href="{{ route('inventory.assets.hub') }}" title="{{ __('financial.screens.inventory_assets') }}"><i class="fas fa-boxes"></i><span>{{ __('financial.screens.inventory_assets') }}</span></a></li>
      </ul>
    </div>
  </aside>
</section>

      <script>
        (function () {
          var sidebar = document.getElementById('leftsidebar');
          if (!sidebar) return;
          var bars = document.querySelector('.bars');
          var administration = sidebar.querySelector('.administration-shell');
          if (administration) administration.querySelectorAll('.ml-menu .menu-toggle').forEach(function (toggle) {
            toggle.addEventListener('click', function () {
              window.setTimeout(function () {
                administration.querySelectorAll(':scope > .ml-menu > li > .ml-menu').forEach(function (menu) {
                  if (menu !== toggle.nextElementSibling) {
                    menu.style.display = 'none';
                    if (menu.previousElementSibling) menu.previousElementSibling.classList.remove('toggled');
                  }
                });
              }, 0);
            });
          });
          if (bars) bars.addEventListener('click', function () {
            document.body.classList.toggle(window.innerWidth < 768 ? 'accounting-drawer-open' : 'accounting-collapsed');
          });
        }());
      </script>
