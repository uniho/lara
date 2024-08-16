<?php

final class HQ
{
  private static $env = []; 
  private static $APP_SLUG = 'lara';
  private static $COOKIE_PATH = '/';
  private static $caches = [];
  private static $keep_caches = [];

  public static function onStart()
  {
    if (self::on_exists() && method_exists(\HQ\On::class, 'onStart')) {
      \HQ\On::onStart();
    }
  }

  public static function onFinish()
  {
    if (self::on_exists() && method_exists(\HQ\On::class, 'onFinish')) {
      \HQ\On::onFinish();
    }
  }

  public static function onBoot()
  {
    if (self::on_exists() && method_exists(\HQ\On::class, 'onBoot')) {
      \HQ\On::onBoot();
    }
  }

  public static function onConsole()
  {
    if (self::on_exists() && method_exists(\HQ\On::class, 'onConsole')) {
      \HQ\On::onConsole();
    }
  }

  public static function onWeb($router)
  {
    if (self::on_exists() && method_exists(\HQ\On::class, 'onWeb')) {
      \HQ\On::onWeb($router);
    }
  }

  public static function webOrigin($request)
  {
    if ($request->has('rest_route')) {
      require_once __DIR__.'/../rest/cmds/autoload.php';
      require_once __DIR__.'/../rest/RestApi.php';
      $request->headers->set('Accept', 'application/json');
      return \RestApi\Procedures::handle($request);
    }

    if ($request->has('view_route')) {
      $name = $request->query('view_route');
      abort_unless(view()->exists($name), 404, "View [{$name}] not found.");
      return view($name); // $data dosen't need.
    }

    if ($request->method() == 'GET') {

      if (basename(url()->current()) == 'debugbar.php') {
        if (self::getDebugMode() && self::isAdminUser()) {
          if ($request->has('phpinfo')) {
            phpinfo();
            exit();
          }
          return view('welcome');
        }
        debugbar()->disable();
        return App::abort(403);
      }

    }

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
    }

    if (view()->exists('sample.index')) {
      return view('sample.index');
    }

    return \App::abort(404);
  }

  public static function getenv($name)
  {
    if (isset(self::$env[$name])) {
      return self::$env[$name];
    }

    if (substr($name, 0, 5) === 'CCC::') {
      $refClass = new ReflectionClass(\CCC::class);
      $consts = $refClass->getConstants();
      return $consts[substr($name, 5)] ?? null;
    }
  }

  public static function setenv($name, $val)
  {
    self::$env[$name] = $val;
  }

  public static function getDebugMode(): bool
  {
    return is_file(self::getenv('CCC::STORAGE_FILE_DEBUG'));
  }

  public static function setDebugMode(bool $mode)
  {
    if ($mode) {
      if (self::getDebugMode()) return;
      file_put_contents(self::getenv('CCC::STORAGE_FILE_DEBUG'), '1');
    } else {
      if (!self::getDebugMode()) return;
      @unlink(self::getenv('CCC::STORAGE_FILE_DEBUG'));
    }
  }

  public static function getViewCacheMode(): bool
  {
    return is_file(self::getenv('CCC::STORAGE_FILE_VIEW_CACHE'));
  }

  public static function setViewCacheMode(bool $mode)
  {
    if ($mode) {
      if (self::getViewCacheMode()) return;
      file_put_contents(self::getenv('CCC::STORAGE_FILE_VIEW_CACHE'), '1');
      file_put_contents(self::getenv('CCC::STORAGE_FILE_VIEW_CACHE_CLEAR'), '1');
    } else {
      if (!self::getViewCacheMode()) return;
      @unlink(self::getenv('CCC::STORAGE_FILE_VIEW_CACHE'));
    }
  }

  public static function getMaintenanceMode()
  {
    if (app()->isDownForMaintenance()) {
      return 5;
    }

    if (!\CachedConfig::exists('$$__maintenance')) {
      return 0;
    } 

    return \CachedConfig::get('$$__maintenance')['level'];
  }

  public static function getMaintenanceData()
  {
    if (app()->isDownForMaintenance()) {
      return app()->maintenanceMode()->data();
    }

    if (!\CachedConfig::exists('$$__maintenance')) {
      return false;
    } 

    return \CachedConfig::get('$$__maintenance');
  }

  public static function setMaintenanceMode($level, $data = [])
  {
    if (is_int($level) && $level >= 5) {
      app()->maintenanceMode()->activate($data);

      // It doesn't matter, maybe.
      // file_put_contents(
      //   storage_path('framework/maintenance.php'),
      //   file_get_contents(__DIR__.'/../laravel/vendor/laravel/framework/src/Illuminate/Foundation/Console/stubs/maintenance-mode.stub')
      // );

      return;
    }

    if (app()->isDownForMaintenance()) {
      app()->maintenanceMode()->deactivate();
      @unlink(storage_path('framework/maintenance.php')); // just to make sure
    }

    if ($level) {
      $data['level'] = $level;
      \CachedConfig::set('$$__maintenance', $data);
    } else {
      \CachedConfig::delete('$$__maintenance');
    }
  }

  public static function getDebugShowSource()
  {
    return isset(self::$env['debugShowSource']);
  }

  public static function setDebugShowSource($mode)
  {
    if (!$mode) {
      unset(self::$env['debugShowSource']);
      return;
    }
    self::$env['debugShowSource'] = true;
  }

  public static function getDebugbarShowAlways()
  {
    return isset(self::$env['debugbarShowAlways']);
  }

  public static function setDebugbarShowAlways($mode)
  {
    if (!$mode) {
      unset(self::$env['debugbarShowAlways']);
      return;
    }
    self::$env['debugbarShowAlways'] = true;
  }

  public static function isAdminUser() {
    $user = \Auth::user();
    return 
      (!$user && \HQ::getSuperUser()) ||
      ($user && ($user->id == 0 || (class_exists('\Models\UserEx') && \Models\UserEx::find($user->id)->isAdmin())))
    ;
  }

  public static function getSuperUser()
  {
    $user = session('SUPER-USER-HQ');
    if ($user) {
      return $user;
    }

    // Remember Me
    $cookie = \Cookie::get(self::getAppSlug().'_SUPER-USER-HQ');
    if (!$cookie) {
      return null;
    }

    $cookie_arr = explode('|', $cookie);
    if (count($cookie_arr) != 3) {
      return null;
    }
    $uid = $cookie_arr[0];
    $user = $cookie_arr[1];
    $secret = $cookie_arr[2];

    $cache = self::cache()->get("SUPER-USER-HQ_{$user}_{$uid}");
    if (!$cache) {
      return null;
    }
    
    $cache_arr = explode('|', $cache);
    if (!isset($cache_arr[0]) || !isset($cache_arr[1])) {
      return null;
    }

    if ($cache_arr[0] === $secret) {
      self::updateSuperUser($user, intval($cache_arr[1]));
      return $user;
    }

    return null;
  }

  public static function updateSuperUser($user = 'super', $expire = 60 * 60 * 24 * 1)
  {
    if (!$user) return false;

    session(['SUPER-USER-HQ' => $user]);
    session()->regenerate();

    // Remember Me
    $uid = \Str::uuid();
    $token = \Str::random(60);
    self::cache()->put("SUPER-USER-HQ_{$user}_{$uid}", "$token|$expire", intval($expire)/* sec */);
    self::cache_gc();
    cookie()->queue(
      cookie(self::getAppSlug().'_SUPER-USER-HQ', "$uid|$user|$token", intval($expire/60)/* min */)
    );

    return true;
  }

  public static function logoutSuperUser()
  {
    session()->forget('SUPER-USER-HQ');
    session()->invalidate();
    session()->regenerateToken();

    $cookie = \Cookie::get(self::getAppSlug().'_SUPER-USER-HQ');
    cookie()->queue(
      cookie()->forget(self::getAppSlug().'_SUPER-USER-HQ')
    );
    if (!$cookie) return null;

    $cookie_arr = explode('|', $cookie);
    if (count($cookie_arr) != 3) return null;
    self::cache()->forget("SUPER-USER-HQ_{$cookie_arr[1]}_{$cookie_arr[0]}");
  }

  public static function rateLimitForTheBruteForceAttack($key, $wait)
  {
    if (cache()->has($key)) {
      sleep($wait);
    }
    cache()->put($key, true, $wait);
  }

  public static function getConfigFile(): string
  {
    $file = __DIR__.'/../config.php';
    if (is_file($file)) {
      return $file;
    } 
    return __DIR__.'/../config.sample.php';
  }

  public static function getAppSlug() {
    return self::$APP_SLUG;
  }

  public static function setAppSlug($slug) {
    self::$APP_SLUG = $slug;
  }

  public static function getCookiePath() {
    return self::$COOKIE_PATH;
  }

  public static function setCookiePath($path) {
    self::$COOKIE_PATH = $path;
  }

  public static function cache($options = []) {
    $type = $options['type'] ?? 'serialize';

    if ($options['keep'] ?? false) {
      if (!isset(self::$keep_caches[$type])) {
        self::$keep_caches[$type] = new \Illuminate\Cache\Repository(
          new \Unsta\FileStore(app()['files'], self::getenv('CCC::KEEP_PATH'), $type)
        );
      }
      return self::$keep_caches[$type];
    }

    if (!isset(self::$caches[$type])) {
      self::$caches[$type] = new \Illuminate\Cache\Repository(
        new \Unsta\FileStore(app()['files'], storage_path("_HQ_"), $type)
      );
    }
    return self::$caches[$type];
  }

  public static function cache_gc()
  {
    if (cache()->add('rate_limit_cache_gc', 1, 60*60*24)) {
      \Utils\AsyncCLI::runArtisan("cache_gc");
    }
  }

  private static function on_exists(): bool
  {
    if (!class_exists('\HQ\On::class', false)) {
      $file = __DIR__.'/../on.php';
      if (!is_file($file)) return false;
      include_once($file);
    }
    return true;
  }
}
