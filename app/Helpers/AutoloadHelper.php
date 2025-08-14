<?php

use Illuminate\Support\Facades\Auth;
use Spatie\Browsershot\Browsershot;

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
            ->withBrowsershot(function (Browsershot $browsershot) {
                $config = $browsershot
                    ->noSandbox()
                    ->ignoreHttpsErrors()
                    ->newHeadless()
                    ->format('A4')
                    ->addChromiumArguments(config('prf.app.reports.environment.chromium_args'));

                if (! app()->isLocal()) {
                    $config->setChromePath(config('prf.app.reports.environment.chrome_path'));
                }

                $config
                    ->setNodeBinary(config('prf.app.reports.environment.node_path'))
                    ->setNpmBinary(config('prf.app.reports.environment.npm_path'))
                    ->timeout(120);
            })
            ->view($view, $data)
            ->name(downloadName: $filename)
            ->download();
    }
}
