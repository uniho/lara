<?php

use Illuminate\Http\Request;

//
\HQ::onWeb($router);

//
\Route::any('/adminer', function () {
  abort_unless(\HQ::isAdminUser(), 403);

  $connect = request()->query('connect') ?: config('database.default');
  if (substr($connect, 0, 1) == '/') {
    $driver = 'sqlite';
  } else {
    $driver = config("database.connections.$connect.driver");
  }
  abort_unless($driver, 404, 'unknown connet');
  if ($driver == 'mysql') {
    if (!isset($_GET['db'])) {
      $_POST['auth'] = [
        'driver'    => 'server',
        'server'    => config("database.connections.$connect.host").':'.config("database.connections.$connect.port"),
        'username'  => config("database.connections.$connect.username"),
        'password'  => config("database.connections.$connect.password"),
        'db'        => config("database.connections.$connect.database"),
        // 'permanent' => 1,
      ];
    }
  } else {
    $_GET['sqlite'] = '';
    if (!isset($_GET['db'])) {
      $_POST['auth'] = [
        'driver'    => 'sqlite',
        'db'        => substr($connect, 0, 1) == '/' ? dirname(base_path())."/database$connect.sqlite" : config("database.connections.$connect.database"),
        // 'permanent' => 1,
      ];
    }
    function adminer_object() {
      require 'bd/vendor/adminer/plugin-without-credentials.php';
      return new Adminer\Plugins([new PluginWithoutCredentials()]);
    }
  }
  require 'bd/vendor/adminer/adminer-5.3.0-en.php';
  exit();
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

//
\Route::match(['get', 'post'], '/', function (Request $request) {
  if (($r = \HQ::webOrigin($request)) !== false) return $r;

  if (view()->exists('index')) {
    return view('index');
  }

  if (is_file('./fd/index.html')) {
    $path = 'fd/';
    if ($request->query()) {
      $path .= '?'.Arr::query($request->query());
    }
    header("Location: $path");
    exit();
    return;
  }

  if (view()->exists('sample.index')) {
    return view('sample.index');
  }

  abort(404);
});
