<?php

define('LARAVEL_START', microtime(true));

// Check If The Application Is Under Maintenance
if (is_file(__DIR__.'/bd/laravel/storage/framework/down')) {
  $query = [];
  parse_str($_SERVER['QUERY_STRING'], $query);
  if (isset($query['secret4down'])) {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $_SERVER['REQUEST_URI'] = $path . $query['secret4down'];
  }
}

// bd/modules ディレクトリ内のモジュールをオートロードする
require_once 'bd/modules/autoload.php';

// bd/laravel/vendor ディレクトリ内のモジュールをオートロードする
require_once 'bd/laravel/vendor/autoload.php';

\HQ::onStart();

$app = require_once 'bd/laravel-ext/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$request = \Illuminate\Http\Request::capture();
$response = $kernel->handle($request)->send();
$kernel->terminate($request, $response);

\HQ::onFinish();
