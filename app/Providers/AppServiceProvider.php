<?php

namespace App\Providers;

use App\Models\AgencySetting;
use App\Models\BookingItem;
use App\Observers\BookingItemObserver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share Agency Settings globally
        $agencySettings = null;

        try {
            if (Schema::hasTable('agency_settings')) {
                $agencySettings = Cache::rememberForever('agency_settings', function () {
                    $settings = AgencySetting::first();
                    if ($settings && Storage::disk('local')->exists('agency_legal.json')) {
                        $legalData = json_decode(Storage::disk('local')->get('agency_legal.json'), true);
                        $settings->cuit = $legalData['cuit'] ?? '';
                        $settings->legajo = $legalData['legajo'] ?? '';
                    }

                    return $settings;
                });
            }
        } catch (\Throwable $e) {
            // Ignore database connection/file issues during application booting
        }

        View::share('agencySettings', $agencySettings);

        BookingItem::observe(BookingItemObserver::class);
    }
}
