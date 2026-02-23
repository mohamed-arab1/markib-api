<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;

if (isset($_ENV['VERCEL']) || isset($_ENV['VERCEL_URL'])) {
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

    // Tell Laravel to write all cache files into Vercel's /tmp directory instead of the read-only /var/task/user/bootstrap/cache
    putenv('APP_SERVICES_CACHE=/tmp/app/services.php');
    putenv('APP_PACKAGES_CACHE=/tmp/app/packages.php');
    putenv('APP_CONFIG_CACHE=/tmp/app/config.php');
    putenv('APP_ROUTES_CACHE=/tmp/app/routes.php');
    putenv('APP_EVENTS_CACHE=/tmp/app/events.php');
    $_ENV['APP_SERVICES_CACHE'] = '/tmp/app/services.php';
    $_ENV['APP_PACKAGES_CACHE'] = '/tmp/app/packages.php';
    $_ENV['APP_CONFIG_CACHE'] = '/tmp/app/config.php';
    $_ENV['APP_ROUTES_CACHE'] = '/tmp/app/routes.php';
    $_ENV['APP_EVENTS_CACHE'] = '/tmp/app/events.php';
    $_SERVER['APP_SERVICES_CACHE'] = '/tmp/app/services.php';
    $_SERVER['APP_PACKAGES_CACHE'] = '/tmp/app/packages.php';
    $_SERVER['APP_CONFIG_CACHE'] = '/tmp/app/config.php';
    $_SERVER['APP_ROUTES_CACHE'] = '/tmp/app/routes.php';
    $_SERVER['APP_EVENTS_CACHE'] = '/tmp/app/events.php';
}

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('api/*')) {
                // Return a json response directly if the request acts like an API
                // This stops Laravel from trying to boot up a "login" webpage view or route
                response()->json(['message' => 'Unauthenticated.'], 401)->send();
                exit;
            }
            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Intercept ANY error and print it as JSON immediately to avoid "Target class [view] does not exist"
        $exceptions->renderable(function (\Throwable $e, Request $request) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'real_error_message' => $e->getMessage(),
                'real_error_file' => $e->getFile(),
                'real_error_line' => $e->getLine()
            ]);
            exit;
        });
    })->create();

if (isset($_ENV['VERCEL']) || isset($_ENV['VERCEL_URL'])) {
    $app->useStoragePath('/tmp');
}

// Force Registering the View provider to fix the Target Class [view] doesn't exist error in Vercel.
$app->register(\Illuminate\View\ViewServiceProvider::class);

return $app;
