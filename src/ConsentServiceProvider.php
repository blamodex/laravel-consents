<?php

declare(strict_types=1);

namespace Blamodex\Consent;

use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the Consent package.
 *
 * Handles the registration and bootstrapping of package services,
 * including configuration merging and migration loading.
 */
class ConsentServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * This is where bindings, singletons, and config merging should happen.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/consent.php', 'blamodex.consent');
    }

    /**
     * Bootstrap any package services.
     *
     * This is where you load migrations, publish configs, etc.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/config/consent.php' => config_path('consent.php'),
        ], 'blamodex-consent-config');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
