<?php

use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\LoginLogController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\ServerController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\OAuth\AuthorizeController;
use App\Http\Controllers\OAuth\TokenController;
use App\Http\Controllers\OAuth\UserInfoController;
use App\Http\Controllers\OAuth\WellKnownController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    // Login
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login');

    // Social OAuth (placeholder)
    Route::get('/auth/{provider}', [SocialAuthController::class, 'redirect'])
        ->whereIn('provider', ['google', 'facebook']);
    Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
        ->whereIn('provider', ['google', 'facebook']);

    // Password Reset
    Route::get('/password/reset', [PasswordResetController::class, 'showLinkRequestForm'])
        ->name('password.request');
    Route::post('/password/email', [PasswordResetController::class, 'sendResetLinkEmail'])
        ->name('password.email');
    Route::get('/password/reset/{token}', [PasswordResetController::class, 'showResetForm'])
        ->name('password.reset');
    Route::post('/password/reset', [PasswordResetController::class, 'reset'])
        ->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| Portal Routes (Authenticated Users)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile/photo', [ProfileController::class, 'destroyPhoto'])->name('profile.photo.destroy');
    Route::get('/profile/password', [ProfileController::class, 'showChangePasswordForm'])
        ->name('profile.password');
    Route::post('/profile/password', [ProfileController::class, 'changePassword'])
        ->name('profile.password.update');
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin|superadmin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Dashboard Admin
        Route::get('/', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

        // Bulk Photo ZIP Import Routes (Must come BEFORE users resource route to prevent wildcard matching)
        Route::get('users/photo-import', [\App\Http\Controllers\Admin\UserPhotoImportController::class, 'index'])
            ->name('users.photo-import.index');
        Route::post('users/photo-import', [\App\Http\Controllers\Admin\UserPhotoImportController::class, 'store'])
            ->name('users.photo-import.store');
        Route::get('users/photo-import/{batch}', [\App\Http\Controllers\Admin\UserPhotoImportController::class, 'show'])
            ->name('users.photo-import.show');
        Route::post('users/photo-import/{batch}/confirm', [\App\Http\Controllers\Admin\UserPhotoImportController::class, 'confirm'])
            ->name('users.photo-import.confirm');
        Route::post('users/photo-import/{batch}/cancel', [\App\Http\Controllers\Admin\UserPhotoImportController::class, 'cancel'])
            ->name('users.photo-import.cancel');
        Route::get('users/photo-import/{batch}/progress', [\App\Http\Controllers\Admin\UserPhotoImportController::class, 'progress'])
            ->name('users.photo-import.progress');
        Route::get('users/photo-import/{batch}/report', [\App\Http\Controllers\Admin\UserPhotoImportController::class, 'downloadReport'])
            ->name('users.photo-import.report');

        // User Management
        Route::resource('users', UserController::class);
        Route::delete('users/{user}/photo', [UserController::class, 'destroyPhoto'])
            ->name('users.photo.destroy');
        Route::post('users/bulk-actions', [UserController::class, 'bulkUpdate'])
            ->name('users.bulk-actions');
        Route::get('users/{user}/reset-password', [UserController::class, 'showResetPassword'])
            ->name('users.reset-password');
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])
            ->name('users.reset-password.update');

        // User Application Access Management
        Route::post('users/{user}/applications', [UserController::class, 'grantApplicationAccess'])
            ->name('users.applications.store');
        Route::put('users/{user}/applications/{application}', [UserController::class, 'updateApplicationAccess'])
            ->name('users.applications.update');
        Route::delete('users/{user}/applications/{application}', [UserController::class, 'revokeApplicationAccess'])
            ->name('users.applications.destroy');

        // Excel Import Routes
        Route::get('users-import/template', [UserController::class, 'downloadImportTemplate'])
            ->name('users.import.template');
        Route::get('users-import', [UserController::class, 'showImportForm'])
            ->name('users.import');
        Route::post('users-import', [UserController::class, 'uploadImport'])
            ->name('users.import.store');
        Route::get('users-import/{batch}', [UserController::class, 'showImportBatch'])
            ->name('users.import.show');
        Route::post('users-import/{batch}/validate', [UserController::class, 'validateImportBatch'])
            ->name('users.import.validate');
        Route::post('users-import/{batch}/commit', [UserController::class, 'commitImportBatch'])
            ->name('users.import.commit');
        Route::get('users-import/{batch}/report', [UserController::class, 'downloadImportReport'])
            ->name('users.import.report');
        Route::post('users-import/{batch}/cancel', [UserController::class, 'cancelImportBatch'])
            ->name('users.import.cancel');

        // Application Management
        Route::resource('applications', ApplicationController::class);
        Route::get('applications/{application}/users', [ApplicationController::class, 'users'])
            ->name('applications.users');
        Route::post('applications/{application}/regenerate-secret', [ApplicationController::class, 'regenerateSecret'])
            ->name('applications.regenerate-secret');

        // Application Access Management & Capabilities
        Route::post('applications/{application}/users/grant', [ApplicationController::class, 'grantUserAccess'])
            ->name('applications.users.grant');
        Route::post('applications/{application}/users/bulk-grant', [ApplicationController::class, 'bulkGrantUserAccess'])
            ->name('applications.users.bulk-grant');
        Route::put('applications/{application}/users/{user}', [ApplicationController::class, 'updateUserAccess'])
            ->name('applications.users.update');
        Route::delete('applications/{application}/users/{user}', [ApplicationController::class, 'revokeUserAccess'])
            ->name('applications.users.destroy');
        Route::post('applications/{application}/users/bulk-revoke', [ApplicationController::class, 'bulkRevokeUserAccess'])
            ->name('applications.users.bulk-revoke');
        Route::put('applications/{application}/capabilities', [ApplicationController::class, 'updateCapabilities'])
            ->name('applications.capabilities.update');

        // Role Management
        Route::middleware('role:superadmin')->group(function () {
            Route::resource('roles', RoleController::class)->except(['index', 'show']);
        });
        Route::resource('roles', RoleController::class)->only(['index', 'show']);

        // Login Logs
        Route::get('logins', [LoginLogController::class, 'index'])->name('logins.index');
        Route::get('logins/export', [LoginLogController::class, 'export'])->name('logins.export');

        // Server Settings (Superadmin only)
        Route::middleware('role:superadmin')
            ->prefix('server')
            ->name('server.')
            ->group(function () {
                Route::get('/', [ServerController::class, 'index'])->name('index');
                Route::post('/', [ServerController::class, 'update'])->name('update');
            });
    });

/*
|--------------------------------------------------------------------------
| OAuth2/OIDC Routes
|--------------------------------------------------------------------------
*/

// Well-Known Endpoints (Public)
Route::get('/.well-known/openid-configuration', [WellKnownController::class, 'openidConfiguration']);
Route::get('/.well-known/jwks.json', [WellKnownController::class, 'jwks']);

// Authorization Endpoint (dengan session check)
Route::get('/oauth/authorize', [AuthorizeController::class, 'authorize'])
    ->middleware('auth')
    ->name('oauth.authorize');

// Token Endpoint (Passport handles this, kita extend untuk id_token)
Route::post('/oauth/token', [TokenController::class, 'issueToken'])
    ->middleware('throttle:oauth-token');

// UserInfo Endpoint
Route::get('/oauth/userinfo', [UserInfoController::class, 'show'])
    ->middleware('auth:api');
Route::post('/oauth/userinfo', [UserInfoController::class, 'show'])
    ->middleware('auth:api');
