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
                        <th class="text-center">{{ __('Sl.No') }}</th>
                        <th>{{ __('Account ID') }}</th>
                        <th>{{ __('financial.screens.account_name') }}</th>
                        <th>{{ __('financial.terms.transaction_date') }}</th>
                        <th>{{ __('financial.terms.reference') }}</th>
                        <th>{{ __('financial.terms.journal') }}</th>
                        <th>{{ __('financial.terms.description') }}</th>
                        <th class="text-right">{{ __('financial.terms.debit') }}</th>
                        <th class="text-right">{{ __('financial.terms.credit') }}</th>
                        <th class="text-right">{{ __('financial.terms.balance') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @php $sl = 1; @endphp
                    @forelse (collect($items)->groupBy(['account_id', 'account_description']) as $accountId => $accountGroup)
                        @foreach ($accountGroup as $accountName => $transactions)
                            @php $rowCount = count($transactions); @endphp
                            @foreach ($transactions as $index => $item)
                                <tr>
                                    @if ($index == 0)
                                        <td class="text-center" rowspan="{{ $rowCount }}">{{ $sl }}</td>
                                        <td rowspan="{{ $rowCount }}">{{ $item->account_id }}</td>
                                        <td rowspan="{{ $rowCount }}">{{ $item->account_description }}</td>
                                    @endif
                                    <td>{{ $item->trans_date }}</td>
                                    <td>{{ $item->reference }}</td>
                                    <td>{{ $item->journal }}</td>
                                    <td>{{ $item->description }}</td>
                                    <td class="text-right">
                                        {{ is_numeric($item->debit) ? number_format($item->debit, 2) : '' }}
                                    </td>
                                    <td class="text-right">
                                        {{ is_numeric($item->credit) ? number_format($item->credit, 2) : '' }}
                                    </td>
                                    <td class="text-right">
                                        {{ is_numeric($item->balance) ? number_format($item->balance, 2) : '' }}
                                    </td>

                                </tr>
                            @endforeach
                            @php $sl++; @endphp
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="10" class="text-center">{{ __('No data available') }}</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop
