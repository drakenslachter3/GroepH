<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EnergyBudgetController;

Route::get('/form', [EnergyBudgetController::class, 'index'])->name('budget.form');
Route::post('/calculate', [EnergyBudgetController::class, 'calculate'])->name('budget.calculate');
Route::post('/store', [EnergyBudgetController::class, 'store'])->name('budget.store');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/user-dashboard', [DashboardController::class, 'index'])->name('index.form');

require __DIR__.'/auth.php';

