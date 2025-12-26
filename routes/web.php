<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\ProfileController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\ServerController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\LoginLogController;
use App\Http\Controllers\OAuth\AuthorizeController;
use App\Http\Controllers\OAuth\TokenController;
use App\Http\Controllers\OAuth\UserInfoController;
use App\Http\Controllers\OAuth\WellKnownController;
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
    
    // User Management
    Route::resource('users', UserController::class);
    Route::get('users/{user}/reset-password', [UserController::class, 'showResetPassword'])
         ->name('users.reset-password');
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])
         ->name('users.reset-password.update');
    Route::get('users-import', [UserController::class, 'showImportForm'])
         ->name('users.import');
    Route::post('users-import', [UserController::class, 'import'])
         ->name('users.import.store');
    
    // Application Management
    Route::resource('applications', ApplicationController::class);
    Route::get('applications/{application}/users', [ApplicationController::class, 'users'])
         ->name('applications.users');
    Route::post('applications/{application}/regenerate-secret', [ApplicationController::class, 'regenerateSecret'])
         ->name('applications.regenerate-secret');
    
    // Role Management
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
