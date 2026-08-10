<?php

namespace App\Providers\Filament;

use App\Models\AgencySetting;
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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

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
            ->maxContentWidth('full')
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
            ->brandName(fn () => get_agency_settings()?->company_name ?? 'Omni-Agent')
            ->brandLogo(function () {
                $url = get_agency_logotipo_url();
                // Check if the logo image physically exists on the disk or if it's the fallback template logo
                if (str_contains($url, 'logo-full.png') && ! file_exists(public_path('images/branding/logo-full.png'))) {
                    return null;
                }

                return $url;
            })
            ->favicon(fn () => get_agency_isotipo_url())
            ->brandLogoHeight('3rem')
            ->homeUrl('/')
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
                    ->label('Ventas')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Catálogo')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Sistema')
                    ->collapsed(),
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
                fn () => new HtmlString('
                    <style>
                        .fi-simple-layout {
                            background-color: #f0f2f5 !important;
                            background-image: url("'.asset('images/landing/bg-pattern.png').'") !important;
                            background-repeat: repeat !important;
                            background-size: 400px !important;
                        }
                    </style>
                    <script>
                        document.addEventListener("DOMContentLoaded", () => {
                            document.body.addEventListener("click", (e) => {
                                let btn = e.target.closest(".fi-sidebar-group-btn");
                                if (!btn) return;
                                
                                // Damos un pequeño respiro para que AlpineJS termine de abrir o cerrar el menú actual
                                setTimeout(() => {
                                    if (!window.Alpine) return;
                                    let sidebar = Alpine.store("sidebar");
                                    if (!sidebar) return;
                                    
                                    let li = btn.closest(".fi-sidebar-group");
                                    if (!li) return;
                                    
                                    let clickedLabel = li.getAttribute("data-group-label");
                                    
                                    // Comprobamos si el grupo que acabamos de clickear quedó ABIERTO
                                    // (Si NO está en el array de grupos colapsados, significa que está abierto)
                                    let isOpen = !sidebar.collapsedGroups.includes(clickedLabel);
                                    
                                    if (isOpen) {
                                        // Si lo abrimos, cerramos todos los demás automáticamente
                                        document.querySelectorAll(".fi-sidebar-group").forEach((groupEl) => {
                                            let otherLabel = groupEl.getAttribute("data-group-label");
                                            if (otherLabel && otherLabel !== clickedLabel) {
                                                if (!sidebar.collapsedGroups.includes(otherLabel)) {
                                                    sidebar.collapsedGroups.push(otherLabel);
                                                }
                                            }
                                        });
                                    }
                                }, 50);
                            });
                        });
                    </script>
                '),
            );
    }
}
