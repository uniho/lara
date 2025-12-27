<?php

final class HQ
{
  const ERRMSG_NOT_AUTHORIZED = 'You are not authorized to access this page.';

  private static $env = []; 
  private static $APP_SLUG = 'lara';
  private static $COOKIE_PATH = false;
  private static $caches = [];
  private static $keep_caches = [];
  private static $array_caches = [];

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

  public static function onMiddleware($middleware)
  {
    if (self::on_exists() && method_exists(\HQ\On::class, 'onMiddleware')) {
      \HQ\On::onMiddleware($middleware);
    }
  }

  public static function onExceptions($exceptions)
  {
    if (self::on_exists() && method_exists(\HQ\On::class, 'onExceptions')) {
      \HQ\On::onExceptions($exceptions);
    }
  }

  public static function onProviders()
  {
    $result = [];
    if (self::on_exists() && method_exists(\HQ\On::class, 'onProviders')) {
      $result = \HQ\On::onProviders();
    }
    return is_array($result) ? $result : [$result];
  }

  public static function webOrigin($request)
  {
    if ($request->has('rest_route')) {
      require_once __DIR__.'/../rest-api/cmds/autoload.php';
      require_once __DIR__.'/../rest-api/RestApi.php';
      $request->headers->set('Accept', 'application/json');
      return \RestApi\Procedures::handle($request);
    }

    return false;
  }

  public static function getenv($name, $default = null)
  {
    if (isset(self::$env[$name])) {
      return self::$env[$name];
    }

    if (substr($name, 0, 5) === 'CCC::') {
      return defined($name) ? constant($name) : $default;
    }

    return $default;
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
    return !is_dir(\CCC::DIR_LARAVEL.'/storage/framework') || is_file(\CCC::DIR_LARAVEL.'/storage/framework/down');
  }

  public static function setMaintenanceMode($data)
  {
    $lock_key = 'maintenance_mode_update_lock';

    if ($data['secret'] ?? false) {
      if (app()->isDownForMaintenance()) { 
          return; 
      }

      \Cache::lock($lock_key, 10)->get(function() {
        app()->maintenanceMode()->activate($data);

        // It doesn't matter, maybe.
        // file_put_contents(
        //   storage_path('framework/maintenance.php'),
        //   file_get_contents(__DIR__.'/../laravel/vendor/laravel/framework/src/Illuminate/Foundation/Console/stubs/maintenance-mode.stub')
        // );

      });  
      return;
    }

    if (!app()->isDownForMaintenance()) {
      return;
    }

    \Cache::lock($lock_key, 10)->get(function() {
      app()->maintenanceMode()->deactivate();
      @unlink(storage_path('framework/maintenance.php')); // just to make sure
    });  
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
      $arr = explode('|', $user);
      if (!isset($arr[0]) || !isset($arr[1])) return null;
      if (isset($arr[2]) && !self::array_cache("SUPER-USER-HQ_{$arr[0]}")->has($arr[2])) {
        // 他の端末で全端末ログアウトした場合など
        session()->forget('SUPER-USER-HQ');
        session()->invalidate();
        session()->regenerateToken();
        return null;
      }
      return $arr[0];
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

    $cache = self::array_cache("SUPER-USER-HQ_{$user}")->get($uid);
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

    $uid = (string)\Str::uuid();
    session(['SUPER-USER-HQ' => "$user|$expire|$uid"]);
    session()->regenerate();

    // Remember Me
    $token = \Str::random(60);
    self::array_cache("SUPER-USER-HQ_{$user}")->put($uid, "$token|$expire", intval($expire)/* sec */);
    self::array_cache("SUPER-USER-HQ_{$user}")->gc();
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
    if ($cookie) {
      $cookie_arr = explode('|', $cookie);
      if (isset($cookie_arr[1])) {
        // 全端末でログアウト
        self::array_cache("SUPER-USER-HQ_{$cookie_arr[1]}")->flush();
      }
    }
    cookie()->queue(
      cookie()->forget(self::getAppSlug().'_SUPER-USER-HQ')
    );
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
    return self::$COOKIE_PATH || \Symfony\Component\HttpFoundation\Request::createFromGlobals()->getBasePath() || '/';
  }

  public static function setCookiePath($path) {
    self::$COOKIE_PATH = $path;
  }

  public static function cache($type = 'serialize') {
    if (!isset(self::$caches[$type])) {
      self::$caches[$type] = new \Illuminate\Cache\Repository(
        new \Unsta\FileStore(app()['files'], storage_path("_HQ_"), $type)
      );
    }
    return self::$caches[$type];
  }

  public static function keep_cache($type = 'serialize') {
    if (!isset(self::$keep_caches[$type])) {
      self::$keep_caches[$type] = new \Illuminate\Cache\Repository(
        new \Unsta\FileStore(app()['files'], self::getenv('CCC::KEEP_PATH').'/cache', $type)
      );
    }
    return self::$keep_caches[$type];
  }

  public static function array_cache($key) {
    if (!isset(self::$array_caches[$key])) {
      self::$array_caches[$key] = new \Illuminate\Cache\Repository(
        new \Unsta\ArrayStore(cache(), $key)
      );
    }
    return self::$array_caches[$key];
  }

  public static function cache_gc($force = false)
  {
    if ($force || cache()->add('rate_limit_cache_gc', 1, 60*60*24)) {
      \Utils\AsyncCLI::runArtisan("cache_gc");
    }
  }

  public static function cache_gc_proc()
  {
    foreach (self::$caches as $cache) {
      $cache->gc(0);
    }
    foreach (self::$keep_caches as $cache) {
      $cache->gc(0);
    }
    foreach (self::$array_caches as $cache) {
      $cache->gc();
    }
    \Unsta\FloodControl::gc();
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

  public static function jwtCreate($secret, $expire = 60, $claims = [])
  {
    $key = \Lcobucci\JWT\Signer\Key\InMemory::plainText($secret);
    $config = \Lcobucci\JWT\Configuration::forSymmetricSigner(new \Lcobucci\JWT\Signer\Hmac\Sha256(), $key);
    $now = new \Carbon\CarbonImmutable();
    $kigen = match(true) {
      is_string($expire) =>
        $now->modify($expire), // '+100 seconds', '+7 day' など
      (is_int($expire) || is_numeric($expire)) && (int)$expire > 0 =>
        $now->addSeconds((int)$expire),
      default =>
        throw new \Exception('bad jwt expire'),
    };

    $builder = $config->builder()
      // ->issuedBy('https://your-issuer.example.com')        // iss
      // ->permittedFor('https://your-audience.example.com') // aud
      ->issuedAt($now)                                    // iat
      ->canOnlyBeUsedAfter($now)                          // nbf
      ->expiresAt($kigen) // exp
      // ->withClaim('uid', 123) // ユーザーIDなど
    ;

    if (!isset($claims['jti'])) {
      $claims['jti'] = bin2hex(random_bytes(8));
    }

    foreach ($claims as $key => $value) {
      $builder = match($key) {
        'sub' => $builder->relatedTo($value), // Configures the subject of the token (sub claim)
        'jti' => $builder->identifiedBy($value),
        default => $builder->withClaim($key, $value)
      };
    }

    return $builder->getToken($config->signer(), $config->signingKey())->toString();
  }

  //
  public static function jwtValidate($secret, $jwt)
  {
    $key = \Lcobucci\JWT\Signer\Key\InMemory::plainText($secret);
    $config = \Lcobucci\JWT\Configuration::forSymmetricSigner(new \Lcobucci\JWT\Signer\Hmac\Sha256(), $key);

    $token = $config->parser()->parse($jwt);

    $signed = $config->validator()->validate(
      $token,
      new \Lcobucci\JWT\Validation\Constraint\SignedWith($config->signer(), $config->verificationKey())
    );

    // 有効期限を手動チェック
    $exp = $token->claims()->get('exp')->getTimestamp();
    $nbf = $token->claims()->get('nbf')->getTimestamp();
    $nowTs = (new \DateTimeImmutable())->getTimestamp();

    if ($signed && $nowTs >= $nbf && $nowTs <= $exp) {
      return $token->claims()->all();
    };
    
    throw new \Exception('bad jwt token');
  }
}
