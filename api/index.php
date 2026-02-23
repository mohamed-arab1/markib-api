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
    // Correctly reconstruct the URI for Laravel when running in Vercel's Serverless environment.
    // Sometimes Vercel passes `/auth/login` and sometimes `/api/auth/login` to the entrypoint
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $parsedUrl = parse_url($uri, PHP_URL_PATH);
    
    // Ensure it always starts with /api because this is the API entrypoint
    // and Laravel 11's withRouting(api: ...) automatically prefixes `api/` to all routes in routes/api.php
    if (!str_starts_with($parsedUrl, '/api')) {
        $_SERVER['REQUEST_URI'] = '/api' . ($parsedUrl === '/' ? '' : $parsedUrl) . (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '');
    }
    
    // Some Vercel setups need SCRIPT_NAME to be explicitly index.php for correct routing
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    
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

