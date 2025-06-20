<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
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
        //
        ini_set('max_execution_time', 120); // in seconds
        ini_set('memory_limit', '512M');    // increase if needed
        Paginator::useBootstrapFive();
    }
}
