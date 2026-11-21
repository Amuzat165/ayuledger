<?php

namespace App\Http\Controllers;

class FaviconController extends Controller
{
    public function show()
    {
        $configuredLogo = config('settings.company_logo') ?: 'upload/company-logo/e.png';
        $publicRoot = realpath(public_path());
        $logoPath = realpath(public_path($configuredLogo));
        $publicPrefix = $publicRoot ? rtrim($publicRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : null;

        if (!$logoPath || !$publicPrefix || strpos($logoPath, $publicPrefix) !== 0 || !is_file($logoPath)) {
            $logoPath = realpath(public_path('upload/company-logo/e.png'));
        }
        abort_unless($logoPath && is_file($logoPath), 404);

        $extension = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
        $mime = [
            'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif', 'webp' => 'image/webp',
        ][$extension] ?? 'image/png';

        return response()->file($logoPath, [
            'Content-Type' => $mime,
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }
}
