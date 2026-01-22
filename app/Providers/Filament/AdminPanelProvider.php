<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\CourseEnrollmentChart;
use App\Filament\Widgets\ExpensesByCategoryChart;
use App\Filament\Widgets\MemberGrowthChart;
use App\Filament\Widgets\MissionsByTypeChart;
use App\Filament\Widgets\PrayerRequestsWidget;
use App\Filament\Widgets\RecentAnnouncementsWidget;
use App\Filament\Widgets\RoleBasedStatsWidget;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\UpcomingEventsWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login([AuthenticatedSessionController::class, 'create'])
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                AccountWidget::class,
                StatsOverview::class,
                RoleBasedStatsWidget::class,
                MemberGrowthChart::class,
                MissionsByTypeChart::class,
                ExpensesByCategoryChart::class,
                CourseEnrollmentChart::class,
                RecentAnnouncementsWidget::class,
                UpcomingEventsWidget::class,
                PrayerRequestsWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
            ])
            ->navigationGroups([
                'Organising Secretary',
                'Missions Secretary',
                'Follow-Up Secretary',
                'Prayer Secretary',
                'Treasurer',
                'E-Learning',
                'Settings',
            ])
            ->databaseNotifications()
            ->sidebarCollapsibleOnDesktop()
            ->sidebarFullyCollapsibleOnDesktop();
    }
}
