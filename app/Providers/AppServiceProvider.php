<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

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
        // ini_set('max_execution_time', 120); // in seconds
        // ini_set('memory_limit', '512M');    // increase if needed
        Paginator::useBootstrapFive();

        RateLimiter::for('limiter', function (Request $request) {

            return $request->user()
                ? Limit::perMinutes(30, 100)->by($request->user()->id)->response(function () {
                    return response()->json(['success' => false, 'message' => 'Too many requests. Please try again later.'], 429);
                })
                : Limit::perMinutes(30, 5)->by(getClientIp())->response(function () {
                    return response()->json(['success' => false, 'message' => 'Too many requests. Please try again later.'], 429);
                });
        });

        RateLimiter::for('learner-limiter', function (Request $request) {
            if ($request->is('api/learn/create-new-token') || $request->is('api/learn/blue-collar/create-new-token')) {  // Sensitive route
                return Limit::perMinutes(30, 5)->by(getClientIp())->response(function () {
                    return response()->json(['success' => false, 'message' => 'Too many requests. Please try again later.'], 429);
                });
            }

            return Limit::perMinutes(30, 50)->by(getClientIp())->response(function () {
                return response()->json(['success' => false, 'message' => 'Too many requests. Please try again later.'], 429);
            });
        });

        RateLimiter::for('hook-limiter', function (Request $request) {

            return Limit::perMinutes(30, 20)->by(getClientIp())->response(function () {
                return response()->json(['success' => false, 'message' => 'Too many requests. Please try again later.'], 429);
            });
        });
    }
}
