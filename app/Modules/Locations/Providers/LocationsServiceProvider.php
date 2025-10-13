<?php

namespace App\Modules\Locations\Providers;

use Illuminate\Support\ServiceProvider;

class LocationsServiceProvider extends ServiceProvider
{
    /**
     * Register any module services.
     */
    public function register(): void
    {
        // Register module-specific bindings
    }

    /**
     * Bootstrap any module services.
     */
    public function boot(): void
    {
        // Load module migrations
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}