<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn (Request $request) => 
            $request->is('api/*') ? '/' : route('login')
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'يجب تسجيل الدخول أولاً'], 401);
            }
        });
    })->create();

if (isset($_ENV['VERCEL']) || isset($_ENV['VERCEL_URL'])) {
    $app->useStoragePath('/tmp');

    // Create the mandatory storage sub-directories in Vercel's /tmp layer
    $paths = [
        '/tmp/app',
        '/tmp/framework/cache/data',
        '/tmp/framework/sessions',
        '/tmp/framework/testing',
        '/tmp/framework/views',
        '/tmp/logs'
    ];

    foreach ($paths as $path) {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }
}

// Force Registering the View provider to fix the Target Class [view] doesn't exist error in Vercel.
$app->register(\Illuminate\View\ViewServiceProvider::class);

return $app;
