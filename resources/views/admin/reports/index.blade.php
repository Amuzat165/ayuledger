@extends('layouts.app')

@section('title', 'Reports')

@section('top-bar')
    @include('shared.header')
@stop

@section('left-sidebar')
    @include('shared.sidebar')
@stop

@section('content')
<section @if ($is_rtl) dir="rtl" @endif class="content reports-hub">
    <div class="container-fluid">
        <div class="reports-intro">
            <p class="reports-eyebrow">Financial intelligence</p>
            <h1>Reports</h1>
            <p>Access and manage all your business and financial reports in one place.</p>
        </div>

        <label class="reports-search"><i class="fas fa-search"></i><input id="report-search" type="search" placeholder="Search reports..." aria-label="Search reports"></label>

        @php
            $reportCategories = [
                ['title' => 'Financial Reports', 'class' => 'financial', 'items' => [
                    ['reports.accounts.profit_or_loss_account', 'Income Statement', 'Income, expenses, and profitability.', 'fa-chart-line', 'ProfitOrLossAccount'],
                    ['reports.accounts.trial_balance', 'Trial Balance', 'Review debit and credit balances.', 'fa-balance-scale', 'TrialBalance'],
                    ['reports.accounts.balance_sheet', 'Financial Position', 'View assets, liabilities, and equity.', 'fa-university', 'StatementOfFinancialPosition'],
                    ['reports.accounts.cash_flow', 'Cash Flow', 'Analyze movement of cash and bank balances.', 'fa-money-bill-alt', 'CashFlow'],
                    ['reports.accounts.retained_earnings', 'Retained Earnings', 'Review changes in retained earnings.', 'fa-chart-pie', 'RetainedEarning'],
                    ['reports.accounts.retained_earnings', 'Statement of Changes in Equity', 'Review changes in equity over time.', 'fa-chart-pie', 'RetainedEarning'],
                    ['reports.accounts.cost_of_revenue', 'Cost of Revenue', 'Review direct costs against revenue.', 'fa-receipt', 'CostOfRevenue'],
                ]],
                ['title' => 'Accounting & Transactions', 'class' => 'accounting', 'items' => [
                    ['reports.accounts.ledger', 'General Ledger', 'Trace account activity and balances.', 'fa-book-open', 'Ledger'],
                    ['reports.accounts.receive_payment', 'Receipt and Payment', 'Review money received and paid.', 'fa-exchange-alt', 'ReceiveAndPayment'],
                    ['reports.accounts.notes', 'Notes Report', 'Review supporting account notes.', 'fa-sticky-note', 'Notes'],
                    ['reports.general.ledger.type', 'General Ledger Report', 'Analyze ledger activity by date.', 'fa-list', 'GeneralLedger'],
                    ['reports.general.voucher', 'General Voucher Report', 'Review vouchers across transactions.', 'fa-file-alt', 'GeneralVoucher'],
                ]],
                ['title' => 'Inventory & Assets', 'class' => 'assets', 'items' => [
                    ['reports.accounts.fixed_asset_schedule', 'Fixed Asset Schedule', 'Review assets and depreciation schedules.', 'fa-briefcase', 'FixedAssetsSchedule'],
                ]],
                ['title' => 'Administration & Planning', 'class' => 'admin', 'items' => [
                    ['reports.general.branch', 'Branch Report', 'Compare activity across branches.', 'fa-project-diagram', 'GeneralBranch'],
                    ['reports.general.bank_cash', 'General Bank & Cash', 'Review bank and cash activity.', 'fa-university', 'GeneralBankCash'],
                    ['reports.accounts.payroll', 'Payroll Report', 'Review payroll information by branch.', 'fa-users', null],
                    ['reports.accounts.budget', 'Budget Report', 'Review planned financial activity.', 'fa-clipboard-list', null],
                ]],
            ];
        @endphp

        <div class="reports-grid" id="reports-grid">
            @foreach ($reportCategories as $category)
                <section class="report-category {{ $category['class'] }}">
                    <div class="category-heading"><h2>{{ $category['title'] }}</h2><span>{{ count($category['items']) }} reports</span></div>
                    <div class="report-cards">
                        @foreach ($category['items'] as $item)
                            @if (!$item[4] || config('role_manage.' . $item[4] . '.All'))
                            <a class="report-card" href="{{ route($item[0]) }}" data-report-name="{{ strtolower($item[1] . ' ' . $item[2]) }}">
                                <span class="report-icon"><i class="fas {{ $item[3] }}"></i></span>
                                <span class="report-copy"><strong>{{ $item[1] }}</strong><small>{{ $item[2] }}</small></span>
                                <i class="fas fa-arrow-right report-arrow"></i>
                            </a>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
        <p class="reports-empty" id="reports-empty">No reports match your search.</p>
    </div>
</section>
@stop

@push('include-css')
<style>
    .reports-hub { background: #f6f9fa; min-height: calc(100vh - 66px); }
    .reports-intro { margin: 8px 0 25px; }
    .reports-eyebrow { color: #0f766e; font-size: 11px; font-weight: 700; letter-spacing: 1.2px; margin: 0 0 8px; text-transform: uppercase; }
    .reports-intro h1 { color: #183b56; font-size: 30px; font-weight: 600; margin: 0 0 8px; }
    .reports-intro p:last-child { color: #6b7c87; font-size: 14px; margin: 0; }
    .reports-search { background: #fff; border: 1px solid #d9e3e8; border-radius: 5px; display: block; max-width: 560px; padding: 0 15px; }
    .reports-search i { color: #0f766e; }
    .reports-search input { border: 0; color: #183b56; height: 46px; outline: 0; padding: 0 12px; width: calc(100% - 30px); }
    .reports-grid { display: grid; gap: 25px; grid-template-columns: repeat(2, minmax(0, 1fr)); margin-top: 30px; }
    .report-category { min-width: 0; }
    .category-heading { align-items: baseline; border-bottom: 1px solid #d9e3e8; display: flex; justify-content: space-between; margin-bottom: 12px; padding-bottom: 9px; }
    .category-heading h2 { color: #183b56; font-size: 15px; font-weight: 600; margin: 0; }
    .category-heading span { color: #91a0a7; font-size: 11px; }
    .report-cards { display: grid; gap: 10px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .report-card { align-items: center; background: #fff; border: 1px solid #d9e3e8; border-radius: 5px; display: flex; min-height: 92px; padding: 14px; text-decoration: none !important; transition: border-color .15s ease, transform .15s ease; }
    .report-card:hover { border-color: #0f766e; transform: translateY(-2px); }
    .report-icon { align-items: center; background: #e4f2f0; border-radius: 4px; color: #0f766e; display: flex; flex: 0 0 34px; height: 34px; justify-content: center; }
    .report-copy { display: flex; flex: 1; flex-direction: column; margin: 0 10px; min-width: 0; }
    .report-copy strong { color: #183b56; font-size: 12px; font-weight: 600; }
    .report-copy small { color: #7b8b94; font-size: 11px; line-height: 1.35; margin-top: 5px; }
    .report-arrow { color: #9aabb2; font-size: 11px; }
    .reports-empty { color: #7b8b94; display: none; margin-top: 35px; text-align: center; }
    @media (max-width: 900px) { .reports-grid { grid-template-columns: 1fr; } }
    @media (max-width: 540px) { .report-cards { grid-template-columns: 1fr; } .reports-intro h1 { font-size: 25px; } }
</style>
@endpush

@push('include-js')
<script>
    (function () {
        var search = document.getElementById('report-search');
        var empty = document.getElementById('reports-empty');
        if (!search) return;
        search.addEventListener('input', function () {
            var term = search.value.toLowerCase().trim();
            var visible = 0;
            document.querySelectorAll('.report-category').forEach(function (category) {
                var cards = category.querySelectorAll('.report-card');
                var categoryVisible = 0;
                cards.forEach(function (card) { var match = card.dataset.reportName.indexOf(term) !== -1; card.style.display = match ? 'flex' : 'none'; categoryVisible += match ? 1 : 0; });
                category.style.display = categoryVisible ? '' : 'none';
                visible += categoryVisible;
            });
            empty.style.display = visible ? 'none' : 'block';
        });
    }());
</script>
@endpush
