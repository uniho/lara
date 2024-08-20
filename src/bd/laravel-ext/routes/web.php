<?php

use Illuminate\Http\Request;

//
\Route::any('/adminer', function () {
  abort_unless(\HQ::isAdminUser(), 403);

  $connect = request()->query('connect') ?: config('database.default');
  if (config("database.connections.$connect.driver") == 'mysql') {
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
        'db'        => config("database.connections.$connect.database"),
        // 'permanent' => 1,
      ];
    }
    function adminer_object() {
      require 'bd/vendor/adminer/plugin-without-credentials.php';
      require 'bd/vendor/adminer/plugin.php';
      $plugins = [new PluginWithoutCredentials()];
      return new AdminerPlugin($plugins);
    }
  }
  require 'bd/vendor/adminer/adminer-4.8.1-en.php';
  exit();
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

//
\Route::match(['get', 'post'], '/', function (Request $request) {
  return \HQ::webOrigin($request);
});

//
\HQ::onWeb($router);
