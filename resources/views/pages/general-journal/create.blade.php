@extends('layouts.app')

@section('title', $params->title)

@section('top-bar')
    @include('shared.header')
@stop

@section('left-sidebar')
    @include('shared.sidebar')
@stop

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="block-header">
                <a class="btn btn-sm btn-info waves-effect" href="{{ route('general-journal.index') }}">{{ __('Back') }}</a>
            </div>

            <ol class="breadcrumb breadcrumb-col-cyan pull-right">
                <li><a href="{{ route('dashboard') }}"><i class="material-icons">home</i> {{ __('Home') }}</a></li>
                <li><a href="{{ route('general-journal.index') }}"><i class="fas fa-archive"></i> {{ __('General Journal') }}</a></li>
                <li class="active"><i class="material-icons">archive</i> {{ $params->title }}</li>
            </ol>

            <div class="row clearfix">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="card">
                        <div class="header">
                            <h2>{{ $params->title }}</h2>
                            <small>{{ __('Enter :value Information', ['value' => $params->resource]) }}</small>
                        </div>
                        <div class="body">
                            <form method="POST" action="{{ $params->action }}" class="form2">
                                @csrf
                                <table class="table table-bordered">
                                    <tbody>
                                    <tr>
                                        <td><label for="account">{{ __('Branch') }}</label></td>
                                        <td>
                                            <select id="branch_id" class="form-control" name="branch_id" required>
                                                <option value="">{{ __('Select Branch') }}</option>
                                                @foreach($params->branches as $branchesId => $branchesName)
                                                    <option value="{{ $branchesId }}">{{ $branchesName }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="account">{{ __('Account') }}</label></td>
                                        <td>
                                            <select id="debit_account_id" name="debit_account_id" class="form-control" required>
                                                <option value="" disabled selected>{{ __('Select Debit Account') }}</option>
                                                @foreach($params->accounts as $accountId => $accountName)
                                                    <option value="{{ $accountId }}">{{ $accountName }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="debit">{{ __('Debit') }}</label></td>
                                        <td>
                                            <input id="debit" name="debit" type="number" class="form-control"
                                                   value="{{ old('debit', $params->defaults['debit'] ?? '') }}" required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="account">{{ __('Account') }}</label></td>
                                        <td>
                                            <select id="credit_account_id" name="credit_account_id" class="form-control" required>
                                                <option value="" disabled selected>{{ __('Select Credit Account') }}</option>
                                                @foreach($params->accounts as $accountId => $accountName)
                                                    <option value="{{ $accountId }}">{{ $accountName }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="credit">{{ __('Credit') }}</label></td>
                                        <td>
                                            <input id="credit" name="credit" type="number" class="form-control"
                                                   value="{{ old('credit', $params->defaults['credit'] ?? '') }}" required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="memo">{{ __('Memo') }}</label></td>
                                        <td>
                                            <input id="memo" name="memo" class="form-control" required
                                                   value="{{ old('memo', $params->defaults['memo'] ?? '') }}">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="customer_vendor">{{ __('Customer/Vendor') }}</label></td>
                                        <td>
                                            <select id="customer_vendor" name="customer_vendor" class="form-control" required>
                                                <option value="">{{ __('Select Customer/Vendor') }}</option>
                                                @foreach ($params->customers as $customer)
                                                    <option value="{{ $customer->name }}">{{ $customer->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <button type="submit" class="btn btn-primary m-t-15 waves-effect">
                                                {{ __('root.common.save') }}
                                            </button>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </form>
                            @if(session('success'))
                                <div class="alert alert-success">
                                    {{ __(session('success')) }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@stop

@push('include-css')
    <!-- Colorpicker Css -->
    <link href="{{ asset('asset/plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.css') }}" rel="stylesheet" />

    <!-- Dropzone Css -->
    <link href="{{ asset('asset/plugins/dropzone/dropzone.css') }}" rel="stylesheet">

    <!-- Multi Select Css -->
    <link href="{{ asset('asset/plugins/multi-select/css/multi-select.css') }}" rel="stylesheet">

    <!-- Bootstrap Spinner Css -->
    <link href="{{ asset('asset/plugins/jquery-spinner/css/bootstrap-spinner.css') }}" rel="stylesheet">

    <!-- Bootstrap Tagsinput Css -->
    <link href="{{ asset('asset/plugins/bootstrap-tagsinput/bootstrap-tagsinput.css') }}" rel="stylesheet">

    <!-- Bootstrap Select Css -->
    <link href="{{ asset('asset/plugins/bootstrap-select/css/bootstrap-select.css') }}" rel="stylesheet" />

    <!-- Bootstrap Material Datetime Picker Css -->
    <link href="{{ asset('asset/plugins/bootstrap-material-datetimepicker/css/bootstrap-material-datetimepicker.css') }}"
          rel="stylesheet" />

    <!-- Bootstrap DatePicker Css -->
    <link href="{{ asset('asset/plugins/bootstrap-datepicker/css/bootstrap-datepicker.css') }}" rel="stylesheet" />

    <!-- noUISlider Css -->
    <link href="{{ asset('asset/plugins/nouislider/nouislider.min.css') }}" rel="stylesheet" />

    <!-- Sweet Alert Css -->
    <link href="{{ asset('asset/plugins/sweetalert/sweetalert.css') }}" rel="stylesheet" />
@endpush

@push('include-js')
@endpush
