<?php

use App\Filament\Support\PRFPalette;
use Filament\Support\Colors\Color;

it('uses the PRF navy as primary with the brand at shade 600', function () {
    $colors = PRFPalette::colors();

    expect($colors['primary'])
        ->toBe(PRFPalette::NAVY_SHADES)
        ->and($colors['primary'][600])
        ->toBe('#1A2253')
        ->and($colors['accent'])
        ->toBe(PRFPalette::LIME_SHADES);
});

it('keeps the PRF navy for colours seeded before branding existed', function (?string $seeded) {
    expect(PRFPalette::colors($seeded)['primary'])->toBe(PRFPalette::NAVY_SHADES);
})->with([
    'old default' => '#1E40AF',
    'PRF navy' => '#1a2253',
    'blank' => '',
    'not a colour' => 'navy',
    'none' => null,
]);

it('builds the primary palette from a tenant colour', function () {
    expect(PRFPalette::colors('#8A2BE2')['primary'])->toBe(Color::generatePalette('#8A2BE2'));
});

it('makes translucent chart colours', function () {
    expect(PRFPalette::chart(PRFPalette::SUCCESS, 0.2))
        ->toBe('#0FA67833')
        ->and(PRFPalette::chart(PRFPalette::SUCCESS))
        ->toBe('#0FA678');
});
