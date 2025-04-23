<?php

// File: routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MeterDataController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Smart meter data endpoints
Route::post('/meter-data', [MeterDataController::class, 'store'])->name('api.meter-data.store');
Route::get('/meter-data/{meterId}', [MeterDataController::class, 'getLatestReading'])->name('api.meter-data.latest');
Route::post('/meter-data/test-parsing', [MeterDataController::class, 'testParsing'])->name('api.meter-data.test-parsing');

// Search endpoint for smart meters (used by the UI)
Route::get('/smartmeters/search', 'App\Http\Controllers\SmartMeterController@search')->name('api.smartmeters.search');