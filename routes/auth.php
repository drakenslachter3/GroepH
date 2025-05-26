<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/password-reset-request', [App\Http\Controllers\Auth\PasswordResetRequestController::class, 'create'])
        ->name('password.request');
    Route::post('/password-reset-request', [App\Http\Controllers\Auth\PasswordResetRequestController::class, 'store'])
        ->name('password.email.request');
    Route::get('/reset-password/{token}', [App\Http\Controllers\Auth\PasswordResetRequestController::class, 'resetForm'])
        ->name('password.reset.form');
    Route::post('/reset-password', [App\Http\Controllers\Auth\PasswordResetRequestController::class, 'reset'])
        ->name('password.reset.update');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

});

Route::prefix('admin')->group(function () {

    Route::get('/password-reset-requests', [App\Http\Controllers\Auth\PasswordResetRequestController::class, 'adminIndex'])

        ->name('password.admin.inbox');

    Route::post('/password-reset-requests/{resetRequest}/approve', [App\Http\Controllers\Auth\PasswordResetRequestController::class, 'approve'])

        ->name('password.admin.approve');

    Route::post('/password-reset-requests/{resetRequest}/deny', [App\Http\Controllers\Auth\PasswordResetRequestController::class, 'deny'])

        ->name('password.admin.deny');

});
