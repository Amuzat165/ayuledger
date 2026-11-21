<section class="login-hero">
    <div>
        <div class="brand-mark">
            <img src="{{ asset('asset/layout/images/avatar-flat.png') }}" alt="{{ config('settings.company_name') }}">
            <div class="brand-copy">
                <h1>{{ config('settings.company_name') }}</h1>
            </div>
        </div>
        <div class="brand-copy" style="margin-top: 28px;">
            <p>{{ __('Access your accounting workspace, review balances, and keep daily operations moving from one secure place.') }}</p>
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
