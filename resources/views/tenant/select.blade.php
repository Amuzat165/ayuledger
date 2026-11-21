<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Select your organization</title>
    <style>
        body { background: #f4f7f9; color: #183b56; font-family: Arial, sans-serif; margin: 0; }
        main { background: #fff; border-radius: 8px; box-shadow: 0 8px 28px rgba(24, 59, 86, .10); margin: 10vh auto; max-width: 460px; padding: 32px; }
        h1 { font-size: 23px; margin: 0 0 8px; }
        p { color: #536b7b; margin: 0 0 24px; }
        label.option { border: 1px solid #d6e0e6; border-radius: 5px; display: block; margin: 10px 0; padding: 14px; }
        button { background: #176b63; border: 0; border-radius: 4px; color: #fff; cursor: pointer; font-size: 15px; margin-top: 18px; padding: 12px 18px; width: 100%; }
        .error { color: #a32626; margin-top: 8px; }
    </style>
</head>
<body>
    <main>
        <h1>Select your organization</h1>
        <p>Choose the organization you want to work in.</p>

        <form method="POST" action="{{ route('tenant.select.store') }}">
            @csrf
            @foreach ($tenants as $tenant)
                <label class="option">
                    <input type="radio" name="tenant_id" value="{{ $tenant->id }}" required {{ old('tenant_id') == $tenant->id ? 'checked' : '' }}>
                    {{ $tenant->name }}
                </label>
            @endforeach
            @error('tenant_id')
                <div class="error" role="alert">{{ $message }}</div>
            @enderror
            <button type="submit">Continue</button>
        </form>
    </main>
</body>
</html>
