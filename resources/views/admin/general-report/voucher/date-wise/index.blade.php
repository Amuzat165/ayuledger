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
                            <td class="text-right">{{ __('financial.labels.search_by') }}:</td>
                            <td class="text-right">{{ __('Voucher Type') }}</td>
                            <th>{{ $search_by['type_name'] }}</th>
                            <td class="text-right">{{ __('financial.reports.date_start') }}:</td>
                            <th>{{ $search_by['from'] }}</th>
                            <td class="text-right">{{ __('financial.reports.date_end') }}:</td>
                            <th>{{ $search_by['to'] }}</th>
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
                                {{ __('Sl.No') }}
                            </th>
                            <th class="text-center">{{ __('financial.labels.voucher_number') }}</th>
                            <th class="text-center">{{ __('Type') }}</th>
                            <th>{{ __('financial.labels.particulars') }}</th>
                            <th>{{ __('financial.labels.branch_name') }}</th>
                            <th>{{ __('financial.labels.date') }}</th>
                            <th>{{ __('Ledger Name') }}</th>
                            <th>{{ __('financial.labels.payment_account') }}</th>
                            <th>{{ __('CHQ. No') }}</th>
                            <th>{{ __('Debit') }} ( <?php echo config('settings.is_code') == 'code' ? config('settings.currency_code') : config('settings.currency_symbol'); ?>
                                )
                            </th>
                            <th>{{ __('Credit') }} ( <?php echo config('settings.is_code') == 'code' ? config('settings.currency_code') : config('settings.currency_symbol'); ?>
                                )
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $sl = 1;
                        @endphp
                        @foreach ($items as $item)
                            <tr>
                                <td class="text-center">{{ $sl }}</td>
                                <td class="text-center">{{ str_pad($item->voucher_no, 4, '0', STR_PAD_LEFT) }}</td>
                                <td>{{ $item->voucher_type }}</td>
                                <td>{{ $item->particulars }}</td>
                                <td>{{ $item->Branch ? $item->Branch->name : '' }}</td>
                                <td>{{ date(config('settings.date_format'), strtotime($item->voucher_date)) }}</td>
                                <td>
                                    {{ $item->IncomeExpenseHead ? $item->IncomeExpenseHead->name : '' }}
                                </td>
                                <td>
                                    {{ $item->BankCash ? $item->BankCash->name : '' }}
                                </td>
                                <td> {{ $item->cheque_number }} </td>
                                <td>{{ Helper::convertMoneyFormat($item->dr) }}</td>
                                <td>{{ Helper::convertMoneyFormat($item->cr) }}</td>
                            </tr>
                            @php
                                $sl++;
                            @endphp
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop
