@inject('Bh', 'App\Helpers\BladeHelper')

<style>
    :root { --accounting-ink: #183b56; --accounting-teal: #0f766e; --accounting-line: #d9e3e8; --accounting-muted: #6b7c87; }
    .accounting-sidebar { background: #f8fbfc; border-right: 1px solid var(--accounting-line); box-shadow: none; }
    .accounting-sidebar .sidebar-brand { padding: 22px 20px 18px; border-bottom: 1px solid var(--accounting-line); }
    .accounting-sidebar .sidebar-brand strong { color: var(--accounting-ink); font-size: 18px; }
    .accounting-sidebar .sidebar-brand small { display: block; color: var(--accounting-muted); font-size: 11px; margin-top: 4px; }
    .accounting-sidebar .module-label { padding: 18px 20px 7px; color: var(--accounting-muted); font-size: 10px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; }
    .accounting-sidebar .list > li > a { color: #36505f; padding: 12px 20px; }
    .accounting-sidebar .list > li > a i { color: var(--accounting-teal); width: 24px; }
    .accounting-sidebar, .accounting-sidebar *, .accounting-sidebar a, .accounting-sidebar span { filter: none !important; opacity: 1 !important; text-shadow: none; }
    .accounting-sidebar .list > li > a, .accounting-sidebar .ml-menu li > a { -webkit-font-smoothing: antialiased; transform: none !important; }
    .accounting-sidebar .list > li.active > a { background: #e4f2f0; color: var(--accounting-teal); font-weight: 600; }
    .accounting-sidebar .list > li.active > a,
    .accounting-sidebar .list > li.active > a > i,
    .accounting-sidebar .list > li.active > a > span { background: #e4f2f0 !important; color: var(--accounting-teal) !important; font-weight: 600; opacity: 1 !important; }
    .accounting-sidebar .list > li.active > .ml-menu,
    .accounting-sidebar .inventory-assets-menu { display: block; }
    .accounting-sidebar .inventory-assets-menu { list-style: none; margin: 0; padding: 0; }
    .accounting-sidebar .inventory-assets-menu > li { display: list-item !important; height: auto !important; visibility: visible !important; }
    .accounting-sidebar .inventory-assets-menu > li > a { display: flex !important; height: auto !important; visibility: visible !important; color: #526873 !important; }
    .accounting-sidebar .ml-menu { background: transparent; }
    .accounting-sidebar .ml-menu li a { color: #526873; padding: 8px 18px 8px 53px; font-size: 13px; }
    .accounting-sidebar .ml-menu li.active > a { color: var(--accounting-teal); font-weight: 600; }
    .accounting-sidebar .ml-menu li.active > a, .accounting-sidebar .ml-menu li > a:hover, .accounting-sidebar .ml-menu li > a:focus { background: transparent !important; color: var(--accounting-teal) !important; }
    .accounting-sidebar .ml-menu li.active > a > i,
    .accounting-sidebar .ml-menu li.active > a > span,
    .accounting-sidebar .ml-menu li > a.toggled > i,
    .accounting-sidebar .ml-menu li > a.toggled > span { color: var(--accounting-teal) !important; opacity: 1 !important; filter: none !important; -webkit-font-smoothing: antialiased; }
    .accounting-sidebar .legal { border-top: 1px solid var(--accounting-line); color: var(--accounting-muted); padding: 16px 20px; font-size: 11px; }
    body.accounting-collapsed .accounting-sidebar { width: 76px; }
    body.accounting-collapsed .accounting-sidebar .sidebar-brand strong, body.accounting-collapsed .accounting-sidebar .sidebar-brand small, body.accounting-collapsed .accounting-sidebar .module-label, body.accounting-collapsed .accounting-sidebar .list span, body.accounting-collapsed .accounting-sidebar .legal { display: none; }
    body.accounting-collapsed .accounting-sidebar .list > li > a { text-align: center; padding: 14px 0; }
    body.accounting-collapsed .accounting-sidebar .list > li > a i { width: auto; }
    body.accounting-collapsed .accounting-sidebar .ml-menu { display: none !important; }
    @media (max-width: 767px) { .accounting-sidebar { transform: translateX(-100%); transition: transform .2s ease; z-index: 1001; } body.accounting-drawer-open .accounting-sidebar { transform: translateX(0); } }
</style>

<section>
  <aside id="leftsidebar" class="sidebar accounting-sidebar">
    <div class="sidebar-brand"><strong>{{ Config::get('settings.company_name') }}</strong><small>Financial workspace</small></div>
    <div class="menu">
      <ul class="list">
        <li @if (request()->routeIs('dashboard')) class="active" @endif>
          <a href="{{ route('dashboard') }}" title="Dashboard"><i class="fas fa-home"></i><span>Dashboard</span></a>
        </li>
        <li @if (request()->routeIs('administration.hub')) class="active" @endif><a href="{{ route('administration.hub') }}" title="Administration"><i class="fas fa-sliders-h"></i><span>Administration</span></a></li>
        <li @if (request()->routeIs('reports.hub') or request()->routeIs('reports.*')) class="active" @endif>
          <a href="{{ route('reports.hub') }}" title="Reports"><i class="fas fa-file-alt"></i><span>Reports</span></a>
        </li>
        <li class="module-label">Workspace</li>
        <li @if (request()->routeIs('sales.hub')) class="active" @endif><a href="{{ route('sales.hub') }}" title="Sales"><i class="fas fa-chart-line"></i><span>Sales</span></a></li>
        <li @if (request()->routeIs('purchases.expenses.hub')) class="active" @endif><a href="{{ route('purchases.expenses.hub') }}" title="Purchases &amp; Expenses"><i class="fas fa-shopping-bag"></i><span>Purchases &amp; Expenses</span></a></li>
        <li @if (request()->routeIs('accounting.hub')) class="active" @endif><a href="{{ route('accounting.hub') }}" title="Accounting"><i class="fas fa-book-open"></i><span>Accounting</span></a></li>
        <li @if (request()->routeIs('banking.cash.hub')) class="active" @endif><a href="{{ route('banking.cash.hub') }}" title="Banking &amp; Cash"><i class="fas fa-university"></i><span>Banking &amp; Cash</span></a></li>
        <li @if (request()->routeIs('inventory.assets.hub')) class="active" @endif><a href="{{ route('inventory.assets.hub') }}" title="Inventory &amp; Assets"><i class="fas fa-boxes"></i><span>Inventory &amp; Assets</span></a></li>
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
