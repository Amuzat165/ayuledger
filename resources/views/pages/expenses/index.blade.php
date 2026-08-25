<!-- resources/views/expenses/index.blade.php -->
@extends('layouts.table-layout')

@inject('Bh', 'App\Helpers\BladeHelper')

@section('title', 'Expense List')

@section('container')
    <x-breadcrumb :level="'Expenses'" :location="'Expense List'" :as="'Index'" />
    <div class="row">
        <div class="col-xs-12">
            <div class="card">
                <x-table.table-search-bar :all="count($expenses)" :trash="0" />
                <form class="actionForm" action="" method="get">
                    @csrf
                    <x-table.table-pagination-with-selected />
                    <x-table.table-wrapper :thead="['#', 'Branch','Account','Payment Mode', 'Vendor', 'Expense Date', 'Category', 'Amount', 'Description','Edit','Delete']">
                        @forelse ($expenses as $expense)
                            @php
                                $sn = $loop->iteration;
                                $amount = number_format($expense->amount, 2);
                            @endphp
                            <tr>
                                <x-table.table-checkbox :sn="$sn" :item="$expense" />
                                <td style="font-size: 12px;">{{ $sn }}</td>
                                <td style="font-size: 11px;">{{ $expense->branch->name ?? 'N/A' }}</td>
                                <td style="font-size: 12px;">{{ $expense->account }}</td>
                                <td style="font-size: 12px;">{{ $expense->bankCash->name ?? 'N/A' }}</td>
                                <td style="font-size: 12px;">{{ $expense->vendor }}</td>
                                <td style="font-size: 12px;">{{ $expense->expense_date }}</td>
                                <td style="font-size: 12px;">{{ $expense->type->name }}</td>
                                <td style="font-size: 12px;">₦{{ $amount }}</td>
                                <td style="font-size: 12px;">{{ \Illuminate\Support\Str::words($expense->description, 3) }}</td>
                                <td>
                                    <a href="{{ route('expenses.edit', $expense->id) }}" class="btn btn-sm btn-warning">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                </td>
                                <td>
                                    <form action="{{ route('expenses.destroy', $expense->id) }}" method="POST">
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
