<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnergyBudgetController;
use App\Http\Controllers\EnergyVisualizationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SmartMeterController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('auth')->group(function () {
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

    Route::get('/energy/monthly-budget', [MonthlyEnergyBudgetController::class, 'index'])->name('budget.monthly');
    Route::post('/energy/monthly-budget/update', [MonthlyEnergyBudgetController::class, 'update'])->name('budget.monthly.update');
    Route::post('/energy/monthly-budget/reset', [MonthlyEnergyBudgetController::class, 'reset'])->name('budget.monthly.reset');

    // Energie visualisatie routes
    Route::get('/energy/visualization', [EnergyVisualizationController::class, 'dashboard'])->name('energy.dashboard');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/set-widget', [DashboardController::class, 'setWidget'])->name('dashboard.setWidget');
    Route::post('/dashboard/reset-layout', [DashboardController::class, 'resetLayout'])->name('dashboard.resetLayout');
    Route::post('/dashboard/set-time', [DashboardController::class, 'setTime'])->name('dashboard.setTime');
});

Route::middleware('auth')->group(function () {
    // Gebruikersbeheer routes
    Route::resource('users', UserController::class);
    Route::post('/delete-user/{user}', [UserController::class, 'destroy'])->name('users.delete');
    
    // Slimme meter beheer routes
    Route::resource('smartmeters', SmartMeterController::class);
    Route::get('/users/{user}/meters', [SmartMeterController::class, 'userMeters'])->name('smartmeters.userMeters');
    Route::post('/users/{user}/meters/link', [SmartMeterController::class, 'linkMeter'])->name('smartmeters.linkMeter');
    Route::post('/users/{user}/meters/{smartMeter}/unlink', [SmartMeterController::class, 'unlinkMeter'])->name('smartmeters.unlinkMeter');
    Route::post('/smartmeters/{smartMeter}/delete', [SmartMeterController::class, 'destroy'])->name('smartmeters.delete');
});

// API route for smart meter search (used by AJAX)
Route::middleware('auth')->prefix('api')->group(function () {
    Route::get('/smartmeters/search', [SmartMeterController::class, 'search'])->name('api.smartmeters.search');
});


require __DIR__ . '/auth.php';

