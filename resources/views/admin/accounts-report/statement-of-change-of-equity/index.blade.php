@extends('layouts.app')

@section('title')
Statement of Changes in Equity
@stop

@section('top-bar')
@include('shared.header')
@stop
@section('left-sidebar')
@include('shared.sidebar')
@stop
@section('content')
<section @if($is_rtl) dir="rtl" @endif class="content report-selection-page">
    <div class="header" style="background-color: transparent !important;">
        <h2 class="text-center">{{ __('financial.terms.statement_of_changes_in_equity') }}</h2>
        <br>
    </div>
    <div class="container-fluid">
        <!-- Inline Layout | With Floating Label -->
        <div class="row clearfix">
            <!-- Branch Wise Start -->
            <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 @if($is_rtl) float-r @endif">
                <div class="card">
                    <div class="header bg-cyan">
                        <h2>
                            {{ __('Branch Wise Report') }}
                            <small>{{ __('Track equity movements and changes') }}</small>
                        </h2>
                    </div>
                    <br>
                    <div class="body">
                        <div class="row clearfix">
                            <form class="form" id="form_validation" method="post"
                                action="{{ route('reports.accounts.statement_of_changes_in_equity.report') }}">
                                {{ csrf_field() }}
                                <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                    <div class="form-group form-float">
                                        <div class="form-line">
                                            <select data-live-search="true" class="form-control show-tick"
                                                name="branch_id">
                                                <option value="">{{ __('financial.labels.all_branches') }}</option>
                                                @foreach ($branches as $Branch)
                                                <option value="{{ $Branch->id }}">{{ $Branch->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                    <h2 class="card-inside-title">{{ __('Period Start') }}</h2>
                                    <div class="input-daterange input-group" id="bs_datepicker_range_container">
                                        <div class="form-line">
                                            <input autocomplete="off" name="start_from" type="text" class="form-control"
                                                placeholder="{{ __('Start Date...') }}">
                                        </div>
                                        <span class="input-group-addon">{{ __('financial.reports.to') }}</span>
                                        <div class="form-line">
                                            <input autocomplete="off" name="start_to" type="text" class="form-control"
                                                placeholder="{{ __('End Date...') }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                    <h2 class="card-inside-title">{{ __('Period End') }}</h2>
                                    <div class="input-daterange input-group" id="bs_datepicker_range_container1">
                                        <div class="form-line">
                                            <input autocomplete="off" name="end_from" type="text" class="form-control"
                                                placeholder="{{ __('Start Date...') }}">
                                        </div>
                                        <span class="input-group-addon">{{ __('financial.reports.to') }}</span>
                                        <div class="form-line">
                                            <input autocomplete="off" name="end_to" type="text" class="form-control"
                                                placeholder="{{ __('End Date...') }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="form-line">
                                        <input formtarget="_blank" name="action" value="Show" type="submit"
                                            class="btn btn-primary m-t-15 waves-effect">
                                        <input name="action" value="Pdf" type="submit"
                                            class="btn btn-primary m-t-15 waves-effect">
                                        <input name="action" value="Excel" type="submit"
                                            class="btn btn-primary m-t-15 waves-effect">
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Branch Wise End -->
        </div>
    </div>
</section>
@stop

@push('include-css')
<!-- Bootstrap Select Css -->
<link href="{{ asset('asset/plugins/bootstrap-select/css/bootstrap-select.css') }}" rel="stylesheet" />
<!-- Bootstrap DatePicker Css -->
<link href="{{ asset('asset/plugins/bootstrap-datepicker/css/bootstrap-datepicker.css') }}" rel="stylesheet" />
@endpush

@push('include-js')
<!-- Moment Plugin Js -->
<script src="{{ asset('asset/plugins/momentjs/moment.js') }}"></script>
<!-- Bootstrap Datepicker Plugin Js -->
<script src="{{ asset('asset/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js') }}"></script>
<!-- Autosize Plugin Js -->
<script src="{{ asset('asset/plugins/autosize/autosize.js') }}"></script>
<script src="{{ asset('asset/js/pages/forms/basic-form-elements.js') }}"></script>
<script>
    @if (Session::has('success'))
            toastr["success"]('{{ __(Session::get('success')) }}');
        @endif
        @if (Session::has('error'))
            toastr["error"]('{{ __(Session::get('error')) }}');
        @endif
        @if ($errors->any())
            @foreach ($errors->all() as $error)
                toastr["error"]('{{ $error }}');
            @endforeach
        @endif
</script>
@endpush
