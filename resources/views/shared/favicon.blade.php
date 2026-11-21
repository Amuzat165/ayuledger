@php
    $faviconSettings = auth()->check() ? [] : ($general_settings ?? []);
    $faviconLogo = auth()->check()
        ? config('settings.company_logo')
        : ($faviconSettings['company_logo'] ?? config('settings.company_logo'));
    $faviconLogo = $faviconLogo ?: 'upload/company-logo/e.png';
    $faviconFile = public_path($faviconLogo);
    if (!is_file($faviconFile)) {
        $faviconLogo = 'upload/company-logo/e.png';
        $faviconFile = public_path($faviconLogo);
    }
    $faviconExtension = strtolower(pathinfo($faviconLogo, PATHINFO_EXTENSION));
    $faviconMime = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp'][$faviconExtension] ?? 'image/png';
    $faviconVersion = is_file($faviconFile) ? filemtime($faviconFile) : 1;
    $faviconUrl = asset($faviconLogo) . '?v=' . $faviconVersion;
@endphp
<link rel="icon" type="{{ $faviconMime }}" href="{{ $faviconUrl }}">
<link rel="shortcut icon" type="{{ $faviconMime }}" href="{{ $faviconUrl }}">
