<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify your email · AYULEDGERS</title>
    @include('shared.favicon')
    <link href="{{ asset('asset/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <style>
        body { min-height: 100vh; display: grid; place-items: center; background: #f4f7fb; font-family: Inter, Segoe UI, Arial, sans-serif; color: #101828; }
        .verify-card { width: min(100% - 32px, 480px); padding: 36px; background: #fff; border: 1px solid #eaecf0; border-radius: 16px; box-shadow: 0 12px 32px rgba(16,24,40,.08); }
        .code-input { height: 56px; font-size: 24px; letter-spacing: .45em; text-align: center; }
    </style>
</head>
<body>
<main class="verify-card">
    <a href="/" class="font-weight-bold text-primary">AYULEDGERS</a>
    <h1 class="h3 mt-4">Verify your email</h1>
    <p class="text-muted">Enter the six digit code we sent to your email address. It expires in 10 minutes.</p>
    @if(session('status')) <div class="alert alert-info">{{ session('status') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
    @if($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif
    <form method="POST" action="{{ route('verification.verify') }}">
        @csrf
        <label for="code">Verification code</label>
        <input id="code" name="code" class="form-control code-input" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required autofocus>
        <button class="btn btn-primary btn-block mt-3">Verify email</button>
    </form>
    <form method="POST" action="{{ route('verification.resend') }}" class="mt-3">@csrf
        <button class="btn btn-link p-0">Send a new code</button>
    </form>
    <p class="small text-muted mt-4 mb-0">Already verified? <a href="{{ route('login') }}">Sign in</a></p>
</main>
</body>
</html>
