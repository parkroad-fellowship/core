<?php

namespace App\Filament\Support;

use Filament\Support\Colors\Color;

/**
 * The PRF design-system colours (prf_design_system/lib/src/theme/colors/prf_colors.dart) as
 * Filament palettes. Navy is the brand primary, lime the accent.
 */
class PRFPalette
{
    public const NAVY = '#1A2253';

    /**
     * Colours seeded as a tenant's `branding.primary_color` before PRF branding existed. They mean
     * "not customised", so the tenant keeps the PRF navy.
     */
    public const UNCUSTOMISED_PRIMARY_COLORS = ['#1e40af', '#1a2253'];

    /**
     * The design-system navy scale, shifted one step so the brand navy sits at 600: Filament fills
     * buttons and colours links and active icons with shade 600 in light mode. 500 is interpolated.
     */
    public const NAVY_SHADES = [
        50 => '#EDF1FF',
        100 => '#D7E0FF',
        200 => '#B6C6F6',
        300 => '#95ACEA',
        400 => '#6D88D4',
        500 => '#434F8F',
        600 => self::NAVY,
        700 => '#141B42',
        800 => '#111636',
        900 => '#0D112A',
        950 => '#090B1F',
    ];

    public const LIME_SHADES = [
        50 => '#F6FEEB',
        100 => '#EAFCD2',
        200 => '#D9F8AE',
        300 => '#C4F184',
        400 => '#AFE964',
        500 => '#9DE35D',
        600 => '#86C14D',
        700 => '#67973B',
        800 => '#4B6D2A',
        900 => '#31481B',
        950 => '#1C2A0F',
    ];

    /**
     * PRF greys, except 900/950: Filament draws its dark-mode surfaces from those two, so they are
     * the design system's dark surfaces (navy800 and navy900).
     */
    public const GRAY_SHADES = [
        50 => '#F7F9FC',
        100 => '#F0F3F8',
        200 => '#E6EAF2',
        300 => '#D6DDE9',
        400 => '#B5C0D3',
        500 => '#8F9BB3',
        600 => '#6B758D',
        700 => '#4B5368',
        800 => '#2F3547',
        900 => '#0D112A',
        950 => '#090B1F',
    ];

    public const SUCCESS = '#0FA678';

    public const WARNING = '#F59E0B';

    public const DANGER = '#D14343';

    public const INFO = '#2E7AF8';

    public const LIME = '#9DE35D';

    public const NEUTRAL = '#8F9BB3';

    public const PERIWINKLE = '#6D88D4';

    /**
     * Colours for categorical charts (pie/doughnut slices), in order.
     */
    public const SERIES = [
        self::NAVY,
        self::LIME,
        self::PERIWINKLE,
        self::SUCCESS,
        self::WARNING,
        self::INFO,
        self::DANGER,
        self::NEUTRAL,
        '#434F8F',
        '#67973B',
    ];

    /**
     * @return array<string, array<int, string>>
     */
    public static function colors(?string $tenantPrimaryColor = null): array
    {
        return [
            'primary' => self::isCustomised($tenantPrimaryColor)
                ? Color::generatePalette((string) $tenantPrimaryColor)
                : self::NAVY_SHADES,
            'accent' => self::LIME_SHADES,
            'gray' => self::GRAY_SHADES,
            'success' => Color::generatePalette(self::SUCCESS),
            'warning' => Color::generatePalette(self::WARNING),
            'danger' => Color::generatePalette(self::DANGER),
            'info' => Color::generatePalette(self::INFO),
        ];
    }

    private static function isCustomised(?string $color): bool
    {
        return (
            $color !== null
            && preg_match('/^#[0-9a-f]{6}$/i', $color) === 1
            && !in_array(strtolower($color), self::UNCUSTOMISED_PRIMARY_COLORS, true)
        );
    }

    /**
     * A brand colour for Chart.js, optionally translucent (8-digit hex).
     */
    public static function chart(string $hex, float $opacity = 1.0): string
    {
        return $opacity >= 1.0 ? $hex : $hex . sprintf('%02X', (int) round($opacity * 255));
    }
}
