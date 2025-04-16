<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnergyBudgetController;
use App\Http\Controllers\EnergyVisualizationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EnergyBudgetMarginController;

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

    // Energie visualisatie routes
    Route::get('/energy/visualization', [EnergyVisualizationController::class, 'dashboard'])->name('energy.dashboard');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/set-widget', [DashboardController::class, 'setWidget'])->name('dashboard.setWidget');
    Route::post('/dashboard/reset-layout', [DashboardController::class, 'resetLayout'])->name('dashboard.resetLayout');
    Route::post('/dashboard/set-time', [DashboardController::class, 'setTime'])->name('dashboard.setTime');

    Route::post('/energy-budget/margin', [EnergyBudgetMarginController::class, 'updateMargin']);
    Route::post('/energy-budget/budgets', [EnergyBudgetMarginController::class, 'updateBudgets']);
    Route::get('/energy-budget/margin', [EnergyBudgetMarginController::class, 'getMarginSettings']);
    Route::get('/energy-budget/budgets', [EnergyBudgetMarginController::class, 'getDailyBudgets']);
});

Route::middleware('auth')->group(function () {
    // Gebruikersbeheer routes (vervangt accountbeheer)
    Route::resource('users', UserController::class);
    Route::post('/delete-user/{user}', [UserController::class, 'destroy'])->name('users.delete');
});

require __DIR__ . '/auth.php';
