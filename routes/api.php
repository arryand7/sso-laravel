<?php

use App\Http\Controllers\Api\ProvisioningController;
use App\Http\Middleware\AuthenticateProvisioningApp;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Provisioning API Routes for Connected Applications
|--------------------------------------------------------------------------
*/

Route::prefix('provisioning')
    ->name('api.provisioning.')
    ->group(function () {
        // Authenticated Provisioning Endpoints
        Route::middleware([AuthenticateProvisioningApp::class, 'throttle:60,1'])->group(function () {
            Route::get('/me', [ProvisioningController::class, 'me'])->name('me');
            Route::get('/users', [ProvisioningController::class, 'index'])->name('users.index');
            Route::get('/users/{uuid}', [ProvisioningController::class, 'show'])->name('users.show');
            Route::get('/changes', [ProvisioningController::class, 'changes'])->name('changes');
            Route::post('/sync-results', [ProvisioningController::class, 'syncResults'])->name('sync-results');
        });

        // Signed Temporary Photo Route (Public but requires valid signature)
        Route::get('/photo/{user}', [ProvisioningController::class, 'photo'])
            ->name('photo')
            ->middleware('signed');
    });
