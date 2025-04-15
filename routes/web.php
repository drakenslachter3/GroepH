<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnergyBudgetController;
use App\Http\Controllers\EnergyVisualizationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::prefix('admin')->group(function () {
    Route::get('/password-reset-requests', [App\Http\Controllers\Auth\PasswordResetRequestController::class, 'adminIndex'])
        ->name('password.admin.inbox');
    Route::post('/password-reset-requests/{resetRequest}/approve', [App\Http\Controllers\Auth\PasswordResetRequestController::class, 'approve'])
        ->name('password.admin.approve');
    Route::post('/password-reset-requests/{resetRequest}/deny', [App\Http\Controllers\Auth\PasswordResetRequestController::class, 'deny'])
        ->name('password.admin.deny');
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
});

Route::middleware('auth')->group(function () {
    // Gebruikersbeheer routes (vervangt accountbeheer)
    Route::resource('users', UserController::class);
    Route::post('/delete-user/{user}', [UserController::class, 'destroy'])->name('users.delete');
});

require __DIR__ . '/auth.php';
