<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\View\Components\DateSelector;
use App\View\Components\EnergyStatus;
use App\View\Components\UsagePrediction;
use App\View\Components\SavingTips;
use App\View\Components\HistoricalComparison;
use App\View\Components\EnergyChart;
use App\View\Components\TrendAnalysis;
use App\View\Components\BudgetAlert;
use App\View\Components\EnergySuggestions;
use App\View\Components\AdminNotificationInbox;
use App\View\Components\dashboard\EnergyPredictionChart;
use App\View\Components\EnergyNotificationInbox;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(EnergyConversionService::class, function ($app) {
            return new EnergyConversionService();
        });
        
        // Registreer de Energy Prediction Service
        $this->app->singleton(EnergyPredictionService::class, function ($app) {
            return new EnergyPredictionService();
        });
        
        // Registreer de Dashboard Prediction Service
        $this->app->singleton(DashboardPredictionService::class, function ($app) {
            return new DashboardPredictionService(
                $app->make(EnergyPredictionService::class),
                $app->make(EnergyConversionService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Bestaande componenten
        Blade::component('date-selector', DateSelector::class);
        Blade::component('energy-status', EnergyStatus::class);
        Blade::component('usage-prediction', UsagePrediction::class);
        Blade::component('saving-tips', SavingTips::class);
        
        // Nieuwe componenten
        Blade::component('historical-comparison', HistoricalComparison::class);
        Blade::component('energy-chart', EnergyChart::class);
        Blade::component('trend-analysis', TrendAnalysis::class);
        Blade::component('budget-alert', BudgetAlert::class);
        Blade::component('energy-suggestions', EnergySuggestions::class);
        Blade::component('admin-notification-inbox', AdminNotificationInbox::class);
        Blade::component('energy-notification-inbox', EnergyNotificationInbox::class);
        Blade::component('energy-prediction-chart', EnergyPredictionChart::class);
    }
}