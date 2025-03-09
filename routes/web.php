<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EnergyBudgetController;

Route::get('/form', [EnergyBudgetController::class, 'index'])->name('budget.form');
Route::post('/calculate', [EnergyBudgetController::class, 'calculate'])->name('budget.calculate');
Route::post('/store', [EnergyBudgetController::class, 'store'])->name('budget.store');

Route::get('/', function () {
    return view('welcome');
});
