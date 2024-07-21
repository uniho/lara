<?php

/*
* Rename this file to `on.php` if you want to use this file. 
*/

namespace HQ;

use Illuminate\Http\Request;

class On
{
  // Called from index.php
  public static function onStart()
  {
    // \HQ::setAppSlug('lara');
    // \HQ::setCookiePath('/');

    \HQ::setDebugMode(true);
    \HQ::setViewCacheMode(!\HQ::getDebugMode());
    \HQ::setDebugShowSource(false); // <====== For security, the default value is false!
    \HQ::setDebugbarShowAlways(false); // <====== For security, the default value is false!
  } 
  
  // Called from index.php
  public static function onFinish()
  {
    // // Clear Batch Table
    // if (!\HQ::cache()->has('rate_imit_on_prune-batches')) {
    //   \HQ::cache()->put('rate_imit_on_prune-batches', true, 60*60*24);
    //   \Utils\AsyncCLI::runArtisan('queue:prune-batches');
    // }
  } 
  
  // Called from App\Providers\AppServiceProvider::boot()
  public static function onBoot()
  {
    \HQ::setenv('CCC::APP_NAME', 'Test App!');
    \Log::debug(\HQ::getenv('CCC::APP_NAME') . ' boot!');

    // \HQ::setenv('superUsers', [['name' => '', 'pass' => '']]);

    \HQ::setenv('CCC::NODE_CLI', '~/.nvm/versions/node/v20.15.0/bin/node');
  } 
  
  // Called from routes/console.php
  // Define the application's command schedule and application's command schedule.
  // See
  //   https://laravel.com/docs/11.x/scheduling
  //   https://laravel.com/docs/11.x/artisan#closure-commands 
  public static function onConsole()
  {
    // 例： inspire2 という Artisan Command を追加する
    \Artisan::command('inspire2', function () {
        $this->comment(\Inspiring::quote());
    })->purpose('Display an inspiring quote v2');
  } 

  // Called from laravel/routes/web.php
  public static function onWeb($router)
  {
    \HQ::setMaintenanceMode(0);
    // \HQ::setMaintenanceMode(5, [
    //   'secret' => 'your secret key',
    //   'template' => view('sample.message', [
    //     'title' => 'Page Under Maintenance',
    //     'message' => 'Sorry for the inconvenience but we’re performing some maintenance at the moment.',
    //   ])->render(),
    // ]);

    \Route::prefix('admin')->group(function() {
      // Admin login
      \Route::get('login', function (Request $request) {
        $users = \HQ::getenv('superUsers');
        $user = $users[0]['name'] ?? false;
        $pass = $users[0]['pass'] ?? false;
        if (!$user || !$pass) {
          abort(403);
        }

        if (\HQ::getSuperUser()) {
          return view('sample.message', ['title' => \HQ::getenv('CCC::APP_NAME'), 'message' => 'Already logged in.']);
        }

        // Rate limit for the Brute-force attack
        $SUPER_USER_LOGIN_RATE_LIMIT_KEY = 'rate_limit_super_user_login';
        $SUPER_USER_LOGIN_RATE_LIMIT_WAIT = 3;
        if (\HQ::cache()->has($SUPER_USER_LOGIN_RATE_LIMIT_KEY)) {
          sleep($SUPER_USER_LOGIN_RATE_LIMIT_WAIT);
          \HQ::cache()->forget($SUPER_USER_LOGIN_RATE_LIMIT_KEY);
        }

        if ($request->query('secret') === $pass) {
          \HQ::updateSuperUser($user);
          return view('sample.message', ['title' => \HQ::getenv('CCC::APP_NAME'), 'message' => 'Hello!']);
        }

        \HQ::cache()->put($SUPER_USER_LOGIN_RATE_LIMIT_KEY, true, $SUPER_USER_LOGIN_RATE_LIMIT_WAIT);
        abort(403, "wrong secret");
      });

      // Admin logout
      \Route::get('logout', function (Request $request) {
        \HQ::logoutSuperUser();
        return view('sample.message', ['title' => \HQ::getenv('CCC::APP_NAME'), 'message' => 'Thanks, bye!']);
      });
    });

    // css 使用例
    \Route::get('css/{name}', function ($name) {
      $ext = substr($name, strrpos($name, '.') + 1);
      if ($ext == 'map') {
        $name = app()['config']['view.compiled'] . '/' . basename($name);
        abort_unless(is_file($name) && \HQ::getDebugMode(), 404, "MAP [{$name}] not found.");
        $contents = \File::get($name);
        return response($contents, 200)->header('Content-Type', 'application/json; charset=utf-8');
      }

      abort_unless(\Compilers::scss()->exists($name), 404, "CSS [{$name}] not found.");
      $contents = \Compilers::scss($name, [], [
        'force_compile' => \HQ::getDebugMode() || request()->has('force_compile'),
        'minify' => 1,
      ]);
      return response($contents, 200)->header('Content-Type', 'text/css; charset=utf-8');
    })->where('name', '.*'); // この where により、$name がパスデリミタを受けられるようになる

    // jsx 使用例 
    \Route::get('jsx/{name}', function ($name) {
      $ext = substr($name, strrpos($name, '.') + 1);
      if ($ext == 'map') {
        $name = app()['config']['view.compiled'] . '/' . basename($name);
        abort_unless(is_file($name) && \HQ::getDebugMode(), 404, "MAP [{$name}] not found.");
        $contents = \File::get($name);
        return response($contents, 200)->header('Content-Type', 'application/json; charset=utf-8');
      }

      abort_unless(\Compilers::jsx()->exists($name), 404, "JSX [{$name}] not found.");
      $contents = \Compilers::jsx($name, [], [
        'force_compile' => \HQ::getDebugMode() || request()->has('force_compile'), 
        'minify' => 1,
        // 'tsconfig' => $tsconfig,
      ]);
      return response($contents, 200)->header('Content-Type', 'application/javascript; charset=utf-8');
    })->where('name', '.*');

    // Markdown 使用例
    \Route::get('markdown/{name}', function ($name) {
      abort_unless(\Compilers::markdown()->exists($name), 404, "Markdown [{$name}] not found.");
      $contents = \Compilers::markdown($name, [], ['force_compile' => \HQ::getDebugMode() || request()->has('force_compile')]);
      return response($contents, 200)->header('Content-Type', 'text/plain; charset=utf-8');
    })->where('name', '.*');

  }
}
