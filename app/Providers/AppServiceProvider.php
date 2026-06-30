<?php

namespace App\Providers;

use App\Models\AgencySetting;
use App\Models\BookingItem;
use App\Observers\BookingItemObserver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
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
                    return AgencySetting::first();
                });
            }
        } catch (\Throwable $e) {
            // Ignore database connection/file issues during application booting
        }

        View::share('agencySettings', $agencySettings);

        BookingItem::observe(BookingItemObserver::class);
    }
}
