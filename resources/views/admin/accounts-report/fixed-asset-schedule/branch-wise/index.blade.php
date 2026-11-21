@extends('layouts.pdf')

@push('include-css')
    <link rel="stylesheet" href="{{ asset('asset/css/main-report.css') }}">
@endpush
@section('title')
    {{ config('settings.company_name') }} -> {{ $extra['module_name'] }}
@endsection
@section('content')
    <div class="mid">
        <div class="col-md-12 col-sm-12 col-lg-12 col-xl-12">
            <br>
            <h2 class="text-center">{{ config('settings.company_name') }}</h2>
            <h6 class="text-center">{{ config('settings.address_1') }}</h6>
            <br>
            <h4 class="text-center">{{ $extra['voucher_type'] }}</h4>
            <hr>
        </div>
    </div>
    <div class="mid mb-3">
        <div class="row">
            <div class="col-md-12 col-sm-12 col-lg-12 col-xl-12">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <td class="text-right">{{ __('Search By') }}:</td>
                            <td class="text-right">{{ __('Branch Name') }}:</td>
                            <th>{{ $search_by['branch_name'] }}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
    <div class="mid">
        <div class="row">
            <div class="col-md-12 col-sm-12 col-lg-12 col-xl-12">
                <table class="table table-bordered table-sm table-hover">
                    <thead>
                        <tr>
                            <th class="text-center">
                                <h5>{{ __('financial.labels.particulars') }} </h5>
                            </th>
                            <th class="text-center">
                                <h5>
                                    {{ __('Opening Balance') }} ( {{ $search_by['from'] }} )
                                    <?php echo config('settings.is_code') == 'code' ? config('settings.currency_code') : config('settings.currency_symbol'); ?>
                                </h5>
                            </th>
                            <th class="text-center">
                                <h5>{{ __('financial.labels.addition_during_year') }}</h5>
                            </th>
                            <th class="text-center">
                                <h5>{{ __('financial.labels.deduction_during_year') }}</h5>
                            </th>
                            <th class="text-center">
                                <h5>{{ __('financial.labels.total_assets') }}</h5>
                            </th>
                            <th class="text-center">
                                <h5>{{ __('Dep.Rate (%)') }}</h5>
                            </th>
                            <th class="text-center">
                                <h5>{{ __('financial.labels.accumulated_depreciation') }}</h5>
                            </th>
                            <th class="text-center">
                                <h5>{{ __('financial.labels.current_year_depreciation') }}</h5>
                            </th>
                            <th class="text-center">
                                <h5>{{ __('financial.labels.total_depreciation') }}</h5>
                            </th>
                            <th class="text-center font-s-16">
                                <h5>{{ __('Closing Balance') }} ( {{ $search_by['to'] }} )
                                    <?php echo config('settings.is_code') == 'code' ? config('settings.currency_code') : config('settings.currency_symbol'); ?>
                                </h5>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($particulars['StartDate'] as $particular => $balance)
                            <tr>
                                <td class="text-left" scope="row">{{ $particular }}</td>
                                <td class="text-right" scope="row">{{ Helper::convertMoneyFormat($balance) }}</td>
                                <td class="text-center" scope="row">0</td>
                                <td class="text-center" scope="row">0</td>
                                <td class="text-center" scope="row">0</td>
                                <td class="text-center" scope="row">0</td>
                                <td class="text-center" scope="row">0</td>
                                <td class="text-center" scope="row">0</td>
                                <td class="text-center" scope="row">0</td>
                                <td class="text-right" scope="row">
                                    {{ Helper::convertMoneyFormat($particulars['EndDate'][$particular]) }}
                                </td>
                            </tr>
                        @endforeach
                        <tr>
                            <td class="text-right font-w-b font-s-20" scope="row">{{ __('Total') }}=</td>
                            <td class="text-right font-w-b font-s-20" scope="row">
                                {{ Helper::convertMoneyFormat($particulars['TotalBalance']['start_balance']) }}</td>
                            <td class="text-left font-w-b font-s-20" colspan="7" scope="row"></td>
                            <td class="text-right font-w-b font-s-20" scope="row">
                                {{ Helper::convertMoneyFormat($particulars['TotalBalance']['end_balance']) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <br>
    </div>
@stop
