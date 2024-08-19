<?php

namespace Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
    //
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    // Add some variable for Blade
    view()->composer('*', function($view){
      $view_info = [
        'name' => $view->getName(),
        'path' => $view->getPath(),
        'modified' => app()['files']->lastModified($view->getPath()),
      ];
      $css = new class($view_info) {
        private $id_count = 0;
        public $props = [];
        public function __construct(public $view_info){}
        public function getId()
        {
          $this->id_count++; 
          return 't'.sha1($this->view_info["path"].$this->view_info["modified"].$this->id_count);
        }
      };
      view()->share('css', $css);
    });

    // Add css directive
    \Blade::directive('css', function ($expression) {
      return '<?php
        $id = uniqid();
        $class = '.($expression ?: '""').';
        $__env->startSection("$id|$class");
      ?>';
    });

    \Blade::directive('endcss', function ($expression) {
      return '<?php
        $id = $__env->stopSection();
        $style = $__env->yieldContent($id); 
        $class = explode("|", $id)[1];
        if ($class) {
          $style = ".$class{\n$style\n}";
        }
        $__env->startPush("__style-css", $style);

        $hash = $__env->yieldPushContent("__style-hash");
        if (!$hash || strpos($hash, $css->view_info["path"].$css->view_info["modified"]) === false) {  
          $__env->startPush("__style-hash", $css->view_info["path"].$css->view_info["modified"]);
        }
      ?>';
    });

    \Blade::directive('stackcss', function ($expression) {
      return '<?php
        $style = $__env->yieldPushContent("__style-css");
        if ($style) {
          $hash = sha1($__env->yieldPushContent("__style-hash") ?: $style);
          $key = "cache/scss_inline_cache/".substr($hash, 0, 2)."/".substr($hash, 2, 2)."/".$hash;
          if (\HQ::cache()->has($key)) {
            $style = \HQ::cache()->get($key);
          } else {
            $style = Compilers::scss()->inline($style, options: ["minify" => 1]);
            if (isset($style["error"])) {
              $style = "/*\n{$style["error"]}\n*/";
            } else {
              \HQ::cache()->put($key, $style, 60*60*24*14);
            }
          }
          echo "<style>$style</style>";
        }
      ?>';
    });

    //
    \View::addLocation(\HQ::getenv('CCC::VIEWS_PATH'));

    //
    if (is_file(\HQ::getenv('CCC::STORAGE_FILE_VIEW_CACHE_CLEAR'))) {
      @unlink(\HQ::getenv('CCC::STORAGE_FILE_VIEW_CACHE_CLEAR'));
      \File::deleteDirectory(app()['config']['view.compiled'], true);
    }

    // set config values
    config(\Arr::dot(require(\HQ::getConfigFile())));
    date_default_timezone_set(config('app.timezone', 'UTC'));

    if (!is_file(\HQ::getenv('CCC::FILE_APP_KEY'))) {
      @mkdir(dirname(\HQ::getenv('CCC::FILE_APP_KEY')), 0777, true);
      $key = base64_encode(\Illuminate\Support\Str::random(32));
      @file_put_contents(\HQ::getenv('CCC::FILE_APP_KEY'), "base64:$key");
    }
    
    config([
      'app.key' => @file_get_contents(\HQ::getenv('CCC::FILE_APP_KEY')),
      'app.debug' => \HQ::getDebugMode() || \HQ::getenv('debug'),
      'filesystems.disks.local.root' => \HQ::getenv('CCC::STORAGE_LOCAL_PRIVATE_FILES_PATH'),
      'filesystems.disks.public.root' => \HQ::getenv('CCC::STORAGE_LOCAL_PUBLIC_FILES_PATH'),
      'view.cache' => \HQ::getViewCacheMode(),
      'session.cookie' => \HQ::getAppSlug().'_session',
      'session.path' => \HQ::getCookiePath(),
      'debugbar.storage.open' => true,
      'debugbar.collectors.log' => true,
      'debugbar.inject' => \HQ::getDebugbarShowAlways() || \HQ::getenv('debug'),
    ]);

    //
    \HQ::onBoot();
  }
}
