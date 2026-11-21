@extends('layouts.pdf')

@section('title')
    {{ $extra['module_name']  }}
@endsection

@push('include-css')
    <link rel="stylesheet" href="{{ asset('asset/css/only-voucher.css') }}">
@endpush

@section('content')

    <?php
    $transaction = new \App\Transaction();
    ?>
    <p>{{ __('Printing Date & Time') }}: {{ $extra['current_date_time']  }}</p>

    <div>
        <div class="f-l-W-600">
            <img class="width-60 height-60 margin-top-20"
                 src="{{ asset( config('settings.company_logo')) }}"
                 alt="">
        </div>
        <br>
        <div class="f-r width-200 margin-bottom-10">
            <p class="text-a-c border-t-1 border-l-1 border-r-1 line-h-20">
                {{ __('financial.labels.voucher_number') }} <br></p>
            <p class="text-a-c border-1 line-h-20">{{ str_pad($items[0]->voucher_no, 4, '0', STR_PAD_LEFT)  }}</p>
        </div>
    </div>
    <br><br><br>
    <div class="border-b-1">
        <h4 class="text-a-c font-w-b">{{ config('settings.company_name')  }}</h4>
        <h5 class="text-a-c">{{ config('settings.address_1')  }}</h5>
    </div>
    <h4 class="text-a-c margin-top-20">{{ $extra['voucher_type'] }}</h4>
    <table class="table table-bordered table-sm">
        <tbody>
        <tr>
            <td>{{ __('financial.labels.branch_name') }}: {{ App\Transaction::find($items[0]->id)->Branch->name }} ( {{ __('Dr') }} )</td>
            <td>{{ __('financial.labels.branch_name') }}: {{ App\Transaction::find($items[1]->id)->Branch->name }} ( {{ __('Cr') }} )</td>
            <td>{{ __('Date') }}: {{  date(config('settings.date_format'), strtotime($items[0]->voucher_date))  }}</td>
        </tr>
        </tbody>
    </table>

    <table class="table table-bordered table-sm">
        <thead>
        <tr>
            <th scope="col" class="text-center">{{ __('SL.NO') }}</th>
            <th scope="col">{{ __('financial.labels.head_of_account') }}</th>

            <th scope="col">{{ __('Debit') }} ( <?php echo (config('settings.is_code') == 'code') ?
                    config('settings.currency_code') : config('settings.currency_symbol')  ?>
                )
            </th>
            <th scope="col">{{ __('Credit') }} ( <?php echo (config('settings.is_code') == 'code') ?
                    config('settings.currency_code') : config('settings.currency_symbol')  ?>
                )
            </th>


        </tr>
        </thead>
        <tbody>

        <?php $dr_total_amount = 0; ?>
        <?php $cr_total_amount = 0; ?>

        @foreach($items as $key=>$item)

            <?php
            $dr_total_amount += $item->dr;

            $cr_total_amount += $item->cr;
            ?>

            <tr>
                <th scope="row" class="text-center">{{ $key+1 }}</th>
                <td>{{ App\Transaction::find($item->id)->IncomeExpenseHead->name  }}</td>
                <td>{{  $transaction->convert_money_format($item->dr) }}</td>
                <td>{{  $transaction->convert_money_format($item->cr) }}</td>
            </tr>
        @endforeach

        <tr>
            <th scope="row" colspan="2" class="text-right">{{ __('Total') }} =</th>
            <th>{{ $transaction->convert_money_format($dr_total_amount)  }} /=</th>
            <th>{{ $transaction->convert_money_format($cr_total_amount)  }} /=</th>
        </tr>

        </tbody>
    </table>
    <p>{{ __('financial.labels.amount_in_words') }}: <span class="font-w-b">{{ $transaction->convert_number_to_words($cr_total_amount) }} {!! config('settings.currency_code') !!} </span>
        only.
    </p>
    @if(!empty($items[0]->particulars))
        <br>
        <p>{{ __('financial.labels.particulars') }}: <span class="font-w-b"> {{ $items[0]->particulars }}</span></p>
    @endif
    @if(count($items)==2)
        <br><br><br>
    @endif

    @if(count($items)==3)
        <br>
    @endif
    @if(count($items) >= 4)
        <br><br><br><br>
    @endif

    <table class="table">
        <tr>
            <td class="text-center border-n">
                - - - - -<br>
                {{ __('Prepared by') }}
            </td>
            <td class="text-center border-n">
                - - - - -<br>
                {{ __('Checked by') }}
            </td>
            <td class="text-center border-n">
                - - - - -<br>
                {{ __('Forward by') }}
            </td>
            <td class="text-center border-n">
                - - - - -<br>
                {{ __('Approved by') }}
            </td>
        </tr>
    </table>


@stop


