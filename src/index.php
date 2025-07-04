<?php
// v12.250621

define('LARAVEL_START', microtime(true));

// Check If The Application Is Under Maintenance
if (!is_dir(__DIR__.'/.bd/laravel/storage/framework')) {
  header ('HTTP/1.0 503 Service Temporarily Unavailable');
  exit();
}
if (is_file(__DIR__.'/.bd/laravel/storage/framework/down')) {
  $query = [];
  parse_str($_SERVER['QUERY_STRING'], $query);
  if (isset($query['secret4down'])) {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $_SERVER['REQUEST_URI'] = $path . $query['secret4down'];
  }
}

// .bd/modules ディレクトリ内のモジュールをオートロードする
require_once '.bd/modules/autoload.php';

// .bd/laravel/vendor ディレクトリ内のモジュールをオートロードする
require_once '.bd/laravel/vendor/autoload.php';

\HQ::onStart();

$app = require_once '.bd/laravel-ext/bootstrap/app.php';
$app->handleRequest(\Illuminate\Http\Request::capture());

\HQ::onFinish();
