<?php
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Vendor autoload is missing.']);
    exit;
}

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

try {
    // Vercel routes all `/*` to `api/index.php`.
    // If we're executing this script, we're acting as the API entrypoint.
    // Laravel needs the URI to begin with `/api` to match the api routes file, 
    // but sometimes Vercel rewrites strip it or preserve it unexpectedly.
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    if (!str_starts_with($uri, '/api')) {
        $_SERVER['REQUEST_URI'] = '/api' . ($uri === '/' ? '' : $uri);
    }
    
    require __DIR__ . '/../public/index.php';
} catch (\Throwable $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}

