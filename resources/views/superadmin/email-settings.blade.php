@extends('layouts.app')

@section('title', 'Email & Notifications')

@section('top-bar')
    @include('shared.header')
@stop

@section('left-sidebar')
    @include('shared.sidebar')
@stop

@section('content')
<style>
    .content.email-settings-page { max-width: 1120px; margin: 96px 28px 48px 205px; color: #253247; }
    .email-settings-page, .email-settings-page * { box-sizing: border-box; }
    .email-settings-page .es-header { display:flex; align-items:flex-start; justify-content:space-between; gap:20px; margin-bottom:24px; }
    .email-settings-page .es-eyebrow { margin:0 0 6px; color:#64748b; font-size:12px; font-weight:700; letter-spacing:.09em; text-transform:uppercase; }
    .email-settings-page h1 { margin:0; color:#172b4d; font-size:28px; font-weight:700; line-height:1.25; }
    .email-settings-page .es-subtitle { max-width:680px; margin:8px 0 0; color:#64748b; font-size:14px; line-height:1.6; }
    .email-settings-page .es-card { margin:0 0 20px; overflow:hidden; background:#fff; border:1px solid #e2e8f0; border-radius:12px; box-shadow:0 3px 12px rgba(15,23,42,.045); }
    .email-settings-page .es-card-head { padding:19px 24px; border-bottom:1px solid #edf1f5; }
    .email-settings-page .es-card-head h2 { margin:0; color:#172b4d; font-size:17px; font-weight:700; line-height:1.4; }
    .email-settings-page .es-card-head p { margin:5px 0 0; color:#64748b; font-size:13px; line-height:1.5; }
    .email-settings-page .es-card-body { padding:24px; }
    .email-settings-page .es-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:20px 24px; }
    .email-settings-page .es-field { min-width:0; margin:0; }
    .email-settings-page .es-field label { display:block; margin:0 0 7px; color:#344054; font-size:13px; font-weight:600; line-height:1.4; }
    .email-settings-page .es-field input:not([type=file]), .email-settings-page .es-field select, .email-settings-page .es-field textarea { display:block; width:100%; min-height:44px; margin:0; padding:10px 12px; color:#253247; background:#fff; border:1px solid #cbd5e1; border-radius:7px; box-shadow:none; font:inherit; font-size:14px; line-height:1.4; }
    .email-settings-page .es-field textarea { min-height:86px; resize:vertical; }
    .email-settings-page .es-field input:focus, .email-settings-page .es-field select:focus, .email-settings-page .es-field textarea:focus { border-color:#3975d5; outline:0; box-shadow:0 0 0 3px rgba(57,117,213,.13); }
    .email-settings-page .es-help { display:block; margin-top:6px; color:#748198; font-size:12px; line-height:1.5; }
    .email-settings-page .es-file { display:block; width:100%; min-height:44px; padding:9px 10px; color:#475569; background:#f8fafc; border:1px dashed #b9c5d3; border-radius:7px; font-size:13px; }
    .email-settings-page .es-logo-preview { display:block; max-width:180px; max-height:60px; margin-top:10px; object-fit:contain; object-position:left center; }
    .email-settings-page .es-notice { margin:0 0 22px; padding:13px 15px; color:#385274; background:#f1f6fc; border:1px solid #d9e7f7; border-radius:8px; font-size:13px; line-height:1.6; }
    .email-settings-page .es-notice strong { color:#243d5c; }
    .email-settings-page .es-actions { display:flex; align-items:center; gap:12px; margin-top:24px; padding-top:19px; border-top:1px solid #edf1f5; }
    .email-settings-page .es-button { display:inline-flex; align-items:center; justify-content:center; min-height:42px; padding:10px 17px; border:1px solid #155eef; border-radius:7px; background:#155eef; color:#fff; font-size:13px; font-weight:600; line-height:1.3; text-decoration:none; cursor:pointer; }
    .email-settings-page .es-button:hover { background:#104fc9; border-color:#104fc9; color:#fff; text-decoration:none; }
    .email-settings-page .es-button-secondary { background:#fff; color:#344054; border-color:#cbd5e1; }
    .email-settings-page .es-button-secondary:hover { background:#f8fafc; border-color:#94a3b8; color:#253247; }
    .email-settings-page .es-alert { margin:0 0 18px; padding:12px 15px; border:1px solid transparent; border-radius:8px; font-size:13px; line-height:1.5; }
    .email-settings-page .es-alert-success { color:#17603b; background:#effaf4; border-color:#bce7ce; }
    .email-settings-page .es-alert-error { color:#9b2c2c; background:#fff4f3; border-color:#f2c8c5; }
    .email-settings-page .es-errors { margin:0 0 18px; padding:12px 15px; color:#9b2c2c; background:#fff4f3; border:1px solid #f2c8c5; border-radius:8px; font-size:13px; }
    .email-settings-page .es-errors ul { margin:0; padding-left:20px; }
    @media (max-width: 992px) { .content.email-settings-page { margin-left:185px; margin-right:20px; } }
    @media (max-width: 767px) { .content.email-settings-page { margin:86px 15px 32px; } .email-settings-page .es-header { flex-direction:column; align-items:stretch; } .email-settings-page .es-header .es-button { align-self:flex-start; } .email-settings-page .es-grid { grid-template-columns:1fr; gap:16px; } .email-settings-page .es-card-head,.email-settings-page .es-card-body { padding:18px; } }
</style>

<section class="content email-settings-page">
    <header class="es-header">
        <div>
            <p class="es-eyebrow">Platform administration</p>
            <h1>Email &amp; Notifications</h1>
            <p class="es-subtitle">Configure the sender, delivery service, and brand details used for account and payment emails.</p>
        </div>
        <a class="es-button es-button-secondary" href="{{ route('superadmin.dashboard') }}">Back to console</a>
    </header>

    @if(session('status'))<div class="es-alert es-alert-success" role="status">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="es-alert es-alert-error" role="alert">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="es-errors" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <section class="es-card">
        <div class="es-card-head"><h2>Mail server and sender</h2><p>Use the SMTP details supplied by your email provider.</p></div>
        <div class="es-card-body">
            <p class="es-notice"><strong>Notification routing:</strong> contact form enquiries go to info@ayuledgers.com. Payment records are copied to sales@ayuledgers.com.</p>
            <form method="POST" action="{{ route('superadmin.email-settings.save') }}" enctype="multipart/form-data">
                @csrf
                <div class="es-grid">
                    <div class="es-field"><label for="email-host">SMTP host</label><input id="email-host" name="email_host" value="{{ old('email_host', $settings['email_host'] ?? '') }}" placeholder="smtp.example.com" autocomplete="off"></div>
                    <div class="es-grid" style="grid-template-columns:repeat(2,minmax(0,1fr));gap:16px">
                        <div class="es-field"><label for="email-port">Port</label><input id="email-port" type="number" name="email_port" value="{{ old('email_port', $settings['email_port'] ?? 587) }}" min="1" max="65535"></div>
                        <div class="es-field"><label for="email-encryption">Encryption</label><select id="email-encryption" name="email_encryption"><option value="tls" {{ old('email_encryption', $settings['email_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS</option><option value="ssl" {{ old('email_encryption', $settings['email_encryption'] ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option><option value="none" {{ old('email_encryption', $settings['email_encryption'] ?? '') === 'none' ? 'selected' : '' }}>None</option></select></div>
                    </div>
                    <div class="es-field"><label for="email-username">SMTP username</label><input id="email-username" name="email_username" value="{{ old('email_username', $settings['email_username'] ?? '') }}" autocomplete="username"></div>
                    <div class="es-field"><label for="email-password">SMTP password</label><input id="email-password" type="password" name="email_password" autocomplete="new-password" placeholder="Leave blank to keep the saved password"><small class="es-help">Saved securely and never shown here.</small></div>
                    <div class="es-field"><label for="email-from-name">Sender name</label><input id="email-from-name" name="email_from_name" value="{{ old('email_from_name', $settings['email_from_name'] ?? 'AYULEDGERS') }}"></div>
                    <div class="es-field"><label for="email-from-address">Sender email</label><input id="email-from-address" type="email" name="email_from_address" value="{{ old('email_from_address', $settings['email_from_address'] ?? '') }}" placeholder="notifications@ayuledgers.com"></div>
                    <div class="es-field"><label for="email-reply-to">Reply-to email</label><input id="email-reply-to" type="email" name="email_reply_to" value="{{ old('email_reply_to', $settings['email_reply_to'] ?? 'support@ayuledgers.com') }}"></div>
                    <div class="es-field"><label for="email-logo">Email logo</label><input id="email-logo" class="es-file" type="file" name="email_logo" accept="image/png,image/jpeg,image/webp"><small class="es-help">PNG, JPG, or WebP. Maximum 2 MB.</small>@if(!empty($settings['email_logo']))<img class="es-logo-preview" src="{{ asset('storage/'.$settings['email_logo']) }}" alt="Current email logo">@endif</div>
                    <div class="es-field" style="grid-column:1/-1"><label for="email-footer">Footer sign-off</label><textarea id="email-footer" name="email_footer" rows="3" placeholder="Regards,&#10;The AYULEDGERS Team">{{ old('email_footer', $settings['email_footer'] ?? 'AYULEDGERS') }}</textarea><small class="es-help">Shown at the bottom of platform emails.</small></div>
                </div>
                <div class="es-actions"><button type="submit" class="es-button">Save email settings</button></div>
            </form>
        </div>
    </section>

    <section class="es-card">
        <div class="es-card-head"><h2>Test email delivery</h2><p>Send a message using the saved mail server settings.</p></div>
        <div class="es-card-body">
            <form method="POST" action="{{ route('superadmin.email-settings.test') }}">
                @csrf
                <div class="es-grid" style="align-items:end">
                    <div class="es-field"><label for="test-email">Recipient email</label><input id="test-email" type="email" name="test_email" value="{{ old('test_email') }}" placeholder="you@example.com" required></div>
                    <div class="es-field"><button type="submit" class="es-button es-button-secondary">Send test email</button></div>
                </div>
            </form>
        </div>
    </section>
</section>
@endsection
