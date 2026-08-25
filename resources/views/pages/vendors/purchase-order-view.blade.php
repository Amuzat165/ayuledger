@extends('layouts.table-layout')

@inject('Bh', 'App\Helpers\BladeHelper')

@section('title', $title)

@section('container')
    <x-breadcrumb :level="$resource" :location="$title" :as="$type" />
    <div class="row">
        <div class="col-xs-12">
            <div class="card">
                <x-table.table-search-bar :all="count($vendorInvoices)" :trash="0" />
                <form class="actionForm" action="" method="get">
                    @csrf
                    <x-table.table-pagination-with-selected />
                    <x-table.table-wrapper :thead="['#', 'Invoice Number', 'Vendor', 'Invoice Date', 'Total Amount']">
                        @forelse ($vendorInvoices as $invoice)
                            @php
                                $sn = $loop->iteration;
                                $total_amount = number_format($invoice->total_amount, 2);
                            @endphp
                            <tr>
                                <x-table.table-checkbox :sn="$sn" :item="$invoice" />
                                <td style="font-size: 12px;">{{ $sn }}</td>
                                <td style="font-size: 12px;">{{ $invoice->invoice_number }}</td>
                                <td style="font-size: 12px;">{{ $invoice->vendor->name }}</td>
                                <td style="font-size: 12px;">{{$invoice->created_at}}</td>
                                <td style="font-size: 12px;">₦{{ $total_amount }}</td>
{{--                                <td>--}}
{{--                                    <a href="{{ route('vouchers.show', $invoice->id) }}" class="btn btn-sm btn-primary">--}}
{{--                                        <i class="fa fa-eye"></i>--}}
{{--                                    </a>--}}
{{--                                    <a href="{{ route('vouchers.edit', $invoice->id) }}" class="btn btn-sm btn-warning">--}}
{{--                                        <i class="fa fa-edit"></i>--}}
{{--                                    </a>--}}
{{--                                    <form action="{{ route('vouchers.destroy', $invoice->id) }}" method="POST" style="display:inline;">--}}
{{--                                        @csrf--}}
{{--                                        @method('DELETE')--}}
{{--                                        <button type="submit" class="btn btn-sm btn-danger">--}}
{{--                                            <i class="fa fa-trash"></i>--}}
{{--                                        </button>--}}
{{--                                    </form>--}}
{{--                                </td>--}}
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
