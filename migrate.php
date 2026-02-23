<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
\Illuminate\Support\Facades\Artisan::call("migrate", ["--force" => true]);
echo \Illuminate\Support\Facades\Artisan::output();
