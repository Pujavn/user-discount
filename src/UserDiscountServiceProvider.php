<?php

namespace PujaNaik\UserDiscount;

use Illuminate\Support\ServiceProvider;
use PujaNaik\UserDiscount\Services\DiscountManager;

class UserDiscountServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/user-discount.php', 'user-discount');

        $this->app->singleton('user-discount.manager', function ($app) {
            return new DiscountManager(config('user-discount'));
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/user-discount.php' => config_path('user-discount.php'),
        ], 'user-discount-config');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
