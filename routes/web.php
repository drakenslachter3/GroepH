<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnergyBudgetController;
use App\Http\Controllers\EnergyPredictionController;
use App\Http\Controllers\EnergyVisualizationController;
use App\Http\Controllers\InfluxController;
use App\Http\Controllers\InfluxDataController;
use App\Http\Controllers\PredictionSettingsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RefreshSettingsController;
use App\Http\Controllers\SmartMeterController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\CheckRole;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Behouden van beide routes uit de verschillende branches
Route::get('/influx/explore', [InfluxController::class, 'explore']);

// Toegevoegd vanuit dev branch
Route::get('/energy/data-form', [InfluxDataController::class, 'showEnergyForm'])
    ->name('energy.form');

Route::middleware(['auth', 'budget.check'])->group(function () {
    // Budget routes - updated for per-meter functionality
    Route::get('/budget/form', [EnergyBudgetController::class, 'index'])->name('budget.form');
    Route::post('/budget/store-per-meter', [EnergyBudgetController::class, 'storePerMeter'])->name('budget.store-per-meter');

    // Legacy routes for backwards compatibility
    Route::post('/budget/calculate', [EnergyBudgetController::class, 'calculate'])->name('budget.calculate');
    Route::post('/budget/store', [EnergyBudgetController::class, 'store'])->name('budget.store');

    // Additional budget management routes
    Route::get('/budget/meter/{meter}', [EnergyBudgetController::class, 'getBudgetForMeter'])->name('budget.meter');
    Route::delete('/budget/meter/{meter}', [EnergyBudgetController::class, 'deleteBudgetForMeter'])->name('budget.meter.delete');
});

Route::middleware('auth', 'budget.check')->group(function () {
    Route::get('/form', [EnergyBudgetController::class, 'index'])->name('budget.form');
    Route::post('/calculate', [EnergyBudgetController::class, 'calculate'])->name('budget.calculate');
    Route::post('/store', [EnergyBudgetController::class, 'store'])->name('budget.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Energie budget routes
    Route::get('/energy/budget', [EnergyBudgetController::class, 'index'])->name('budget.form');
    Route::post('/energy/budget/calculate', [EnergyBudgetController::class, 'calculate'])->name('budget.calculate');
    Route::post('/energy/budget/store', [EnergyBudgetController::class, 'store'])->name('budget.store');

    // Energie visualisatie routes
    Route::get('/energy/visualization', [EnergyVisualizationController::class, 'dashboard'])->name('energy.dashboard');
    Route::get('/energy/predictions', [EnergyPredictionController::class, 'showPredictions'])->name('energy.predictions');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/set-widget', [DashboardController::class, 'setWidget'])->name('dashboard.setWidget');
    Route::post('/dashboard/reset-layout', [DashboardController::class, 'resetLayout'])->name('dashboard.resetLayout');
    Route::post('/dashboard/set-time', [DashboardController::class, 'setTime'])->name('dashboard.setTime');

    // Opslaan geselecteerde meter dashboard route
    Route::post('/dashboard', [DashboardController::class, 'saveSelectedMeter'])->name('dashboard.saveSelectedMeter');
    Route::post('/dashboard/refresh', [DashboardController::class, 'refreshData'])->name('dashboard.refresh');

    Route::post('/dashboard/comparison-toggle', [DashboardController::class, 'saveComparisonToggle'])->name('dashboard.comparison-toggle');

    Route::post('/energy/store-data', [InfluxDataController::class, 'storeEnergyData'])
        ->name('energy.store-data');
});

Route::middleware('auth')->group(function () {
    // Gebruikersbeheer routes
    Route::resource('users', UserController::class);
    Route::post('/delete-user/{user}', [UserController::class, 'destroy'])->name('users.delete');

    // Slimme meter beheer routes
    Route::resource('smartmeters', SmartMeterController::class);
    Route::get('/users/{user}/meters', [SmartMeterController::class, 'userMeters'])->name('smartmeters.userMeters');
    Route::post('/users/{user}/meters/link', [SmartMeterController::class, 'linkMeter'])->name('smartmeters.linkMeter');
    Route::post('/users/{user}/meters/{smartmeter}/unlink', [SmartMeterController::class, 'unlinkMeter'])->name('smartmeters.unlinkMeter');
    Route::post('/smartmeters/{smartmeter}/delete', [SmartMeterController::class, 'destroy'])->name('smartmeters.delete');
});

// API route for smart meter search (used by AJAX)
Route::middleware('auth')->prefix('api')->group(function () {
    Route::get('/smartmeters/search', [SmartMeterController::class, 'search'])->name('api.smartmeters.search');
});

// Energy notification routes
Route::middleware('auth')->group(function () {
    Route::get('/notifications', [App\Http\Controllers\EnergyNotificationController::class, 'index'])
        ->name('notifications.index');
    Route::post('/notifications/{notification}/mark-as-read', [App\Http\Controllers\EnergyNotificationController::class, 'markAsRead'])
        ->name('notifications.mark-as-read');
    Route::post('/notifications/{notification}/dismiss', [App\Http\Controllers\EnergyNotificationController::class, 'dismiss'])
        ->name('notifications.dismiss');
    Route::get('/notifications/settings', [App\Http\Controllers\EnergyNotificationController::class, 'settings'])
        ->name('notifications.settings');
    Route::post('/notifications/settings', [App\Http\Controllers\EnergyNotificationController::class, 'updateSettings'])
        ->name('notifications.update-settings');
});

// Testroutes - apart van de productie routes
Route::middleware(['auth', CheckRole::class . ':admin'])->prefix('testing')->group(function () {
    Route::get('/generate-notification', [App\Http\Controllers\TestNotificationController::class, 'generateTestNotification'])
        ->name('testing.notification');
});

// Prediction settings routes - match other admin routes pattern
Route::middleware(['auth', CheckRole::class . ':admin'])->group(function () {
    Route::get('/admin/prediction-settings', [PredictionSettingsController::class, 'index'])
        ->name('admin.prediction-settings.index');
    Route::post('/admin/prediction-settings', [PredictionSettingsController::class, 'update'])
        ->name('admin.prediction-settings.update');
    Route::get('/admin/refresh-settings', [RefreshSettingsController::class, 'index'])
        ->name('admin.refresh-settings.index');
    Route::post('/admin/refresh-settings', [RefreshSettingsController::class, 'update'])
        ->name('admin.refresh-settings.update');

    Route::get('/influxdb-outages/index', [App\Http\Controllers\InfluxdbOutageController::class, 'index'])->name('admin.influxdb-outages.index');
    Route::get('/influxdb-outages/create', [App\Http\Controllers\InfluxdbOutageController::class, 'create'])->name('admin.influxdb-outages.create');
    Route::post('/influxdb-outages/create', [App\Http\Controllers\InfluxdbOutageController::class, 'store'])->name('admin.influxdb-outages.store');
    Route::get('/influxdb-outages/edit', [App\Http\Controllers\InfluxdbOutageController::class, 'edit'])->name('admin.influxdb-outages.edit');
    Route::delete('/influxdb-outages/{influxdbOutage}', [App\Http\Controllers\InfluxdbOutageController::class, 'destroy'])->name('admin.influxdb-outages.destroy');
    Route::get('/influxdb-outages', [App\Http\Controllers\InfluxdbOutageController::class, 'index'])->name('admin.influxdb-outages.index');
    Route::get('/influxdb-outages/create', [App\Http\Controllers\InfluxdbOutageController::class, 'create'])->name('admin.influxdb-outages.create');
    Route::post('/influxdb-outages', [App\Http\Controllers\InfluxdbOutageController::class, 'store'])->name('admin.influxdb-outages.store');
    Route::get('/influxdb-outages/{influxdbOutage}/edit', [App\Http\Controllers\InfluxdbOutageController::class, 'edit'])->name('admin.influxdb-outages.edit');
    Route::put('/influxdb-outages/{influxdbOutage}/edit', [App\Http\Controllers\InfluxdbOutageController::class, 'update'])->name('admin.influxdb-outages.update');
    Route::delete('/influxdb-outages/{influxdbOutage}', [App\Http\Controllers\InfluxdbOutageController::class, 'destroy'])->name('admin.influxdb-outages.destroy');

});

require __DIR__ . '/auth.php';

Route::get('/influx', [InfluxDataController::class, 'index'])->name('influx.index');
Route::get('/influx/create', [InfluxDataController::class, 'create'])->name('influx.create');
Route::post('/influx', [InfluxDataController::class, 'store'])->name('influx.store');
Route::get('/influx/test-connection', [InfluxDataController::class, 'testConnection'])->name('influx.test-connection');
