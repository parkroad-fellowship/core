<?php

use Illuminate\Support\Facades\Auth;

use function Spatie\LaravelPdf\Support\pdf;

if (! function_exists('userCan')) {
    function userCan(string $ability): bool
    {
        return Auth::user()->can($ability);
    }
}

if (! function_exists('generatePdf')) {
    function generatePdf(string $view, array $data, string $filename)
    {
        return pdf()
            ->view($view, $data)
            ->name(downloadName: $filename)
            ->download();
    }
}

if (! function_exists('tenant_asset')) {
    function tenant_asset(string $key, ?string $default = null): string
    {
        if (! tenancy()->initialized) {
            return asset($default ?? '');
        }

        $path = \App\Models\AppSetting::get("assets.{$key}", $default);

        return $path ? asset($path) : asset($default ?? '');
    }
}
