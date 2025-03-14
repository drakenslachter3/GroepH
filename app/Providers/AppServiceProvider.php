<?php

namespace App\Providers;

use App\Services\EnergyPredictionService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Component;
use Illuminate\View\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registreer de EnergyPredictionService
        $this->app->singleton(EnergyPredictionService::class, function ($app) {
            return new EnergyPredictionService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registreer alle componenten die we hebben gemaakt
        Blade::component('date-selector', \App\View\Components\DateSelector::class);
        Blade::component('energy-status', \App\View\Components\EnergyStatus::class);
        Blade::component('usage-prediction', \App\View\Components\UsagePrediction::class);
        Blade::component('saving-tips', \App\View\Components\SavingTips::class);
    }
}