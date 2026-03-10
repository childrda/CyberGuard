<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (! file_exists($basePath = dirname(__DIR__).'/vendor/autoload.php')) {
    throw new RuntimeException('Composer dependencies are not installed.');
}

require $basePath;

$app = require_once dirname(__DIR__).'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
