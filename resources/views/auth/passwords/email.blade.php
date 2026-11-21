@extends('layouts.etemplate')

@section('auth-body')
    @include('auth.partials.login-layout-styles')

    <div class="login-shell">
        <div class="login-panel">
            @include('auth.partials.login-hero')

            <section class="login-form-area">
                <div class="login-form-head">
                    <h2>{{ __('auth.reset_password') }}</h2>
                    <p>{{ __('Enter your email address and we will send you a password reset link.') }}</p>
                </div>

                @if (session('status'))
                    <div class="login-success" role="status">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf

                    <div class="login-field">
                        <label for="email">{{ __('auth.email_address') }}</label>
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-envelope" aria-hidden="true"></i></span>
                            <input id="email"
                                   type="email"
                                   class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                                   name="email"
                                   value="{{ old('email') }}"
                                   autocomplete="email"
                                   autofocus
                                   required>
                        </div>
                        @if ($errors->has('email'))
                            <span class="login-error" role="alert">{{ $errors->first('email') }}</span>
                        @endif
                    </div>

                    <div class="login-submit">
                        <button type="submit" class="btn btn-primary">{{ __('auth.send_password_reset_link') }}</button>
                    </div>
                </form>

                <a class="login-back-link" href="{{ route('login') }}">{{ __('ui.auth.log_in') }}</a>
            </section>
        </div>
    </div>
@endsection
