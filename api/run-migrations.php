<?php
// TEMPORARY MIGRATION SCRIPT - DELETE AFTER USE

// Simple security token to prevent unauthorized access
$token = $_GET['token'] ?? '';
if ($token !== 'markib-migrate-2024') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Set maximum execution time
set_time_limit(120);

if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    echo json_encode(['error' => 'Vendor autoload missing']);
    exit;
}

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/plain');
echo "=== Running Migrations ===\n\n";

try {
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    echo \Illuminate\Support\Facades\Artisan::output();
    echo "\n=== Done! ===\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
