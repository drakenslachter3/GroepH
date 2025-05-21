<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\EnhancedDashboardPredictionService;
use App\Services\DashboardPredictionService;

class EnhancedServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind the enhanced dashboard prediction service
        $this->app->bind(DashboardPredictionService::class, function ($app) {
            // For production, you may want to use an environment variable to toggle this
            if (config('app.debug') === true) {
                return new EnhancedDashboardPredictionService(
                    $app->make('App\Services\EnergyPredictionService'),
                    $app->make('App\Services\EnergyConversionService')
                );
            } else {
                return new DashboardPredictionService(
                    $app->make('App\Services\EnergyPredictionService'),
                    $app->make('App\Services\EnergyConversionService')
                );
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}