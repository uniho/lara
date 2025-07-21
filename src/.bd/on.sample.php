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
    \HQ::setenv('CCC::APP_NAME', 'Test App!');
    // \HQ::setAppSlug('lara');
    // \HQ::setCookiePath('/');

    \HQ::setDebugMode(true);
    \HQ::setViewCacheMode(!\HQ::getDebugMode());
    \HQ::setDebugShowSource(false); // <====== For security, the default value is false!
    \HQ::setDebugbarShowAlways(false); // <====== For security, the default value is false!

    // \HQ::setenv('superUserSecret', '');

    // \HQ::setenv('CCC::PHP_CLI', '/usr/bin/php');
    // \HQ::setenv('CCC::NODE_CLI', '~/.nvm/versions/node/v20.16.0/bin/node');

    // CSS breakpoints from bootstrap's default
    \HQ::setenv('STYLES::breakpoints', [
      'sm' => 576, 'md' => 768, 'lg' => 992, 'xl' => 1200, '2xl' => 1400,
    ]);
  } 
  
  // Called from index.php
  public static function onFinish()
  {
    // // Clear Batch Table
    // if (cache()->add('rate_imit_on_prune-batches', true, 60*60*24)) {
    //   \Utils\AsyncCLI::runArtisan('queue:prune-batches');
    // }
  } 
  
  // Called from laravel-ext/bootstrap/app.php ->withMiddleware
  // You cant use `debug()` yet.
  public static function onMiddleware($middleware)
  {
    // dump($middleware);

    // Trusted Hosts の設定
    // デフォルトは指定なし。
    // $middleware->trustHosts(at: [ ... ], subdomains: false); で許可するホスト名を指定。at: callback でもよい。
    // なお、config.env.url を使用しないので subdomains: false が必須。
    // $middleware->trustHosts(at: ['test.com'], subdomains: false);

    // Trusted Proxies の設定
    // デフォルトは指定なし。
    // $middleware->trustProxies(at: [ ... ]); でIPアドレスを指定。
    // $middleware->trustProxies(headers: ...); でProxynoのアドレスを取得するヘッダー名を指定。
    // $middleware->trustProxies(at: ['230.123.2.211']);
    // $middleware->trustProxies(headers: xxx);
  } 
  
  // Called from laravel-ext/bootstrap/app.php ->withExceptions
  public static function onExceptions($exceptions)
  {
  } 
  
  // Called from App\Providers\AppServiceProvider::boot()
  public static function onBoot()
  {
    \Log::debug(\HQ::getenv('CCC::APP_NAME') . ' boot!');

    \HQ::setMaintenanceMode(0);
    // \HQ::setMaintenanceMode(5, [
    //   'secret' => 'your secret key',
    //   'template' => view('sample.message', [
    //     'title' => 'Page Under Maintenance',
    //     'message' => 'Sorry for the inconvenience but we’re performing some maintenance at the moment.',
    //   ])->render(),
    // ]);
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
    // admin
    \Route::prefix('admin')->group(function() {
      include __DIR__."/web_routes/admin.php";

      // Admin root
      \Route::get('/', fn() => redirect('admin/check'));
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

    // tailwind css 使用例
    \Route::get('tailwindcss', function () {
      try {
        if ($contents = \HQ::cache()->get('cache/__tailwindcss__')) {
          // from cache
          return response($contents, 200)->header('Content-Type', 'text/css; charset=utf-8');
        }

        $response = \Http::get('https://unpkg.com/tailwindcss@2.2.19/dist/tailwind.css');
        $response->throw();
        $body = $response->body();
        
        // 使いそうなものだけ抽出
        $contents = '';
        foreach ([
          ['h-0', 'max-w-screen-2xl'], // height, width
          ['m-0', '-ml-3\\\\.5'], // margin
          ['p-0', 'pl-3\\\\.5'], // padding
          ['visible', 'invisible'], // visibility
          ['block', 'hidden'], // display
          ['flex-1', 'flex-grow'], // flex
          ['flex-row', 'justify-items-stretch'], // flex
          ['order-1', 'order-none'], // flex
          ['gap-0', 'gap-y-3\\\\.5'], // flex, grid
          ['place-content-center', 'justify-items-stretch'], // flex, grid
          ['self-auto', 'justify-self-stretch'], // flex, grid
        ] as $item) {
          $start = $item[0]; $end = $item[1];
          if (!preg_match("/\s+(\.$start\s*{[\s\S]+?}[\s\S]+\.$end\s*{[\s\S]+?})\s+/", $body, $m)) {
            throw new \Exception(".$start not found");
          }
          $contents .= $m[1];
        }

        // media screen の追加
        preg_match_all("/\.([\w, -]+\s*{[\s\S]+?})/", $contents, $m);
        foreach (\HQ::getenv('STYLES::breakpoints') as $key => $size) {
          $contents .= "@media screen and (min-width : {$size}px) {\n"; // 'min-width' means mobile first.
          foreach ($m[1] as $i) {
            $contents .= ".$key\\:$i\n";
          }
          $contents .= "}\n";
        }

        $contents = \Compilers::scss()->inline($contents, [], ['minify' => 1]);
        \HQ::cache('raw')->forever('cache/__tailwindcss__', $contents);

        return response($contents, 200)->header('Content-Type', 'text/css; charset=utf-8');
      } catch(\Exception $e) {
        return response("/*\n\nERROR on /tailwindcss :\n{$e->getMessage()}\n\n*/", 200)->header('Content-Type', 'text/css; charset=utf-8');
      }
    });

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
        'args' => '--minify-whitespace --minify-identifiers --loader:.js=tsx',
      ]);
      return response($contents, 200)->header('Content-Type', 'application/javascript; charset=utf-8');
    })->where('name', '.*');

    // Markdown 使用例
    \Route::get('markdown/{name}', function ($name) {
      abort_unless(\Compilers::markdown()->exists($name), 404, "Markdown [{$name}] not found.");
      $contents = \Compilers::markdown($name, [], [
        'force_compile' => \HQ::getDebugMode() || request()->has('force_compile'),
        'markdown' => [
          'config' => [
            'renderer' => [
              'soft_break' => "<br/>\n", // soft break to hard break, like github comment
            ],
          ],
        ],
      ]);
      return response($contents, 200)->header('Content-Type', 'text/plain; charset=utf-8');
    })->where('name', '.*');

    // mdx 使用例 
    \Route::get('mdx/{name}', function ($name) {
      $ext = substr($name, strrpos($name, '.') + 1);
      if ($ext == 'map') {
        $name = app()['config']['view.compiled'] . '/' . basename($name);
        abort_unless(is_file($name) && \HQ::getDebugMode(), 404, "MAP [{$name}] not found.");
        $contents = \File::get($name);
        return response($contents, 200)->header('Content-Type', 'application/json; charset=utf-8');
      }

      abort_unless(\Compilers::mdx()->exists($name), 404, "MDX [{$name}] not found.");
      $contents = \Compilers::mdx($name, [], [
        'force_compile' => \HQ::getDebugMode() || request()->has('force_compile'), 
        'args' => '--minify-whitespace --minify-identifiers --loader:.js=tsx',
      ]);
      return response($contents, 200)->header('Content-Type', 'application/javascript; charset=utf-8');
    })->where('name', '.*');

    // Blade 使用例 
    \Route::get('blade/{name}', function ($name) {
      $body = $name;
      $ext = '';
      $p = strrpos($name, '.');
      $ext = false;
      if ($p !== false) {
        $body = strtr(substr($name, 0, $p), '/', '.');
        $ext = substr($name, $p);
      }
      $body = strtr($body, '.', '/');
      $fn = \HQ::getenv('CCC::RSS_PATH') . "/blade/$body$ext.blade.php";
      abort_unless(is_file($fn), 404, "blade [{$name}] not found.");
      $r = response(view()->file($fn)->render(), 200); // $data dosen't need.
      return match($ext) {
        '.js' => $r->header('Content-Type', 'application/javascript; charset=utf-8'),
        '.css' => $r->header('Content-Type', 'text/css; charset=utf-8'),
        default => $r
      };
    })->where('name', '.*');

    // // Root の例
    // \Route::get('/{name?}', function($name = null) {
    //   if (($r = \HQ::webOrigin(request())) !== false) return $r;
    //   if ($name) abort(404);
    //   return 'Root!';
    // })->where('name', '.*');

  }
}
