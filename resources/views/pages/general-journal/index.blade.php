<!-- resources/views/general-journal/index.blade.php -->
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
                                <td style="font-size: 12px;">{{ $item->branch->name ?? 'N/A' }}</td>
                                <td style="font-size: 12px;">{{ $item->debitAccount->name ?? 'N/A' }}</td>
                                <td style="font-size: 12px;">₦{{ number_format($item->debit,2) }}</td>
                                <td style="font-size: 12px;">{{ $item->creditAccount->name ?? 'N/A' }}</td>
                                <td style="font-size: 12px;">₦{{ number_format($item->credit,2) }}</td>
                                <td style="font-size: 12px;">{{ $item->memo }}</td>
                                <td style="font-size: 12px;">{{ $item->customer_vendor }}</td>
                                <td style="font-size: 12px;">{!! $Bh::date_f($item->created_at) !!}</td>
                                <td>
                                    <a href="{{ route('general-journal.edit', $item->id) }}" class="btn btn-sm btn-warning">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                </td>
                                <td>
                                    <form action="{{ route('general-journal.destroy', $item->id) }}" method="POST">
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
                                <td colspan="11">
                                    <x-table.table-no-records />
                                </td>
                            </tr>
                        @endforelse
                            <tr>
                                <td colspan="4" style="text-align: right;">{{ __('Total') }}</td>
                                <td>₦{{ number_format($tbody->sum('debit'), 2) }}</td>
                                <td colspan="1"></td>
                                <td>₦{{ number_format($tbody->sum('credit'), 2) }}</td>
                            </tr>
                    </x-table.table-wrapper>
                    <x-table.table-pagination-with-selected />
                </form>
            </div>
        </div>
    </div>
@endsection
