<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Archivage BUNEC')
            ->colors([
                'primary' => Color::Sky,
                'success' => Color::Emerald,
                'danger'  => Color::Rose,
                'warning' => Color::Amber,
                'info'    => Color::Sky,
                'gray'    => Color::Slate,
            ])
            ->font('Inter')
            // ═══ THÈME PAR DÉFAUT : SYSTÈME ═══
            // darkMode(true) active le toggle, le navigateur détecte auto light/dark
            ->darkMode(true)
            ->navigationGroups([
                NavigationGroup::make('Tableau de bord')
                    ->icon('heroicon-o-chart-bar-square')
                    ->collapsible(false),
                NavigationGroup::make('Documents')
                    ->icon('heroicon-o-folder-open'),
                NavigationGroup::make('Comptabilité')
                    ->icon('heroicon-o-banknotes'),
                NavigationGroup::make('Administration')
                    ->icon('heroicon-o-shield-check')
                    ->collapsed(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\KpiStatsWidget::class,
                \App\Filament\Widgets\MonthlyTrendChart::class,
                \App\Filament\Widgets\TypeDistributionChart::class,
                \App\Filament\Widgets\CompletionRateChart::class,
                \App\Filament\Widgets\RecentDossiersWidget::class,
                \App\Filament\Widgets\CloudStatusWidget::class,
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
            ->authMiddleware([Authenticate::class])
            ->databaseNotifications()
            ->databaseNotificationsPolling('60s')
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('17rem')
            ->maxContentWidth('full')
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->renderHook('panels::head.end', fn () => view('filament.premium-styles'))
            ->renderHook('panels::sidebar.nav.start', fn () => view('filament.context-badge'));

        // ═══ FILAMENT SHIELD — dans Administration, nommé "Rôles utilisateurs" ═══
        if (class_exists(\BezhanSalleh\FilamentShield\FilamentShieldPlugin::class)) {
            $panel->plugins([
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make()
                    ->gridColumns(['default' => 1, 'sm' => 2, 'lg' => 3])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns(['default' => 1, 'sm' => 2])
                    ->resourceCheckboxListColumns(['default' => 1, 'sm' => 2]),
            ]);
        }

        return $panel;
    }
}
