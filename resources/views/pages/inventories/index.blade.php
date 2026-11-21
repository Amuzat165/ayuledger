<!-- resources/views/inventories/index.blade.php -->
@extends('layouts.table-layout')

@inject('Bh', 'App\Helpers\BladeHelper')

@section('title', __('financial.screens.inventory_list'))

@section('container')
    <x-breadcrumb :level="__('financial.terms.inventory')" :location="__('financial.screens.inventory_list')" :as="__('financial.labels.all')" />
    <div class="row">
        <div class="col-xs-12">
            <div class="card">
                <x-table.table-search-bar :all="count($inventories)" :trash="0" />
                <form class="actionForm" action="" method="get">
                    @csrf
                    <x-table.table-pagination-with-selected />
                    <x-table.table-wrapper :thead="['#', __('financial.labels.branch'), __('financial.screens.inventory_type'), __('financial.terms.description'), __('financial.screens.sub_item'), __('financial.screens.unit_of_measurement'), __('financial.screens.unit_cost'), __('financial.screens.sale_price'), __('financial.terms.quantity'), __('financial.screens.stock_status'), __('financial.terms.account'), __('root.common.edit'), __('root.common.delete')]">
                        @forelse ($inventories as $inventory)
                            @php
                                $sn = $loop->iteration;
                                $unit_cost  = number_format($inventory->unit_cost , 2);
                                $sale_price  = number_format($inventory->sale_price , 2);
                            @endphp
                            <tr>
                                <x-table.table-checkbox :sn="$sn" :item="$inventory" />
                                <td style="font-size: 11px;">{{ $sn }}</td>
                                <td style="font-size: 11px;">{{ $inventory->branch->name ?? 'N/A' }}</td>
                                <td style="font-size: 11px;">{{ $inventory->type->name ?? 'N/A' }}</td>
                                <td style="font-size: 11px;">{{ $inventory->description }}</td>
                                <td style="font-size: 11px;">{{ $inventory->sub_item }}</td>
                                <td style="font-size: 11px;">{{ $inventory->unit_of_measurement }}</td>
                                <td style="font-size: 11px;">₦{{ $unit_cost  }}</td>
                                <td style="font-size: 11px;">₦{{ $sale_price }}</td>
                                <td style="font-size: 11px;">{{ $inventory->quantity_at_hand }}</td>
                                <td style="font-size: 11px;">
                                    @if ($inventory->quantity_at_hand <= 0)
                                        <span class="label label-danger">{{ __('financial.screens.out_of_stock') }}</span>
                                    @elseif ($inventory->quantity_at_hand <= $inventory->reorder_level)
                                        <span class="label label-warning">{{ __('financial.screens.reorder_status') }}</span>
                                    @else
                                        <span class="label label-success">{{ __('financial.screens.in_stock') }}</span>
                                    @endif
                                </td>
                                <td style="font-size: 11px;">{{ $inventory->account }}</td>
{{--                                <td style="font-size: 11px;">{!! $Bh::date_f($inventory->created_at) !!}</td>--}}
                                <td>
                                    <a href="{{ route('inventories.edit', $inventory->id) }}" class="btn btn-sm btn-warning">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                </td>
                                <td>
                                    <form action="{{ route('inventories.destroy', $inventory->id) }}" method="POST">
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
                                <td colspan="12">
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
