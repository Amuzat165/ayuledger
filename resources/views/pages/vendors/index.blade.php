@extends('layouts.table-layout')

@inject('Bh', 'App\Helpers\BladeHelper')

@section('title', $title)

@section('container')
    <x-breadcrumb :level="$resource" :location="$title" :as="$type" />
    <div class="row">
        <div class="col-xs-12">
            <div class="card">
                <x-table.table-search-bar :all="count($tbody)" :trash="0" />
                <form class="actionForm" action="" method="get">
                    @csrf
                    <x-table.table-pagination-with-selected />
                    <x-table.table-wrapper :thead="$thead">
                        @forelse ($tbody as $entry)
                            @php
                                $sn = $loop->iteration;
                                $item = (object) $entry;
                            @endphp
                            <tr>
                                <x-table.table-checkbox :sn="$sn" :item="$item" />
                                <td style="font-size: 12px;">{{ $sn }}</td>
                                <td style="font-size: 12px;">{{ $item->reg_number }}</td>
                                <td style="font-size: 12px;">{{ $item->branch->name ?? __('financial.labels.not_available')  }}</td>
                                <td style="font-size: 12px;">{{ $item->name }}</td>
                                <td style="font-size: 12px;">{{ $item->address }}</td>
                                <td style="font-size: 12px;">{{ $item->phone_number }}</td>
                                <td style="font-size: 12px;">{{ $item->account }}</td>
                                <td style="font-size: 12px;">{{ $item->terms_of_payment }}</td>
{{--                                <td style="font-size: 12px;">{!! $Bh::date_f($item->created_at) !!}</td>--}}
                                <td>
                                    <a href="{{ route('vendors.edit', $item->id) }}" class="btn btn-sm btn-warning">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                </td>
                                <td>
                                    <form action="{{ route('vendors.destroy', $item->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </form>
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
                    <x-table.table-pagination-with-selected />
                </form>
            </div>
        </div>
    </div>
@endsection
