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
                                // Format numeric values as currency with commas
                                $basic_salary = number_format($item->basic_salary, 2);
                                $housing_allowance = number_format($item->housing_allowance, 2);
                                $transport_allowance = number_format($item->transport_allowance, 2);
                                $medical_allowance = number_format($item->medical_allowance, 2);
                                $other_allowance = number_format($item->other_allowance, 2);
                                $gross_salary = number_format($item->gross_salary, 2);
                                $deduction_payee = number_format($item->deduction_payee, 2) . ' %'; // Add percentage symbol
                                $pension = number_format($item->pension, 2) . ' %'; // Add percentage symbol
                                $net_pay = number_format($item->net_pay, 2);
                            @endphp
                            <tr>
                                <x-table.table-checkbox :sn="$sn" :item="$item" />
                                <td style="font-size: 11px;">{{ $sn }}</td>
                                <td style="font-size: 11px;">{{ $item->branch->name ?? 'N/A' }}</td>
                                <td style="font-size: 11px;">{{ $item->employee }}</td>
                                <td style="font-size: 11px;">₦{{ $basic_salary }}</td>
                                <td style="font-size: 11px;">₦{{ $housing_allowance }}</td>
                                <td style="font-size: 11px;">₦{{ $transport_allowance }}</td>
                                <td style="font-size: 11px;">₦{{ $medical_allowance }}</td>
                                <td style="font-size: 11px;">₦{{ $other_allowance }}</td>
                                <td style="font-size: 11px;">₦{{ $gross_salary }}</td>
                                <td style="font-size: 11px;">{{ $deduction_payee }}</td>
                                <td style="font-size: 11px;">{{ $pension }}</td>
                                <td style="font-size: 11px;">₦{{ $net_pay }}</td>
                                {{-- <td style="font-size: 11px;">{!! $Bh::date_f($item->created_at) !!}</td> --}}
                                <td>
                                    <a href="{{ route('payrolls.edit', $item->id) }}" class="btn btn-sm btn-warning">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                </td>
                                <td>
                                    <form action="{{ route('payrolls.destroy', $item->id) }}" method="POST">
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
                                <td colspan="14">
                                    <x-table.table-no-records />
                                </td>
                            </tr>
                        @endforelse
                        {{-- Add a row for the totals --}}
                    </x-table.table-wrapper>
                    <x-table.table-pagination-with-selected />
                </form>
            </div>
        </div>
    </div>
@endsection
