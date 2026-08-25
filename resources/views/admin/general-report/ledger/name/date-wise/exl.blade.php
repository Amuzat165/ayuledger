@extends('layouts.pdf')

@section('title')
    {{ $extra['module_name'] }}
@endsection

@section('content')
    <div class="mid">
        <div class="row">
            <div class="col-md-12 col-sm-12 col-lg-12 col-xl-12">
                <table class="table table-bordered table-sm table-hover">
                    <thead>
                    <tr>
                        <th class="text-center">Sl.No</th>
                        <th>Account ID</th>
                        <th>Account Name</th>
                        <th>Transaction Date</th>
                        <th>Reference</th>
                        <th>Journal</th>
                        <th>Description</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Credit</th>
                        <th class="text-right">Balance</th>
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
                            <td colspan="10" class="text-center">No data available</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop
