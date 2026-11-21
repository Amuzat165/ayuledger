@extends('layouts.app')

@section('title')
{{ config('settings.company_name') }} -> Voucher
@stop
@section('top-bar')
@include('shared.header')
@stop
@section('left-sidebar')
@include('shared.sidebar')
@stop
@section('content')
<section @if ($is_rtl) dir="rtl" @endif class="content">
    <div class="header" style="background-color: transparent !important;">
        <h2 class="text-center">{{ __('financial.screens.voucher') }}</h2>
    </div>
    <div class="container-fluid">
        <!-- Inline Layout | With Floating Label -->
        <div class="row clearfix">
            <!-- Credit Voucher Wise Start -->
            <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6 @if ($is_rtl) float-r @endif">
                <div class="card">
                    <div class="body">
                        <div class="row clearfix">
                            <form class="form" id="form_validation" method="post"
                                action="{{ route('reports.general.voucher.report') }}">
                                {{ csrf_field() }}
                                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                                        <div class="form-group form-float">
                                                                            <label>{{ __('financial.labels.branch') }}</label>
                                                                            <div class="form-line">
                                                                                <select class="form-control show-tick" name="branch_id">
                                                                                    <option value="">{{ __('financial.labels.all_branches') }}</option>
                                                                                    @foreach ($branches as $branch)
                                                                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                                                                    @endforeach
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                    <div class="form-group form-float">
                                        <div class="form-line">
                                            <select data-live-search="true" class="form-control show-tick"
                                                name="voucher_type">
                                                <option value="">{{ __('financial.labels.select_voucher_type') }}</option>
                                                <option value="CV">{{ __('financial.screens.credit_voucher') }}</option>
                                                <option value="DV">{{ __('financial.screens.debit_voucher') }}</option>
                                                <option value="JV">{{ __('financial.screens.journal_voucher') }}</option>
                                                <option value="Contra">{{ __('financial.screens.contra_voucher') }}</option>
                                                <option value="IBCBV">{{ __('financial.labels.initial_bank_cash') }}</option>
                                                <option value="IIEHBV">{{ __('financial.labels.initial_ledger') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="input-daterange input-group" id="bs_datepicker_range_container2">
                                        <div class="form-line">
                                            <input autocomplete="off" name="from" type="text" class="form-control"
                                                placeholder="{{ __('Date start...') }}">
                                        </div>
                                        <span class="input-group-addon">{{ __('financial.reports.to') }}</span>
                                        <div class="form-line">
                                            <input autocomplete="off" name="to" type="text" class="form-control"
                                                placeholder="{{ __('Date end...') }}">
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
            <!-- Credit Voucher Wise End -->
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
<!-- Moment Plugin Js -->
<script src="{{ asset('asset/plugins/momentjs/moment.js') }}"></script>
<!-- Bootstrap Material Datetime Picker Plugin Js -->
<script src="{{ asset('asset/plugins/bootstrap-material-datetimepicker/js/bootstrap-material-datetimepicker.js') }}">
</script>
<!-- Bootstrap Datepicker Plugin Js -->
<script src="{{ asset('asset/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js') }}"></script>
<!-- Sweet Alert Plugin Js -->
<script src="{{ asset('asset/plugins/sweetalert/sweetalert.min.js') }}"></script>
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
