<?php
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Vendor autoload is missing. Composer install did not run on Vercel.']);
    exit;
}

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require __DIR__ . '/../public/index.php';
