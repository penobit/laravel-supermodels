<?php

namespace Penobit\SuperModels\Providers;

use Illuminate\Support\ServiceProvider as _ServiceProvider;

class ServiceProvider extends _ServiceProvider {
    public function register(): void {
        $this->mergeConfigFrom(__DIR__.'/../../config/supermodels.php', 'supermodels');
    }

    public function boot(): void {
        $this->publishes([
            __DIR__.'/../../config/supermodels.php' => config_path('supermodels.php'),
        ], 'config');

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}