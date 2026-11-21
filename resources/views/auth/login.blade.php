@extends('layouts.etemplate')

@section('auth-body')
    <style>
        .login-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background:
                radial-gradient(circle at top left, rgba(255, 255, 255, 0.16), transparent 34%),
                linear-gradient(135deg, #0d47a1 0%, #123d7a 48%, #0b1f3b 100%);
        }

        .login-panel {
            width: min(1040px, 100%);
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            border-radius: 24px;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 24px 70px rgba(4, 20, 54, 0.32);
        }

        .login-hero {
            color: #fff;
            padding: 42px;
            background:
                linear-gradient(180deg, rgba(11, 31, 59, 0.12), rgba(11, 31, 59, 0.42)),
                linear-gradient(135deg, #123d7a 0%, #0d47a1 52%, #1a5cc8 100%);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 24px;
            position: relative;
        }

        .brand-mark {
            display: inline-flex;
            align-items: center;
            gap: 14px;
        }

        .brand-mark img {
            width: 54px;
            height: 54px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.16);
            padding: 8px;
            object-fit: contain;
        }

        .brand-copy h1 {
            margin: 0;
            font-size: 34px;
            font-weight: 700;
            line-height: 1.1;
        }

        .brand-copy p {
            margin: 14px 0 0;
            max-width: 34ch;
            font-size: 15px;
            line-height: 1.7;
            color: rgba(255, 255, 255, 0.9);
        }

        .hero-points {
            display: grid;
            gap: 12px;
            margin-top: auto;
        }

        .hero-point {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 14px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.11);
            backdrop-filter: blur(8px);
        }

        .hero-point i {
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            background: rgba(255, 255, 255, 0.16);
        }

        .hero-point span {
            font-size: 14px;
            line-height: 1.4;
        }

        .login-form-area {
            padding: 42px 38px;
            background: #fff;
        }

        .login-form-head h2 {
            margin: 0;
            color: #0f172a;
            font-size: 28px;
            font-weight: 700;
        }

        .login-form-head p {
            margin: 10px 0 0;
            color: #64748b;
            line-height: 1.6;
        }

        .login-field {
            margin-top: 18px;
        }

        .login-field .input-group {
            width: 100%;
        }

        .login-field .input-group-addon {
            min-width: 46px;
            background: #f8fafc;
            border-color: #dbe5f1;
            color: #0d47a1;
            border-radius: 12px 0 0 12px;
        }

        .login-field .form-control {
            height: 48px;
            border-color: #dbe5f1;
            box-shadow: none;
            border-radius: 0 12px 12px 0;
            color: #0f172a;
        }

        .login-field .form-control:focus {
            border-color: #0d47a1;
            box-shadow: 0 0 0 3px rgba(13, 71, 161, 0.12);
        }

        .login-meta {
            margin-top: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .login-meta .checkbox {
            margin: 0;
        }

        .login-meta a {
            color: #0d47a1;
            text-decoration: none;
            font-weight: 600;
        }

        .login-submit {
            margin-top: 24px;
        }

        .login-submit .btn {
            width: 100%;
            height: 50px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #0d47a1 0%, #1a5cc8 100%);
            color: #fff;
            font-weight: 700;
            letter-spacing: 0;
            box-shadow: 0 14px 24px rgba(13, 71, 161, 0.22);
        }

        .login-submit .btn:hover,
        .login-submit .btn:focus {
            color: #fff;
            background: linear-gradient(135deg, #0b3d8b 0%, #164ea9 100%);
        }

        .login-demo {
            margin-top: 24px;
            padding: 16px;
            border-radius: 16px;
            background: #f8fbff;
            border: 1px solid #dbe5f1;
        }

        .login-demo .table {
            margin-bottom: 0;
        }

        .login-demo th,
        .login-demo td {
            vertical-align: middle !important;
        }

        .login-error {
            display: block;
            margin-top: 6px;
            color: #dc2626;
            font-size: 13px;
        }

        .login-success {
            margin-top: 20px;
            padding: 13px 15px;
            border: 1px solid #bce7ce;
            border-radius: 12px;
            background: #effaf4;
            color: #17603b;
            font-size: 14px;
            line-height: 1.55;
        }

        @media (max-width: 991px) {
            .login-panel {
                grid-template-columns: 1fr;
            }

            .login-hero,
            .login-form-area {
                padding: 30px 22px;
            }
        }
    </style>

    <div class="login-shell">
        <div class="login-panel">
            <section class="login-hero">
                <div>
                    <div class="brand-mark">
                        <img src="{{ asset('asset/layout/images/avatar-flat.png') }}" alt="{{ config('settings.company_name') }}">
                        <div class="brand-copy">
                            <h1>{{ config('settings.company_name') }}</h1>
                        </div>
                    </div>
                    <div class="brand-copy" style="margin-top: 28px;">
                        <p>
                            {{ __('Access your accounting workspace, review balances, and keep daily operations moving from one secure place.') }}
                        </p>
                    </div>
                </div>

                <div class="hero-points">
                    <div class="hero-point">
                        <i class="fa fa-lock" aria-hidden="true"></i>
                        <span>{{ __('ui.login.workspace_summary') }}</span>
                    </div>
                    <div class="hero-point">
                        <i class="fa fa-line-chart" aria-hidden="true"></i>
                        <span>{{ __('ui.login.quick_access_summary') }}</span>
                    </div>
                    <div class="hero-point">
                        <i class="fa fa-users" aria-hidden="true"></i>
                        <span>{{ __('ui.login.team_summary') }}</span>
                    </div>
                </div>
            </section>

            <section class="login-form-area">
                <div class="login-form-head">
                    <h2>{{ __('ui.auth.log_in') }}</h2>
                    <p>{{ __('ui.auth.enter_account_details') }}</p>
                </div>

                @if (session('status'))
                    <div class="login-success" role="status">{{ session('status') }}</div>
                @endif

                <form id="sign_in" method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="login-field">
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-envelope"></i></span>
                            <input id="email"
                                   type="email"
                                   class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                                   name="email"
                                   value="{{ old('email', session('payment_login_email')) }}"
                                   placeholder="{{ __('ui.auth.email_address') }}"
                                   autocomplete="email"
                                   autofocus>
                        </div>
                        @if ($errors->has('email'))
                            <span class="login-error" role="alert">
                                {{ $errors->first('email') }}
                            </span>
                        @endif
                    </div>

                    <div class="login-field">
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-key"></i></span>
                            <input id="password"
                                   type="password"
                                   class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                                   name="password"
                                   placeholder="{{ __('ui.forms.password') }}"
                                   autocomplete="current-password">
                        </div>
                        @if ($errors->has('password'))
                            <span class="login-error" role="alert">
                                {{ $errors->first('password') }}
                            </span>
                        @endif
                    </div>

                    <div class="login-meta">
                        <div class="checkbox">
                            <label for="remember">
                                <input id="remember" type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                                {{ __('ui.auth.remember_me') }}
                            </label>
                        </div>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}">{{ __('ui.auth.forgot_password') }}</a>
                        @endif
                    </div>

                    <div class="login-submit">
                        <button type="submit" class="btn btn-primary">
                            {{ __('ui.auth.login') }}
                        </button>
                    </div>

                </form>
            </section>
        </div>
    </div>
@stop
