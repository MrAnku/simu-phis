<?php

use App\Http\Middleware\CorsMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use PHPOpenSourceSaver\JWTAuth\Http\Middleware\Authenticate as JWTAuthMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
        $middleware->alias([
            'isAdminLoggedIn' => \App\Http\Middleware\AdminAuthenticate::class,
            'isLearnerLoggedIn' => \App\Http\Middleware\LearnerAuthenticate::class,
            'checkWhiteLabel' => \App\Http\Middleware\CheckWhiteLabelDomain::class,
            'blockGoogleBots' => \App\Http\Middleware\BlockGoogleBots::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            '/ai-calling/log-call-detail',
            '/outlook-phish-report'
        ]);
        $middleware->alias([
            'jwt.auth' => JWTAuthMiddleware::class,
        ]);
        $middleware->append(CorsMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
