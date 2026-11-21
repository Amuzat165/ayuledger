@extends('layouts.pdf')
@section('title')
    {{ $extra['module_name'] }}
@endsection
@section('content')
    <div class="mid">
        <div class="row">
            <div class="col-md-12 col-sm-12 col-lg-12 col-xl-12">
                <table class="table table-bordered table-sm table-hover">
                    <thead>
                        <tr>
                            <th colspan="6">{{ $extra['voucher_type'] }}</th>
                        </tr>
                        <tr>
                            <th colspan="6"><strong>{{ __('Branch') }}:</strong> {{ $search_by['branch_name'] }} | <strong>{{ __('Period') }}:</strong> {{ $search_by['start_from'] }} to {{ $search_by['end_to'] }}</th>
                        </tr>
                        <tr>
                            <th style="width: 25%;"><h5>{{ __('financial.labels.particulars') }}</h5></th>
                            <th style="width: 15%;"><h5>{{ __('Share Capital') }}</h5></th>
                            <th style="width: 15%;"><h5>{{ __('Share Premium') }}</h5></th>
                            <th style="width: 15%;"><h5>{{ __('financial.terms.retained_earnings') }}</h5></th>
                            <th style="width: 15%;"><h5>{{ __('Other Reserves') }}</h5></th>
                            <th style="width: 15%;"><h5>{{ __('Total Equity') }}</h5></th>
                        </tr>
                        <tr>
                            <th colspan="6" style="text-align: center;">
                                <?php echo config('settings.is_code') == 'code' ? config('settings.currency_code') : config('settings.currency_symbol'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($particulars as $key => $particular)
                            @if ($key == 'BalanceAtBeginningOfPeriod' || $key == 'BalanceAtEndOfPeriod' || $key == 'TotalComprehensiveIncome')
                                <tr style="background-color: #f0f0f0; font-weight: bold;">
                            @else
                                <tr>
                            @endif
                                <td class="text-left">{{ $particular['name'] }}</td>
                                <td class="text-right">{{ $particular['share_capital'] }}</td>
                                <td class="text-right">{{ $particular['share_premium'] }}</td>
                                <td class="text-right">{{ $particular['retained_earnings'] }}</td>
                                <td class="text-right">{{ $particular['other_reserves'] }}</td>
                                <td class="text-right" style="font-weight: bold;">{{ $particular['total'] }}</td>
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
