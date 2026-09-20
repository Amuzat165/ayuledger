@extends('layouts.app')

@section('title')
    <?php $ApplicationName = Config::get('settings.company_name'); ?>
    {{ $ApplicationName }} -> Dashboard
@stop

@section('top-bar')
    @include('shared.header')
@stop

@section('left-sidebar')
    @include('shared.sidebar')
@stop

@push('include-css')

    <!-- Bootstrap Select Css -->
    <link href="{{ asset('asset/plugins/bootstrap-select/css/bootstrap-select.css') }}" rel="stylesheet" />
    <style>
        .accounting-dashboard { background: #f6f9fa; }
        .accounting-dashboard .block-header { margin-bottom: 22px; }
        .accounting-dashboard .block-header h2 { color: #183b56; font-size: 25px; font-weight: 600; }
        .financial-kpis { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 14px; margin-bottom: 24px; }
        .financial-kpi { background: #fff; border: 1px solid #d9e3e8; border-radius: 6px; min-height: 126px; padding: 18px; }
        .financial-kpi .kpi-icon { color: #0f766e; font-size: 17px; margin-bottom: 19px; }
        .financial-kpi .kpi-label { color: #6b7c87; font-size: 12px; margin: 0 0 8px; }
        .financial-kpi .kpi-value { color: #183b56; font-size: 22px; font-weight: 600; margin: 0; }
        .financial-kpi.kpi-positive { border-top: 3px solid #0f766e; }
        .financial-kpi.kpi-negative { border-top: 3px solid #c25b5b; }
        .accounting-dashboard .card { border: 1px solid #d9e3e8; border-radius: 6px; box-shadow: none; }
        .accounting-dashboard .card .header { border-bottom: 1px solid #edf1f3; padding: 18px 20px; }
        .accounting-dashboard .card .header h2 { color: #183b56; font-size: 15px; font-weight: 600; }
        .accounting-dashboard .chart-card .header { align-items: center; display: flex; justify-content: space-between; overflow: visible; }
        .accounting-dashboard .chart-card .header h2 { margin: 0; overflow: visible; white-space: nowrap; }
        .accounting-dashboard .chart-card .header .row { margin: 0; width: 100%; }
        .accounting-dashboard .chart-card .header .form-group { margin-bottom: 0; }
        .accounting-dashboard .chart-card .header select { min-width: 112px; }
        @media (max-width: 767px) { .accounting-dashboard .chart-card .header h2 { font-size: 13px; } .accounting-dashboard .chart-card .header .col-lg-6, .accounting-dashboard .chart-card .header .col-lg-4 { float: left; width: 50%; } }
        .accounting-dashboard .chart-card .chart-body { min-height: 280px; padding: 20px; position: relative; }
        .accounting-dashboard .chart-card canvas { max-height: 280px; }
        .accounting-dashboard .chart-empty { align-items: center; color: #7b8b94; display: flex; flex-direction: column; height: 240px; justify-content: center; text-align: center; }
        .accounting-dashboard .chart-empty i { color: #b6c5ca; font-size: 26px; margin-bottom: 12px; }
        .accounting-dashboard .chart-empty p { font-size: 13px; margin: 0; }
        .accounting-dashboard .quick-actions .button-demo { display: flex; flex-wrap: wrap; gap: 9px; }
        .accounting-dashboard .quick-actions .btn,
        .accounting-dashboard .quick-actions .btn.bg-red,
        .accounting-dashboard .quick-actions .btn.bg-green,
        .accounting-dashboard .quick-actions .btn.bg-orange,
        .accounting-dashboard .quick-actions .btn.bg-deep-purple,
        .accounting-dashboard .quick-actions .btn.bg-blue,
        .accounting-dashboard .quick-actions .btn.bg-light-green,
        .accounting-dashboard .quick-actions .btn.bg-light-blue,
        .accounting-dashboard .quick-actions .btn.bg-cyan,
        .accounting-dashboard .quick-actions .btn.bg-lime,
        .accounting-dashboard .quick-actions .btn.bg-brown,
        .accounting-dashboard .quick-actions .btn.bg-deep-orange,
        .accounting-dashboard .quick-actions .btn.bg-purple { -webkit-font-smoothing: antialiased; background: #e4f2f0 !important; border: 0; border-radius: 4px; box-shadow: none; color: #0f766e !important; filter: none; margin: 0; opacity: 1; padding: 10px 14px; text-shadow: none; transform: none; }
        .accounting-dashboard .quick-actions .btn:hover,
        .accounting-dashboard .quick-actions .btn:focus { background: #cbe7e3 !important; color: #0b5c56 !important; }
        .accounting-dashboard .quick-actions .btn:nth-child(n+7) { display: none; }
        .accounting-dashboard .quick-actions.is-expanded .btn:nth-child(n+7) { display: inline-block; }
        .view-all-actions { background: transparent; border: 0; color: #0f766e; cursor: pointer; font-size: 12px; margin-top: 14px; padding: 0; }
        .legacy-dashboard-widgets { display: none; }
        @media (max-width: 1200px) { .financial-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (max-width: 600px) { .financial-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } .financial-kpi { padding: 14px; } .financial-kpi .kpi-value { font-size: 18px; } }
    </style>
@endpush
@section('content')
    <section @if ($is_rtl) dir="rtl" @endif class="content accounting-dashboard">
        <div class="container-fluid">
            <div class="block-header">
                <h2> {{ __('root.dashboard.name') }} </h2>
            </div>
            <div class="financial-kpis">
                <div class="financial-kpi kpi-positive"><div class="kpi-icon"><i class="fas fa-chart-line"></i></div><p class="kpi-label">Total Revenue</p><p class="kpi-value">{{ number_format($total_sales, 2) }}</p></div>
                <div class="financial-kpi kpi-negative"><div class="kpi-icon"><i class="fas fa-arrow-down"></i></div><p class="kpi-label">Total Expenses</p><p class="kpi-value">{{ number_format($total_expenses, 2) }}</p></div>
                <div class="financial-kpi kpi-positive"><div class="kpi-icon"><i class="fas fa-balance-scale"></i></div><p class="kpi-label">Net Profit / Loss</p><p class="kpi-value">{{ number_format($total_net_profit, 2) }}</p></div>
                <div class="financial-kpi"><div class="kpi-icon"><i class="fas fa-university"></i></div><p class="kpi-label">Cash &amp; Bank Balance</p><p class="kpi-value">{{ number_format($cash_and_bank_balance, 2) }}</p></div>
                <div class="financial-kpi kpi-positive"><div class="kpi-icon"><i class="fas fa-chart-area"></i></div><p class="kpi-label">Gross Profit</p><p class="kpi-value">{{ number_format($total_gross_profit, 2) }}</p></div>
                <div class="financial-kpi"><div class="kpi-icon"><i class="fas fa-boxes"></i></div><p class="kpi-label">Total Unit Cost</p><p class="kpi-value">{{ number_format($total_unit_cost, 2) }}</p></div>
            </div>
            <!-- Widgets -->
            <div class="row clearfix legacy-dashboard-widgets">
                <div class="col-sm-6 col-md-2">
                    <div class="card-stats card-round">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-icon">
                                    <div class="icon-big text-center icon-primary bg-pink bubble-shadow-small">
                                        <i class="fas fa-project-diagram"></i>
                                    </div>
                                </div>
                                <div class="col col-stats ms-3 ms-sm-0">
                                    <div class="numbers">
                                        <p class="card-category">{{ __('root.dashboard.branch') }}</p>
                                        <h4 class="card-title">{{ $total_branches }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-2">
                    <div class="card-stats card-round">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-icon">
                                    <div class="icon-big text-center icon-primary bg-cyan bubble-shadow-small">
                                        <i class="fas fa-money-bill-alt"></i>
                                    </div>
                                </div>
                                <div class="col col-stats ms-3 ms-sm-0">
                                    <div class="numbers">
                                        <p class="card-category">{{ __('root.dashboard.ledger_type') }}</p>
                                        <h4 class="card-title">{{ $total_income_expense_types }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-2">
                    <div class="card-stats card-round">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-icon">
                                    <div class="icon-big text-center icon-primary bg-purple bubble-shadow-small">
                                        <i class="fas fa-folder-open"></i>
                                    </div>
                                </div>
                                <div class="col col-stats ms-3 ms-sm-0">
                                    <div class="numbers">
                                        <p class="card-category">{{ __('root.dashboard.ledger_group') }}</p>
                                        <h4 class="card-title">{{ $total_income_expense_groups }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-2">
                    <div class="card-stats card-round">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-icon">
                                    <div class="icon-big text-center icon-primary bg-light-green bubble-shadow-small">
                                        <i class="fas fa-chart-bar"></i>
                                    </div>
                                </div>
                                <div class="col col-stats ms-3 ms-sm-0">
                                    <div class="numbers">
                                        <p class="card-category">{{ __('root.dashboard.ledger') }}</p>
                                        <h4 class="card-title">{{ $total_income_expense_heads }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-2">
                    <div class="card-stats card-round">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-icon">
                                    <div class="icon-big text-center icon-primary bubble-shadow-small" style="background-color: #3498DB;">
                                        <i class="fas fa-university"></i>
                                    </div>
                                </div>
                                <div class="col col-stats ms-3 ms-sm-0">
                                    <div class="numbers">
                                        <p class="card-category">{{ __('root.dashboard.bank_or_cash') }}</p>
                                        <h4 class="card-title">{{ $total_bank_cashes }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-2">
                    <div class="card-stats card-round">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-icon">
                                    <div class="icon-big text-center icon-primary bubble-shadow-small" style="background-color: #F39C12;">
                                        <i class="fas fa-user"></i>
                                    </div>
                                </div>
                                <div class="col col-stats ms-3 ms-sm-0">
                                    <div class="numbers">
                                        <p class="card-category">{{ __('root.dashboard.user') }}</p>
                                        <h4 class="card-title">{{ $total_users }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                <div class="row clearfix legacy-dashboard-widgets">
                    <div class="col-sm-6 col-md-2">
                        <div class="card-stats card-round">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-icon">
                                        <div class="icon-big text-center bg-purple icon-primary bubble-shadow-small">
                                            <i class="fas fa-user-lock"></i>
                                        </div>
                                    </div>
                                    <div class="col col-stats ms-3 ms-sm-0">
                                        <div class="numbers">
{{--                                            <p class="card-category">{{ __('root.dashboard.role_manage') }}</p>--}}
                                            <p class="card-category">Role Manager</p>
                                            <h4 class="card-title">{{ $total_role_manages }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-2">
                        <div class="card-stats card-round">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-icon">
                                        <div class="icon-big text-center icon-primary bubble-shadow-small" style="background-color: #795548;">
                                            <i class="fas fa-receipt"></i>
                                        </div>
                                    </div>
                                    <div class="col col-stats ms-3 ms-sm-0">
                                        <div class="numbers">
{{--                                            <p class="card-category">{{ __('root.dashboard.report') }}</p>--}}
                                            <p class="card-category">Reports</p>
                                            <h4 class="card-title">14</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-2">
                        <div class="card-stats card-round">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-icon">
                                        <div class="icon-big text-center icon-primary bg-lime bubble-shadow-small">
                                            <i class="fas fa-users"></i>
                                        </div>
                                    </div>
                                    <div class="col col-stats ms-3 ms-sm-0">
                                        <div class="numbers">
                                            <p class="card-category">{{ __('root.dashboard.customers') }}</p>
                                            <h4 class="card-title">{{ $total_customers }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-2">
                        <div class="card-stats card-round">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-icon">
                                        <div class="icon-big text-center icon-primary bubble-shadow-small">
                                            <i class="fas fa-users"></i>
                                        </div>
                                    </div>
                                    <div class="col col-stats ms-3 ms-sm-0">
                                        <div class="numbers">
                                            <p class="card-category">{{ __('root.dashboard.vendors') }}</p>
                                            <h4 class="card-title">{{ $total_vendors }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-2">
                        <div class="card-stats card-round">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-icon">
                                        <div class="icon-big text-center icon-primary bubble-shadow-small" style="background-color: #008B8B;">
                                            <i class="fas fa-briefcase"></i>
                                        </div>
                                    </div>
                                    <div class="col col-stats ms-3 ms-sm-0">
                                        <div class="numbers">
                                            {{--                                            <p class="card-category">{{ __('root.dashboard.report') }}</p>--}}
                                            <p class="card-category">{{__('root.dashboard.asset')}}</p>
                                            <h4 class="card-title">{{ $total_asset }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-2">
                        <div class="card-stats card-round">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-icon">
                                        <div class="icon-big text-center icon-primary bg-light-green bubble-shadow-small">
                                            <i class="fas fa-box"></i>
                                        </div>
                                    </div>
                                    <div class="col col-stats ms-3 ms-sm-0">
                                        <div class="numbers">
                                            <p class="card-category">{{ __('root.dashboard.inventory') }}</p>
                                            <h4 class="card-title">{{ $total_inventory }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <div class="row clearfix legacy-dashboard-widgets">
                <div class="col-sm-6 col-md-2">
                    <div class="card-stats card-round">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-icon">
                                    <div class="icon-big text-center icon-primary bubble-shadow-small" style="background-color: darkred;">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                </div>
                                <div class="col col-stats ms-3 ms-sm-0">
                                    <div class="numbers">
                                        <p class="card-category">{{ __('root.dashboard.expenses') }}</p>
                                        <h4 class="card-title">{{ number_format($total_expenses,2) }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-2">
                    <div class="card-stats card-round">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-icon">
                                    <div class="icon-big text-center icon-primary bubble-shadow-small" style="background-color: #F39C12;">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                </div>
                                <div class="col col-stats ms-3 ms-sm-0">
                                    <div class="numbers">
                                        <p class="card-category">{{ __('root.dashboard.unit') }}</p>
                                        <h4 class="card-title">{{ number_format($total_unit_cost,2)  }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-2">
                    <div class="card-stats card-round">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-icon">
                                    <div class="icon-big text-center icon-primary bubble-shadow-small" style="background-color: #28a745;">
                                        <i class="fas fa-dollar-sign"></i>
                                    </div>
                                </div>
                                <div class="col col-stats ms-3 ms-sm-0">
                                    <div class="numbers">
                                        <p class="card-category">{{ __('root.dashboard.sales') }}</p>
                                        <h4 class="card-title">{{ number_format($total_sales, 2) }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-2">
                    <div class="card-stats card-round">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-icon">
                                    <div class="icon-big text-center icon-primary bg-blue bubble-shadow-small">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                </div>
                                <div class="col col-stats ms-3 ms-sm-0">
                                    <div class="numbers">
                                        <p class="card-category">{{ __('root.dashboard.gross') }}</p>
                                        <h4 class="card-title">{{ number_format($total_gross_profit, 2) }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-2">
                    <div class="card-stats card-round">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-icon">
                                    <div class="icon-big text-center icon-primary bg-deep-purple bubble-shadow-small">
                                        <i class="fas fa-coins"></i>
                                    </div>
                                </div>
                                <div class="col col-stats ms-3 ms-sm-0">
                                    <div class="numbers">
                                        <p class="card-category">{{ __('root.dashboard.net') }}</p>
                                        <h4 class="card-title">{{ number_format($total_net_profit,2)  }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                <!-- #END# Widgets -->
            </div>
            <!--  Quick links  -->
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="card quick-actions">
                        <div class="header">
                            <h2>
                                {{ __('root.dashboard.quick_access') }}
                            </h2>
                        </div>
                        <div class="body">
                            <div class="button-demo">
                                <a href="{{ route('reports.accounts.ledger') }}" type="button"
                                   class="btn bg-teal waves-effect">{{ __('root.dashboard.quick_access_ledger') }}</a>
                                <a href="{{ route('reports.accounts.trial_balance') }}" type="button"
                                   class="btn bg-green waves-effect">{{ __('root.dashboard.quick_access_trial_balance')
                                }}</a>
                                <a href="{{ route('reports.accounts.cost_of_revenue') }}" type="button"
                                   class="btn bg-orange waves-effect">{{ __('root.dashboard.quick_access_cost_of_revenue')
                                }}</a>
                                <a href="{{ route('reports.accounts.profit_or_loss_account') }}" type="button"
                                   class="btn bg-deep-purple waves-effect">{{
                                __('root.dashboard.quick_access_profit_or_loss_account') }}</a>
                                <a href="{{ route('reports.accounts.retained_earnings') }}" type="button"
                                   class="btn bg-blue waves-effect">{{ __('root.dashboard.quick_access_retained_earnings')
                                }}</a>
                                <a href="{{ route('reports.accounts.fixed_asset_schedule') }}" type="button"
                                   class="btn bg-light-green waves-effect">{{
                                __('root.dashboard.quick_access_fixed_asset_schedule') }}</a>
                                <a href="{{ route('reports.accounts.balance_sheet') }}" type="button"
                                   class="btn bg-light-blue waves-effect">{{
                                __('root.dashboard.quick_access_balance_sheet') }}</a>
                                <a href="{{ route('reports.accounts.cash_flow') }}" type="button"
                                   class="btn bg-cyan waves-effect">{{ __('root.dashboard.quick_access_cash_flow') }}</a>
                                <a href="{{ route('reports.accounts.receive_payment') }}" type="button"
                                   class="btn bg-teal waves-effect">{{ __('root.dashboard.quick_access_receive_payment')
                                }}</a>
                                <a href="{{ route('income_expense_type') }}" type="button"
                                   class="btn bg-light-green waves-effect">{{ __('root.dashboard.quick_access_ledger_type')
                                }}</a>
                                <a href="{{ route('income_expense_group') }}" type="button"
                                   class="btn bg-orange waves-effect">{{ __('root.dashboard.quick_access_ledger_group')
                                }}</a>

                                <a href="{{ route('dr_voucher') }}" type="button" class="btn bg-lime waves-effect">{{
                                __('root.dashboard.quick_access_debit_voucher') }}</a>
                                <a href="{{ route('cr_voucher') }}" type="button" class="btn bg-brown waves-effect">{{
                                __('root.dashboard.quick_access_credit_voucher') }}</a>
                                <a href="{{ route('jnl_voucher') }}" type="button"
                                   class="btn bg-deep-orange waves-effect">{{
                                __('root.dashboard.quick_access_journal_voucher') }}</a>
                                <a href="{{ route('contra_voucher') }}" type="button" class="btn bg-orange waves-effect">{{
                                __('root.dashboard.quick_access_contra_voucher') }}</a>
                                <a href="" type="button" class="btn bg-purple waves-effect">Invoice</a>
                                <a href="" type="button" class="btn bg-light-green waves-effect">Receipt</a>
                            </div>
                            <button type="button" class="view-all-actions" aria-expanded="false">View all actions <i class="fas fa-chevron-down"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Chart -->
            <div class="row clearfix">
                <!-- Line Chart -->
                <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
                    <div class="card chart-card">
                        <div class="header">
                            <div class="row">
                                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                    <h2>{{ __('root.dashboard.graph_profit_or_loss') }}</h2>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12 float-r">
                                    <div class="form-group form-float">
                                        <div class="form-line">
                                            <select data-live-search="true" class="form-control  show-tick" name="year"
                                                    id="profit_or_loss_year">
                                                <option value="0">Select year</option>
                                                @foreach ($years as $year)
                                                    <option @if ($year==date('Y')) selected @endif value="{{ $year }}">{{ $year
                                                }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="body chart-body">
                            <canvas id="profit_or_loss_graph" height="150"></canvas>
                        </div>
                    </div>
                </div>
                <!-- #END# Line Chart -->
                <!-- Bar Chart -->
                <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
                    <div class="card chart-card">
                        <div class="header">
                            <div class="row">
                                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                    <h2>{{ __('root.dashboard.graph_total_voucher') }}</h2>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12 float-r">
                                    <div class="form-group form-float">
                                        <div class="form-line">
                                            <select data-live-search="true" class="form-control  show-tick" name="year"
                                                    id="voucher_year">
                                                <option value="0">Select year</option>
                                                @foreach ($years as $year)
                                                    <option @if ($year==date('Y')) selected @endif value="{{ $year }}">{{ $year
                                                }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="body chart-body">
                            <canvas id="bar_chart" height="150"></canvas>
                        </div>
                    </div>
                </div>
                <!-- #END# Bar Chart -->
            </div>
    </section>
@stop
@push('include-js')
    <!-- Jquery CountTo Plugin Js -->
    <script src="{{ asset('asset/plugins/jquery-countto/jquery.countTo.js') }}"></script>
    <!-- ChartJs -->
    <script src="{{ asset('asset/plugins/chartjs/chart.min.js') }}"></script>
    <script>
        document.querySelector('.view-all-actions')?.addEventListener('click', function () {
            var actions = document.querySelector('.quick-actions');
            var expanded = actions.classList.toggle('is-expanded');
            this.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            this.innerHTML = expanded ? 'Show fewer actions <i class="fas fa-chevron-up"></i>' : 'View all actions <i class="fas fa-chevron-down"></i>';
        });
        @if (Session::has('success'))
            toastr["success"]('{{ Session::get('success') }}');
        @endif
            @if (Session::has('error'))
            toastr["error"]('{{ Session::get('error') }}');
        @endif
            @if ($errors->any())
            @foreach ($errors->all() as $error)
            toastr["error"]('{{ $error }}');
        @endforeach
        @endif
        $(function() {
            //Widgets count
            $('.count-to').countTo();
            // voucher total graph by ajax
            function hasChartData(datasets) {
                return datasets.some(function (dataset) { return dataset.data.some(function (value) { return Number(value) !== 0; }); });
            }

            function showChartState(canvas, message) {
                canvas.style.display = 'none';
                var emptyState = document.createElement('div');
                emptyState.className = 'chart-empty';
                emptyState.innerHTML = '<i class="fas fa-chart-line"></i><p>' + message + '</p>';
                canvas.parentElement.appendChild(emptyState);
            }

            function clearChartState(canvas) {
                canvas.style.display = '';
                var emptyState = canvas.parentElement.querySelector('.chart-empty');
                if (emptyState) emptyState.remove();
            }

            function chartConfig(type, datasets) {
                var config = Helper.configChart(type, labels, datasets);
                config.options.legend = {
                    position: 'bottom',
                    labels: { boxWidth: 12, usePointStyle: true, padding: 18 }
                };
                config.options.tooltips = { mode: 'index', intersect: false };
                config.options.hover = { mode: 'index', intersect: false };
                config.options.scales.xAxes = [{ gridLines: { display: false } }];
                config.options.scales.yAxes[0].gridLines = { color: '#edf1f3' };
                config.options.scales.yAxes[0].ticks.beginAtZero = true;
                return config;
            }

            function getTotalVoucher(year) {
                return new Promise((resolve, reject) => {
                    let datasets;
                    Helper.ajaxRequest('GET', `total-voucher/${year}`).then((res) => {
                        let data = res.data;
                        datasets = [{
                            label: "{{ __('root.dashboard.graph_total_dr_voucher') }}",
                            data: data.total_dr_voucher,
                            backgroundColor: '#0f766e'
                        },
                            {
                                label: "{{ __('root.dashboard.graph_total_cr_voucher') }}",
                                data: data.total_cr_voucher,
                                backgroundColor: '#2f80a8'
                            },
                            {
                                label: "{{ __('root.dashboard.graph_total_cnt_voucher') }}",
                                data: data.total_cnt_voucher,
                                backgroundColor: '#d18b36'
                            },
                            {
                                label: "{{ __('root.dashboard.graph_total_jnl_voucher') }}",
                                data: data.total_jnl_voucher,
                                backgroundColor: '#183b56'
                            }
                        ];
                        resolve(datasets);
                    }).catch((error) => {
                        reject(0);
                    });
                });
            }

            function getProfitOrLoss(year) {
                return new Promise((resolve, reject) => {
                    let datasets;
                    Helper.ajaxRequest('GET', `profit-loss/${year}`).then((res) => {
                        let res_data = res.data;
                        datasets = [{
                            label: "{{ __('root.dashboard.graph_revenue') }}",
                            data: res_data.revenue,
                            borderColor: '#0f766e', backgroundColor: 'rgba(15,118,110,.10)', fill: true,
                        },
                            {
                                label: "{{ __('root.dashboard.graph_net_profit_loss') }}",
                                data: res_data.NetProfitOrLoss,
                                borderColor: '#0f766e', backgroundColor: 'rgba(15,118,110,.10)', fill: true,
                                tension: 0.25,
                            }
                        ];
                        resolve(datasets);
                    }).catch((error) => {
                        reject(0);
                    });
                });
            }

            // load current year
            const labels = [
                "Jan",
                "Feb",
                "Mar",
                "Apr",
                "May",
                "June",
                "July",
                "Aug",
                "Sept",
                "Oct",
                "Nov",
                "Dec"
            ];
            // total voucher graph
            const bar_chart = document.getElementById("bar_chart");
            let barChart;
            getTotalVoucher(new Date().getFullYear()).then((datasets) => {
                clearChartState(bar_chart);
                if (hasChartData(datasets)) barChart = new Chart(bar_chart, chartConfig('bar', datasets));
                else showChartState(bar_chart, 'No voucher activity for this year');
            }).catch((error) => {
                showChartState(bar_chart, 'Voucher data is unavailable');
            });
            document.getElementById("voucher_year").addEventListener('change', (e) => {
                getTotalVoucher(e.target.value).then((datasets) => {
                    if (barChart) barChart.destroy();
                    clearChartState(bar_chart);
                    if (hasChartData(datasets)) barChart = new Chart(bar_chart, chartConfig('bar', datasets));
                    else showChartState(bar_chart, 'No voucher activity for this year');
                }).catch((error) => {
                    if (barChart) barChart.destroy();
                    showChartState(bar_chart, 'Voucher data is unavailable');
                });
            });
            // Profit or loss graph
            const profit_or_loss_graph = document.getElementById("profit_or_loss_graph");
            let profitOrLossGraph;
            getProfitOrLoss(new Date().getFullYear()).then((datasets) => {
                clearChartState(profit_or_loss_graph);
                if (hasChartData(datasets)) profitOrLossGraph = new Chart(profit_or_loss_graph, chartConfig('line', datasets));
                else showChartState(profit_or_loss_graph, 'No income statement activity for this year');
            }).catch((error) => {
                showChartState(profit_or_loss_graph, 'Income statement data is unavailable');
            });
            document.getElementById("profit_or_loss_year").addEventListener('change', (e) => {
                getProfitOrLoss(e.target.value).then((datasets) => {
                    if (profitOrLossGraph) profitOrLossGraph.destroy();
                    clearChartState(profit_or_loss_graph);
                    if (hasChartData(datasets)) profitOrLossGraph = new Chart(profit_or_loss_graph, chartConfig('line', datasets));
                    else showChartState(profit_or_loss_graph, 'No income statement activity for this year');
                }).catch((error) => {
                    if (profitOrLossGraph) profitOrLossGraph.destroy();
                    showChartState(profit_or_loss_graph, 'Income statement data is unavailable');
                });
            });

        });
    </script>
    {{-- All datagrid --}}
    <script src="{{ Helper::assetV('asset/js/dataTable.js') }}"></script>
    <script>
        BaseController.init();
    </script>
@endpush
