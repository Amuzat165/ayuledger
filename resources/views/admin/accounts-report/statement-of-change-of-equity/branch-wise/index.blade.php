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
            <h6 class="text-center">{{ __('For the period from') }} {{ $search_by['start_from'] }} to {{ $search_by['end_to'] }}</h6>
            <hr>
        </div>
    </div>
    <div class="mid mb-3">
        <div class="row">
            <div class="col-md-12 col-sm-12 col-lg-12 col-xl-12">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <td class="text-right"><strong>{{ __('Branch Name') }}:</strong></td>
                            <th>{{ $search_by['branch_name'] }}</th>
                        </tr>
                        <tr>
                            <td class="text-right"><strong>{{ __('financial.terms.currency') }}:</strong></td>
                            <th><?php echo config('settings.is_code') == 'code' ? config('settings.currency_code') : config('settings.currency_symbol'); ?></th>
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
                            <th class="text-center" style="width: 25%;">
                                <h5>{{ __('financial.labels.particulars') }}</h5>
                            </th>
                            <th class="text-center" style="width: 15%;">
                                <h5>{{ __('Share Capital') }}</h5>
                            </th>
                            <th class="text-center" style="width: 15%;">
                                <h5>{{ __('Share Premium') }}</h5>
                            </th>
                            <th class="text-center" style="width: 15%;">
                                <h5>{{ __('financial.terms.retained_earnings') }}</h5>
                            </th>
                            <th class="text-center" style="width: 15%;">
                                <h5>{{ __('Other Reserves') }}</h5>
                            </th>
                            <th class="text-center" style="width: 15%;">
                                <h5>{{ __('Total Equity') }}</h5>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($particulars as $key => $particular)
                            @if ($key == 'BalanceAtBeginningOfPeriod' || $key == 'BalanceAtEndOfPeriod')
                                <tr class="font-w-b font-s-16" style="background-color: #f0f0f0;">
                            @elseif ($key == 'TotalComprehensiveIncome')
                                <tr class="font-w-b font-s-14" style="background-color: #f5f5f5;">
                            @else
                                <tr>
                            @endif
                                <td class="text-left" scope="row">{{ $particular['name'] }}</td>
                                <td class="text-right">{{ \App\Helpers\Helper::convertMoneyFormat($particular['share_capital']) }}</td>
                                <td class="text-right">{{ \App\Helpers\Helper::convertMoneyFormat($particular['share_premium']) }}</td>
                                <td class="text-right">{{ \App\Helpers\Helper::convertMoneyFormat($particular['retained_earnings']) }}</td>
                                <td class="text-right">{{ \App\Helpers\Helper::convertMoneyFormat($particular['other_reserves']) }}</td>
                                <td class="text-right font-w-b">{{ \App\Helpers\Helper::convertMoneyFormat($particular['total']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <br>
        <div class="row">
            <div class="col-md-12">
                <p><strong>{{ __('Generated on') }}:</strong> {{ $extra['current_date_time'] }}</p>
            </div>
        </div>
    </div>
@stop
