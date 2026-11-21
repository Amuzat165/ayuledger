@extends('layouts.app')

{{--Important Variables--}}

<?php

$moduleName = __('root.settings.general');
$createItemName = __('root.common.update') . ' ' . $moduleName;

$breadcrumbMainName = $moduleName;
$breadcrumbCurrentName = ' ' . __('root.common.update');

$breadcrumbMainIcon = "fas fa-project-diagram";
$breadcrumbCurrentIcon = "archive";

$ModelName = 'App\Setting';
$ParentRouteName = 'settings.general';

?>

@section('title')
{{ $moduleName }}->{{ $createItemName }}
@stop

@section('top-bar')
@include('shared.header')
@stop
@section('left-sidebar')
@include('shared.sidebar')
@stop
@section('content')
<section @if($is_rtl) dir="rtl" @endif class="content">
    <div class="container-fluid general-settings-page">
        <ol class="breadcrumb breadcrumb-col-cyan @if ($is_rtl) pull-left  @else pull-right @endif">
            <li><a href="{{ route('dashboard') }}"><i class="material-icons">home</i> {{ __('root.common.home') }}</a>
            </li>
            <li><a href="{{ route($ParentRouteName) }}"> <i class="material-icons">settings</i>&nbsp;{{
                    $breadcrumbMainName }}</a>
            </li>
            <li class="active"><i class="material-icons">{{ $breadcrumbCurrentIcon }}</i> {{ $breadcrumbCurrentName }}
            </li>
        </ol>
        <header class="general-settings-hero">
            <div class="general-settings-hero-icon"><i class="material-icons">tune</i></div>
            <div>
                <p class="general-settings-eyebrow">{{ __('root.settings.general') }}</p>
                <h1>{{ $createItemName }}</h1>
                <p>Manage your business profile, document branding, and tax preferences.</p>
            </div>
        </header>

        <form enctype="multipart/form-data" class="form general-settings-form" id="form_validation" method="post"
            action="{{ route($ParentRouteName.'.update') }}">
            {{ csrf_field() }}

            <section class="general-settings-card">
                <div class="general-settings-card-heading">
                    <span class="general-settings-section-icon"><i class="material-icons">business</i></span>
                    <div><h2>Business profile</h2><p>Contact details used across your account and business documents.</p></div>
                </div>
                <div class="general-settings-grid">
                    <div class="general-settings-field general-settings-field-wide">
                        <label for="company_name">{{ __('ui.settings.company_name') }}</label>
                        <input id="company_name" value="{{ $settings['company_name'] }}" name="company_name" type="text" class="form-control">
                    </div>
                    <div class="general-settings-field">
                        <label for="contract_person">{{ __('ui.settings.contact_person') }}</label>
                        <input id="contract_person" value="{{ $settings['contract_person'] }}" name="contract_person" type="text" class="form-control">
                    </div>
                    <div class="general-settings-field">
                        <label for="email">{{ __('ui.settings.email') }}</label>
                        <input id="email" value="{{ $settings['email'] }}" name="email" type="email" class="form-control">
                    </div>
                    <div class="general-settings-field">
                        <label for="phone">{{ __('ui.settings.phone') }}</label>
                        <input id="phone" value="{{ $settings['phone'] }}" name="phone" type="text" class="form-control">
                    </div>
                    <div class="general-settings-field general-settings-field-wide">
                        <label for="address_1">{{ __('ui.settings.address_line_1') }}</label>
                        <input id="address_1" value="{{ $settings['address_1'] }}" name="address_1" type="text" class="form-control">
                    </div>
                    <div class="general-settings-field general-settings-field-wide">
                        <label for="address_2">{{ __('ui.settings.address_line_2') }}</label>
                        <input id="address_2" value="{{ $settings['address_2'] }}" name="address_2" type="text" class="form-control">
                    </div>
                    <div class="general-settings-field">
                        <label for="city">{{ __('ui.settings.city') }}</label>
                        <input id="city" value="{{ $settings['city'] }}" name="city" type="text" class="form-control">
                    </div>
                    <div class="general-settings-field">
                        <label for="state">{{ __('ui.settings.state') }}</label>
                        <input id="state" value="{{ $settings['state'] }}" name="state" type="text" class="form-control">
                    </div>
                    <div class="general-settings-field">
                        <label for="zip_code">{{ __('ui.settings.zip_code') }}</label>
                        <input id="zip_code" value="{{ $settings['zip_code'] }}" name="zip_code" type="text" class="form-control">
                    </div>
                </div>
            </section>

            <section class="general-settings-card">
                <div class="general-settings-card-heading">
                    <span class="general-settings-section-icon"><i class="material-icons">image</i></span>
                    <div><h2>Business branding</h2><p>Choose the images shown on your business documents.</p></div>
                </div>
                <div class="general-settings-grid general-settings-branding-grid">
                    <div class="general-settings-upload-card">
                        <label class="general-settings-upload-label" for="company_logo">{{ __('ui.settings.company_logo') }}</label>
                        <div class="general-settings-image-preview general-settings-logo-preview">
                            <img src="{{ asset($settings['company_logo'] ?? 'upload/company-logo/e.png') }}" alt="{{ __('ui.settings.company_logo') }}">
                        </div>
                        <input id="company_logo" name="company_logo" type="file" class="form-control" accept="image/*">
                        <small>Upload a clear logo image. Supported image formats are accepted.</small>
                    </div>
                    <div class="general-settings-upload-card">
                        <label class="general-settings-upload-label" for="header_image">{{ __('financial.labels.header_image') }}</label>
                        <div class="general-settings-image-preview general-settings-banner-preview">
                            @if(!empty($settings['header_image']))
                                <img class="settings-header-image-preview" src="{{ asset($settings['header_image']) }}" alt="{{ __('financial.labels.header_image') }}">
                            @else
                                <span><i class="material-icons">photo_size_select_actual</i>Header image preview</span>
                            @endif
                        </div>
                        <input id="header_image" name="header_image" type="file" class="form-control" accept="image/*">
                        <small>{{ __('financial.labels.header_image_usage') }}</small>
                    </div>
                </div>
            </section>

            <section class="general-settings-card">
                <div class="general-settings-card-heading">
                    <span class="general-settings-section-icon"><i class="material-icons">account_balance</i></span>
                    <div><h2>Tax and pricing</h2><p>Configure tax rates and customer invoice discount options.</p></div>
                </div>
                <div class="general-settings-grid">
                    <div class="general-settings-field">
                        <label for="vat_percentage">{{ __('financial.labels.vat_percentage') }}</label>
                        <input id="vat_percentage" value="{{ $settings['vat_percentage'] ?? 0 }}" name="vat_percentage" type="number" step="0.01" class="form-control">
                    </div>
                    <div class="general-settings-field">
                        <label for="wht_enabled">{{ __('financial.labels.wht_status') }}</label>
                        <select id="wht_enabled" name="wht_enabled" class="form-control" required>
                            <option value="0" {{ (string) old('wht_enabled', $settings['wht_enabled'] ? '1' : '0') === '0' ? 'selected' : '' }}>{{ __('financial.labels.disabled') }}</option>
                            <option value="1" {{ (string) old('wht_enabled', $settings['wht_enabled'] ? '1' : '0') === '1' ? 'selected' : '' }}>{{ __('financial.labels.enabled') }}</option>
                        </select>
                    </div>
                    <div class="general-settings-field">
                        <label for="wht_mode">{{ __('financial.labels.wht_mode') }}</label>
                        <select id="wht_mode" name="wht_mode" class="form-control" required>
                            <option value="manual" {{ old('wht_mode', $settings['wht_mode']) === 'manual' ? 'selected' : '' }}>{{ __('financial.labels.manual') }}</option>
                            <option value="rule_based" {{ old('wht_mode', $settings['wht_mode']) === 'rule_based' ? 'selected' : '' }}>{{ __('financial.labels.rule_based') }}</option>
                        </select>
                    </div>
                    <div class="general-settings-field">
                        <label for="wht_manual_rate">{{ __('financial.labels.manual_wht_rate') }}</label>
                        <input id="wht_manual_rate" value="{{ old('wht_manual_rate', $settings['wht_manual_rate']) }}" name="wht_manual_rate" type="number" min="0" max="100" step="0.01" class="form-control" required>
                        <small>{{ __('financial.labels.wht_manual_help') }}</small>
                    </div>
                    <div class="general-settings-field general-settings-field-wide general-settings-note">
                        <i class="material-icons">info_outline</i><span>{{ __('financial.labels.wht_rule_based_help') }} {{ __('financial.labels.wht_admin_rate_help') }}</span>
                    </div>
                    <div class="general-settings-field">
                        <label for="discount_enabled">{{ __('financial.labels.customer_invoice_discounts') }} — {{ __('financial.labels.status') }}</label>
                        <select id="discount_enabled" name="discount_enabled" class="form-control">
                            <option value="1" {{ (string) old('discount_enabled', $settings['discount_enabled'] ?? false) === '1' ? 'selected' : '' }}>{{ __('financial.labels.enabled') }}</option>
                            <option value="0" {{ (string) old('discount_enabled', $settings['discount_enabled'] ?? false) === '1' ? '' : 'selected' }}>{{ __('financial.labels.disabled') }}</option>
                        </select>
                    </div>
                    <div class="general-settings-field">
                        <label for="discount_percentage">{{ __('financial.labels.discount_percentage') }}</label>
                        <input id="discount_percentage" name="discount_percentage" type="number" min="0" max="100" step="0.01" class="form-control"
                            value="{{ old('discount_percentage', $settings['discount_percentage'] ?? '0.00') }}"
                            {{ (string) old('discount_enabled', $settings['discount_enabled'] ?? false) === '1' ? '' : 'disabled' }}>
                    </div>
                    <div class="general-settings-field general-settings-field-wide general-settings-color-field">
                        <label for="company_color">{{ __('ui.settings.company_color') }}</label>
                        <input id="company_color" value="{{ $settings['company_color'] ?? '#00897B' }}" name="company_color" type="color" class="form-control">
                    </div>
                </div>
            </section>

            <div class="general-settings-actions">
                <p><i class="material-icons">lock</i> Your settings are saved securely for this business.</p>
                <button type="submit" class="btn btn-primary waves-effect"><i class="material-icons">save</i>{{ __('root.common.update') }}</button>
            </div>
        </form>
    </div>
</section>

@stop

@push('include-css')
<style>
    .general-settings-page { max-width: 1440px; padding-bottom: 38px; color: #203044; }
    .general-settings-page .breadcrumb { margin-bottom: 18px; }
    .general-settings-hero { display: flex; align-items: center; gap: 17px; margin: 10px 0 22px; padding: 23px 26px; border: 1px solid #e2eaf0; border-radius: 12px; background: linear-gradient(115deg, #fff 0%, #f3f8fa 100%); box-shadow: 0 5px 18px rgba(27, 51, 71, .045); }
    .general-settings-hero-icon, .general-settings-section-icon { display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; width: 48px; height: 48px; border-radius: 12px; background: #e6f3f2; color: #087f78; }
    .general-settings-hero-icon .material-icons { font-size: 25px; }
    .general-settings-eyebrow { margin: 0 0 3px; color: #087f78; font-size: 11px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; }
    .general-settings-hero h1 { margin: 0; color: #1d2e40; font-size: 23px; font-weight: 700; }
    .general-settings-hero p:last-child { margin: 5px 0 0; color: #718092; font-size: 13px; }
    .general-settings-card { margin-bottom: 18px; padding: 22px 24px 24px; border: 1px solid #e4ebf0; border-radius: 11px; background: #fff; box-shadow: 0 4px 16px rgba(27, 51, 71, .04); }
    .general-settings-card-heading { display: flex; align-items: center; gap: 13px; margin-bottom: 21px; padding-bottom: 17px; border-bottom: 1px solid #edf1f4; }
    .general-settings-section-icon { width: 40px; height: 40px; border-radius: 10px; }
    .general-settings-section-icon .material-icons { font-size: 21px; }
    .general-settings-card-heading h2 { margin: 0 0 4px; color: #23354a; font-size: 16px; font-weight: 700; }
    .general-settings-card-heading p { margin: 0; color: #7a8795; font-size: 12px; }
    .general-settings-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px 20px; }
    .general-settings-field { min-width: 0; }
    .general-settings-field-wide { grid-column: span 2; }
    .general-settings-field > label, .general-settings-upload-label { display: block; margin: 0 0 7px; color: #465669; font-size: 12px; font-weight: 600; }
    .general-settings-page .general-settings-field .form-control { height: 42px; padding: 9px 12px; border: 1px solid #d9e1e8; border-radius: 6px; background-color: #fff; box-shadow: none; color: #26384c; font-size: 13px; transition: border-color .15s, box-shadow .15s; }
    .general-settings-page .general-settings-field .form-control:focus { border-color: #2a9c91; box-shadow: 0 0 0 3px rgba(42, 156, 145, .12); }
    .general-settings-field small, .general-settings-upload-card small { display: block; margin-top: 7px; color: #788797; font-size: 11px; line-height: 1.5; }
    .general-settings-branding-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .general-settings-upload-card { min-width: 0; padding: 16px; border: 1px solid #e5ebf0; border-radius: 9px; background: #fbfcfd; }
    .general-settings-image-preview { display: flex; min-height: 125px; align-items: center; justify-content: center; margin-bottom: 13px; padding: 14px; overflow: hidden; border: 1px dashed #d5dfe7; border-radius: 7px; background: #fff; }
    .general-settings-logo-preview img { display: block; max-width: 100%; max-height: 100px; object-fit: contain; }
    .general-settings-banner-preview { min-height: 125px; }
    .settings-header-image-preview { display: block; max-width: 100%; max-height: 100px; width: auto; height: auto; object-fit: contain; }
    .general-settings-banner-preview span { display: flex; align-items: center; gap: 7px; color: #9aa6b2; font-size: 12px; }
    .general-settings-banner-preview .material-icons { font-size: 18px; }
    .general-settings-upload-card input[type="file"] { height: auto !important; padding: 8px 10px !important; background: #fff !important; font-size: 12px !important; }
    .general-settings-note { display: flex; align-items: flex-start; gap: 8px; padding: 11px 13px; border-radius: 6px; background: #f2f8fa; color: #68798a; font-size: 11px; line-height: 1.55; }
    .general-settings-note .material-icons { color: #378b91; font-size: 17px; }
    .general-settings-color-field .form-control { width: 100%; padding: 4px !important; }
    .general-settings-actions { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px 20px; border: 1px solid #e3eaf0; border-radius: 10px; background: #fff; }
    .general-settings-actions p { display: flex; align-items: center; gap: 7px; margin: 0; color: #7b8997; font-size: 11px; }
    .general-settings-actions p .material-icons { color: #78968e; font-size: 16px; }
    .general-settings-actions .btn { display: inline-flex; align-items: center; gap: 7px; margin: 0; padding: 10px 17px; border-radius: 6px; background: #087f78; box-shadow: none; font-size: 12px; font-weight: 600; }
    .general-settings-actions .btn:hover, .general-settings-actions .btn:focus { background: #066c66; }
    .general-settings-actions .btn .material-icons { font-size: 17px; }
    @media (max-width: 991px) { .general-settings-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 600px) { .general-settings-hero { align-items: flex-start; padding: 18px; } .general-settings-hero-icon { width: 40px; height: 40px; } .general-settings-hero h1 { font-size: 19px; } .general-settings-card { padding: 18px 15px; } .general-settings-grid, .general-settings-branding-grid { grid-template-columns: 1fr; gap: 14px; } .general-settings-field-wide { grid-column: auto; } .general-settings-actions { align-items: stretch; flex-direction: column; } .general-settings-actions .btn { justify-content: center; } }
</style>


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
    @if(Session::has('success'))
            toastr["success"]('{{ __(Session::get('success')) }}');
        @endif

                @if(Session::has('error'))
            toastr["error"]('{{ __(Session::get('error')) }}');
        @endif



                @if ($errors->any())
                @foreach ($errors->all() as $error)
            toastr["error"]('{{ $error }}');
        @endforeach
        @endif



    const discountToggle = document.getElementById('discount_enabled');
    const discountPercentage = document.getElementById('discount_percentage');
    if (discountToggle && discountPercentage) {
        const syncDiscountPercentage = function () {
            const enabled = discountToggle.value === '1';
            discountPercentage.disabled = !enabled;
            discountPercentage.required = enabled;
        };
        discountToggle.addEventListener('change', syncDiscountPercentage);
        syncDiscountPercentage();
    }

</script>

@endpush
