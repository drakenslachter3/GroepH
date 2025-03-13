<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EnergyBudgetController;
use App\Http\Controllers\EnergyVisualizationController;

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


    
      // Energie budget routes
      Route::get('/energy/budget', [EnergyBudgetController::class, 'index'])->name('budget.form');
      Route::post('/energy/budget/calculate', [EnergyBudgetController::class, 'calculate'])->name('budget.calculate');
      Route::post('/energy/budget/store', [EnergyBudgetController::class, 'store'])->name('budget.store');
      
      // Energie visualisatie routes
      Route::get('/energy/visualization', [EnergyVisualizationController::class, 'dashboard'])->name('energy.dashboard');
});

require __DIR__.'/auth.php';

