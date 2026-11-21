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

<!-- noUISlider Css -->
<link href="{{ asset('asset/plugins/nouislider/nouislider.min.css') }}" rel="stylesheet" />
@endpush

@push('include-js')
<script src="{{ asset('asset/js/pages/forms/advanced-form-elements.js') }}"></script>

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

@extends('layouts.app')

@section('top-bar')
@include('shared.header')
@stop
@section('left-sidebar')
@include('shared.sidebar')
@stop

@section('content')
<section @if ($is_rtl) dir="rtl" @endif class="content">
    <div class="container-fluid">
        <div class="row clearfix">
            <div class="col-xs-12 col-sm-3">
                <div class="card profile-card">
                    <div class="profile-header">&nbsp;</div>
                    <div class="profile-body">
                        <div class="image-area">
                            @if (empty($profile['avatar']))
                            <img class="width-140 height-140" src="{{ asset('upload/avatar/avatar.png') }}"
                                alt="{{ __('ui.profile.profile_picture') }}" />
                            @else
                            <img class="width-140 height-140" src="{{ asset($profile['avatar']) }}"
                                alt="{{ __('ui.profile.profile_picture') }}" />
                            @endif

                        </div>
                        <div class="content-area">
                            <h3>{{ $profile['last_name'] }} {{ $profile['first_name'] }}</h3>
                            <p>{{ $profile['designation'] }}</p>
                            <p>{{ App\User::find($profile['user_id'])->role['name'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="card card-about-me">
                    <div class="header">
                        <h2>{{ __('ui.profile.about_me') }}</h2>
                    </div>
                    <div class="body">
                        <ul>
                            <li>
                                <div class="title">
                                    <i class="material-icons">library_books</i>
                                    {{ __('ui.profile.education') }}
                                </div>
                                <div class="content">
                                    {{ $profile['education'] }}
                                </div>
                            </li>
                            <li>
                                <div class="title">
                                    <i class="material-icons">location_on</i>
                                    {{ __('ui.profile.location') }}
                                </div>
                                <div class="content">
                                    {{ $profile['present_address'] }}
                                </div>
                            </li>
                            <li>
                                <div class="title">
                                    <i class="material-icons">notes</i>
                                    {{ __('ui.profile.description') }}
                                </div>
                                <div class="content">
                                    {{ $profile['description'] }}
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-xs-12 col-sm-9">
                <div class="card">
                    <div class="body">
                        <div>
                            <ul class="nav nav-tabs" role="tablist">
                                <li role="presentation" class="active"><a href="#profile_settings"
                                        aria-controls="settings" role="tab" data-toggle="tab">{{ __('ui.profile.profile_settings') }}</a>
                                </li>
                                <li role="presentation"><a href="#change_password_settings" aria-controls="settings"
                                        role="tab" data-toggle="tab">{{ __('ui.profile.change_password') }}</a></li>
                            </ul>
                            <div class="tab-content">
                                <div role="tabpanel" class="tab-pane fade in active" id="profile_settings">
                                    <form enctype="multipart/form-data" class="form-horizontal" method="post"
                                        action="{{ route('profile.update', ['id' => $profile['id']]) }}">
                                        {{ csrf_field() }}
                                        <div class="form-group">
                                            <label for="first_name" class="col-sm-2 control-label">{{ __('ui.profile.first_name') }}</label>
                                            <div class="col-sm-10">
                                                <div class="form-line">
                                                    <input type="text" id="first_name" class="form-control"
                                                        name="first_name" placeholder="{{ __('ui.profile.first_name') }}"
                                                        value="{{ $profile['first_name'] }}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="last_name" class="col-sm-2 control-label">{{ __('ui.profile.last_name') }}</label>
                                            <div class="col-sm-10">
                                                <div class="form-line">
                                                    <input type="text" class="form-control" id="last_name"
                                                        name="last_name" placeholder="{{ __('ui.profile.last_name') }}"
                                                        value="{{ $profile['last_name'] }}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="phone_number" class="col-sm-2 control-label">{{ __('ui.profile.phone_number') }}</label>
                                            <div class="col-sm-10">
                                                <div class="form-line">
                                                    <input type="text" class="form-control" id="phone_number"
                                                        name="phone_number" placeholder="{{ __('ui.profile.phone_number') }}"
                                                        value="{{ $profile['phone_number'] }}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="designation" class="col-sm-2 control-label">{{ __('ui.profile.designation') }}</label>
                                            <div class="col-sm-10">
                                                <div class="form-line">
                                                    <input type="text" class="form-control" id="designation"
                                                        name="designation" placeholder="{{ __('ui.profile.designation') }}"
                                                        value="{{ $profile['designation'] }}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="gender" class="col-sm-2 control-label">{{ __('ui.profile.gender') }}</label>
                                            <div class="col-sm-10">
                                                <div class="form-line">
                                                    <select class="form-control show-tick" name="gender" id="gender">
                                                        <option value="0">{{ __('ui.profile.gender') }}</option>
                                                        <option @if ($profile['gender']==1) selected @endif value="1">{{ __('ui.profile.male') }}</option>
                                                        <option @if ($profile['gender']==2) selected @endif value="2">{{ __('ui.profile.female') }}</option>
                                                        <option @if ($profile['gender']==3) selected @endif value="3">{{ __('ui.profile.gender_common') }}</option>
                                                        <option @if ($profile['gender']==4) selected @endif value="4">{{ __('ui.profile.not_willing_to_say') }}</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="education" class="col-sm-2 control-label">{{ __('ui.profile.education') }}</label>
                                            <div class="col-sm-10">
                                                <div class="form-line">
                                                    <input type="text" class="form-control" id="education"
                                                        name="education" placeholder="{{ __('ui.profile.education') }}"
                                                        value="{{ $profile['education'] }}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="permanent_address" class="col-sm-2 control-label">{{ __('ui.profile.permanent_address') }}</label>
                                            <div class="col-sm-10">
                                                <div class="form-line">
                                                    <input type="text" class="form-control" id="permanent_address"
                                                        name="permanent_address" placeholder="{{ __('ui.profile.permanent_address') }}"
                                                        value="{{ $profile['permanent_address'] }}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="present_address" class="col-sm-2 control-label">{{ __('ui.profile.present_address') }}</label>
                                            <div class="col-sm-10">
                                                <div class="form-line">
                                                    <input type="text" class="form-control" id="present_address"
                                                        name="present_address" placeholder="{{ __('ui.profile.present_address') }}"
                                                        value="{{ $profile['present_address'] }}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="description" class="col-sm-2 control-label">{{ __('ui.profile.profile_picture') }}</label>
                                            <div class="col-sm-10">
                                                <div class="form-line">

                                                    <textarea class="form-control" id="description" name="description"
                                                        rows="1"
                                                        placeholder="{{ __('ui.profile.description') }}">{{ $profile['description'] }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="description" class="col-sm-2 control-label">{{ __('ui.profile.description') }}</label>
                                            <div class="col-sm-10">
                                                <input name="avatar" type="file" class="form-control">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="language_id" class="col-sm-2 control-label">{{ __('ui.profile.add_language') }}</label>
                                            <div class="col-sm-10">
                                                <div class="form-line">
                                                    <select class="form-control show-tick" name="language_id"
                                                        id="language_id">
                                                        <option value="">{{ __('ui.profile.select_language') }}</option>
                                                        @foreach ($languages as $language)
                                                        @php
                                                            $languageLocale = \App\Support\LocaleManager::normalize($language->code);
                                                            $languageLabel = $languageLocale
                                                                ? (\App\Support\LocaleManager::supportedLocales()[$languageLocale] ?? ucfirst($language->name))
                                                                : ucfirst($language->name);
                                                        @endphp
                                                        <option @if ($language_id==$language->id)
                                                            selected
                                                            @endif value="{{ $language->id }}">{{ $languageLabel }}
                                                        </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-sm-offset-2 col-sm-10">
                                                <button type="submit" class="btn btn-info">{{ __('root.common.update') }}</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div role="tabpanel" class="tab-pane fade in" id="change_password_settings">
                                    <form class="form-horizontal" action="{{ route('users.password') }}" method="post">
                                        {{ csrf_field() }}
                                        <div class="form-group">
                                            <label for="OldPassword" class="col-sm-3 control-label">{{ __('Old Password') }}</label>
                                            <div class="col-sm-9">
                                                <div class="form-line">
                                                    <input type="password" class="form-control" id="OldPassword"
                                                        name="OldPassword" placeholder="{{ __('ui.profile.old_password') }}" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="NewPassword" class="col-sm-3 control-label">{{ __('New Password') }}</label>
                                            <div class="col-sm-9">
                                                <div class="form-line">
                                                    <input type="password" class="form-control" id="NewPassword"
                                                        name="NewPassword" placeholder="{{ __('ui.profile.new_password') }}" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="NewPasswordConfirm" class="col-sm-3 control-label">{{ __('New Password (Confirm)') }}</label>
                                            <div class="col-sm-9">
                                                <div class="form-line">
                                                    <input type="password" class="form-control" id="NewPasswordConfirm"
                                                        name="NewPasswordConfirm" placeholder="{{ __('ui.profile.confirm_new_password') }}"
                                                        required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <div class="col-sm-offset-3 col-sm-9">
                                                <button type="submit" class="btn btn-danger">{{ __('ui.actions.submit') }}</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

@stop
