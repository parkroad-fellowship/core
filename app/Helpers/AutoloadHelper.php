<?php

use Illuminate\Support\Facades\Auth;
use Spatie\Browsershot\Browsershot;

use function Spatie\LaravelPdf\Support\pdf;

function userCan(string $ability): bool
{
    return Auth::user()->can($ability);
}

function generatePdf($view, $data = [])
{
    return pdf()
        ->withBrowsershot(function (Browsershot $browsershot) {
            $browsershot
                ->noSandbox()
                ->ignoreHttpsErrors()
                ->newHeadless()
                ->format('A4')
                ->addChromiumArguments(config('prf.app.reports.environment.chromium_args'))
                // ->setChromePath('/usr/bin/google-chrome-stable')
                ->setNodeBinary(config('prf.app.reports.environment.node_path'))
                ->setNpmBinary(config('prf.app.reports.environment.npm_path'))
                ->timeout(120);
        })
        ->view($view, $data)
        ->name(str_replace('.', '_', $view).'.pdf')
        ->download();
}
