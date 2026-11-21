@extends('layouts.pdf')

@push('include-css')
    <link rel="stylesheet" href="{{ asset('asset/css/main-report.css') }}">
@endpush

@section('title')
    {{ config('settings.company_name') }} -> {{ $extra['module_name'] }}
@endsection
@section('content')
    <div class="mid">
        <h2 class="text-center">{{ config('settings.company_name') }}</h2>
        <h5 class="text-center ">{{ config('settings.address_1') }}</h5>
        <hr>
        <h4 class="text-center mb-4">{{ $extra['voucher_type'] }}</h4>
    </div>
    <div class="mid mb-3">
        <div class="row">
            <div class="col-md-12 col-sm-12 col-lg-12 col-xl-12">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <td class="text-right">{{ __('Search By') }}:</td>
                            <td class="text-right">{{ __('Ledger Type') }}</td>
                            <th class="text-left">{{ $search_by['group_name'] }}</th>
                            <td class="text-right">{{ __('Branch Name') }}:</td>
                            <th>{{ $search_by['branch_name'] }}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
    <div class="mid">
        @php
            $grand_total = 0;
        @endphp
        @foreach ($particulars as $key => $groups)
            <table class="table table-bordered table-sm table-hover">
                <thead>
                    <tr>
                        <th class="text-center padding-t-8" colspan="2">
                            <h3>{{ $key }}</h3>
                        </th>
                    </tr>
                    @if ($search_by['start_from'])
                        <tr>
                            <th class="text-center">
                            </th>
                            <th class="text-center">
                                <h5>{{ __('From') }} {{ $search_by['start_from'] }} {{ __('To') }} {{ $search_by['start_to'] }}</h5>
                            </th>
                        </tr>
                    @endif
                    <tr>
                        <th class="text-left" scope="col">{{ __('Groups Name') }}</th>
                        <th class="text-right" scope="col"> <?php echo config('settings.is_code') == 'code' ? config('settings.currency_code') : config('settings.currency_symbol'); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $sub_total = 0;
                    @endphp
                    @foreach ($groups->groupBy('IncomeExpenseHead.name') as $keyHead => $transactions)
                        @php
                            $head_balance = 0;
                        @endphp
                        @foreach ($transactions as $transaction)
                            @if ($transaction->IncomeExpenseHead->type)
                                @php
                                    $head_balance += (int) $transaction->dr - (int) $transaction->cr;
                                @endphp
                            @else
                                @php
                                    $head_balance += (int) $transaction->cr - (int) $transaction->dr;
                                @endphp
                            @endif
                        @endforeach
                        <tr>
                            <td class="text-left" scope="row">{{ $keyHead }}</td>
                            <td scope="row" class=" text-right">
                                {{ Helper::convertMoneyFormat($head_balance) }}</td>
                        </tr>
                        @php
                            $sub_total += $head_balance;
                        @endphp
                    @endforeach
                    <tr>
                        <th class="text-right"> {{ __('financial.labels.subtotal_equals') }}</th>
                        <th class="text-right">{{ Helper::convertMoneyFormat($sub_total) }}</th>
                    </tr>
                    @php
                        $grand_total += $sub_total;
                    @endphp
                </tbody>
            </table>
        @endforeach
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th class="text-right">
                        <h2>{{ __('Grand Total') }} = </h2>
                    </th>
                    <th class="text-right">
                        <h2>{{ Helper::convertMoneyFormat($grand_total) }} </h2>
                    </th>
                </tr>
            </thead>
        </table>
        <br>
    </div>
@stop
