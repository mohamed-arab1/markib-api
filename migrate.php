<?php

/**
 * This script is called by Vercel's Build Command to run database migrations.
 * It runs before the deployment goes live.
 */

echo "=== Markib Migration Script ===\n";
echo "PHP Version: " . PHP_VERSION . "\n\n";

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "ERROR: vendor/autoload.php not found. Composer install may not have run.\n";
    exit(1);
}

require __DIR__ . '/vendor/autoload.php';

// Setup storage directories for Vercel
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

putenv('APP_SERVICES_CACHE=/tmp/app/services.php');
putenv('APP_PACKAGES_CACHE=/tmp/app/packages.php');
putenv('APP_CONFIG_CACHE=/tmp/app/config.php');
putenv('APP_ROUTES_CACHE=/tmp/app/routes.php');
putenv('APP_EVENTS_CACHE=/tmp/app/events.php');

$app = require_once __DIR__ . '/bootstrap/app.php';

try {
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    echo "Running migrations...\n";
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    $output = \Illuminate\Support\Facades\Artisan::output();
    echo $output ?: "No new migrations to run.\n";
    echo "\n=== Migrations complete ===\n";
    exit(0);
} catch (\Throwable $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    // Don't exit(1) — we don't want migration failure to block deployment
    exit(0);
}
