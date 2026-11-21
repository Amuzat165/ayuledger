@extends('layouts.table-layout')

@inject('Bh', 'App\Helpers\BladeHelper')
@inject('Cc', 'App\Constants\CustomerConstant')
@inject('IETs', 'App\Services\IncomeExpenseTypeService')

<?php $params = (object)$params ?>

@section('title', $params->title)

@section('container')
    <x-breadcrumb :level="$params->resource" :location="$params->title" :as="$params->type" />
    {{-- --}}
    <div class="row">
        <div class="col-xs-12">
            <div class="card">
                <x-table.table-search-bar :all="count($params->tbody)" :trash="0" />
                @foreach ($params->tbody as $customer)
                    <form id="delete-customer-{{ $customer->id }}" action="{{ route('customers.destroy', $customer->id) }}" method="POST" class="d-none">
                        @csrf
                        @method('DELETE')
                    </form>
                @endforeach
                {{-- --}}
                <form class="actionForm" action="" method="get">
                    @csrf
                    <x-table.table-pagination-with-selected />
                    {{-- --}}
                    <x-table.table-wrapper :thead="$params->thead">
                        @forelse ($params->tbody as $customer)
                            @php
                                $sn = $loop->iteration;
                                $item = (object) $customer;
                                // Split address into maximum 3 words per line
                                $address_parts = preg_split('/\s+/', $item->address, 7);
                                $address_lines = array_chunk($address_parts, 3);
                            @endphp
                            <tr>
                                <x-table.table-checkbox :sn="$sn" :item="$item" />
                                {{-- Display customer details --}}
                                <td style="font-size: 12px;">{{ $sn }}</td>
                                <td style="font-size: 12px;">{{ $item->branch->name ?? __('financial.labels.not_available')}}</td>
                                <td style="font-size: 12px;">{{ $item->name }}</td>
                                <td style="font-size: 12px;">{!! $Bh::email_f($item->email) !!}</td>
                                <td style="font-size: 12px;">{{ $item->phone }}</td>
                                <td style="font-size: 12px;">
                                    @foreach ($address_lines as $line)
                                        {{ implode(' ', $line) }}<br>
                                    @endforeach
                                </td>
                                <td style="font-size: 12px;">{{ $IETs::getName($item->income_expense_type_id) }}</td>
                                <td style="font-size: 12px;">{{ $item->terms_of_payment }}</td>
{{--                                <td style="font-size: 12px;">{!! $Bh::date_f($item->created_at) !!}</td>--}}
                                {{-- Edit and Delete actions --}}
                                <td>
                                    <a href="{{ route('customers.edit', $item->id) }}" class="btn btn-sm btn-warning">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                </td>
                                <td>
                                    <button type="submit" form="delete-customer-{{ $item->id }}" class="btn btn-sm btn-danger">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <x-table.table-no-records />
                                </td>
                            </tr>
                        @endforelse
                    </x-table.table-wrapper>
                    {{-- --}}
                    <x-table.table-pagination-with-selected />
                </form>
            </div>
        </div>
    </div>
@endsection
