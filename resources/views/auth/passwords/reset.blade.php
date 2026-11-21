@extends('layouts.etemplate')

@section('auth-body')
    @include('auth.partials.login-layout-styles')

    <div class="login-shell">
        <div class="login-panel">
            @include('auth.partials.login-hero')

            <section class="login-form-area">
                <div class="login-form-head">
                    <h2>{{ __('auth.reset_password') }}</h2>
                    <p>{{ __('Choose a new password for your account.') }}</p>
                </div>

                @if ($errors->any())
                    <div class="login-error" role="alert">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="login-field">
                        <label for="email">{{ __('auth.email_address') }}</label>
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-envelope" aria-hidden="true"></i></span>
                            <input id="email"
                                   type="email"
                                   class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                                   name="email"
                                   value="{{ $email ?? old('email') }}"
                                   autocomplete="email"
                                   autofocus
                                   required>
                        </div>
                        @if ($errors->has('email'))
                            <span class="login-error" role="alert">{{ $errors->first('email') }}</span>
                        @endif
                    </div>

                    <div class="login-field">
                        <label for="password">{{ __('ui.forms.password') }}</label>
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-key" aria-hidden="true"></i></span>
                            <input id="password"
                                   type="password"
                                   class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                                   name="password"
                                   autocomplete="new-password"
                                   required>
                        </div>
                        @if ($errors->has('password'))
                            <span class="login-error" role="alert">{{ $errors->first('password') }}</span>
                        @endif
                    </div>

                    <div class="login-field">
                        <label for="password-confirm">{{ __('ui.forms.confirm_password') }}</label>
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-key" aria-hidden="true"></i></span>
                            <input id="password-confirm"
                                   type="password"
                                   class="form-control"
                                   name="password_confirmation"
                                   autocomplete="new-password"
                                   required>
                        </div>
                    </div>

                    <div class="login-submit">
                        <button type="submit" class="btn btn-primary">{{ __('auth.reset_password') }}</button>
                    </div>
                </form>

                <a class="login-back-link" href="{{ route('login') }}">{{ __('ui.auth.log_in') }}</a>
            </section>
        </div>
    </div>
@endsection
