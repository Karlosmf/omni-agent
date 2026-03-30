<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

use App\Models\AgencySetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $agencySettings = null;
        
        if (! app()->runningInConsole() || app()->runningUnitTests()) {
            try {
                if (Schema::hasTable('agency_settings')) {
                    $agencySettings = Cache::rememberForever('agency_settings', function () {
                        return AgencySetting::first();
                    });
                }
            } catch (\Exception $e) {
                // Silently fail if table doesn't exist yet (e.g. during migrations)
            }
        }

        return $panel
            ->id('admin')
            ->path('admin')
            ->login()
            ->default()
            ->colors([
                'primary' => $agencySettings?->be_primary_color ?? Color::Amber,
                'danger' => $agencySettings?->be_danger_color ?? Color::Red,
                'gray' => $agencySettings?->be_gray_color ?? Color::Zinc,
                'info' => $agencySettings?->be_info_color ?? Color::Blue,
                'success' => $agencySettings?->be_success_color ?? Color::Green,
                'warning' => $agencySettings?->be_warning_color ?? Color::Amber,
            ])
            ->brandLogo(asset('images/branding/logo-full.png'))
            ->darkModeBrandLogo(asset('images/branding/logo-full-white.png'))
            ->brandLogoHeight('3rem')
            ->homeUrl('/')
            ->favicon(asset('images/branding/logo-icon.png'))
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
            ->widgets([
                // Widgets are discovered, but we can enforce grid here
            ])
            ->databaseNotifications()
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Ventas'),
                NavigationGroup::make()
                    ->label('Sistema'),
            ])

            ->darkMode()
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
            ->renderHook(
                'panels::body.start',
                fn () => new \Illuminate\Support\HtmlString('
                    <style>
                        .fi-simple-layout {
                            background-color: #f0f2f5 !important;
                            background-image: url("'.asset('images/landing/bg-pattern.png').'") !important;
                            background-repeat: repeat !important;
                            background-size: 400px !important;
                        }
                    </style>
                '),
            );
    }
}
