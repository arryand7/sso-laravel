<?php

namespace App\Providers;

use App\Models\PassportClient;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passport::useClientModel(PassportClient::class);
        Passport::authorizationView('passport.authorize');
        Passport::tokensCan([
            'openid' => 'OpenID Connect scope',
            'profile' => 'Access to basic profile data',
            'email' => 'Access to email address',
            'roles' => 'Access to role claims',
        ]);
        Passport::setDefaultScope([
            'openid',
            'profile',
            'email',
        ]);
    }
}
