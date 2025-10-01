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
          return 't'.hash('xxh64', $this->view_info["path"].$this->view_info["modified"].$this->id_count);
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
          $hash = hash("xxh128", $__env->yieldPushContent("__style-hash") ?: $style);

          $props = '. ($expression ?: '[]') . ';
          if (isset($props["inline"]) || isset($props["no-style-tag"])) { // no-style-tag は deprecated
            // 文字列で出力
            $key = "cache/scss_inline_cache/".substr($hash, 0, 2)."/".substr($hash, 2, 2)."/".$hash;
            if (\HQ::cache()->has($key)) {
              $style = \HQ::cache()->get($key);
            } else {
              $style = str_replace(["<style>", "</style>"], "", $style);
              $style = Compilers::scss()->inline($style, options: ["minify" => 1]);
              if (isset($style["error"])) {
                $style = "/*\n{$style["error"]}\n*/";
              } else {
                \HQ::cache()->put($key, $style, 60*60*24*14);
              }
            }
            echo $style;
            return;
          }

          // css ファイルに出力
          $dir = \CCC::DIR_FD . "/cache";
          $fn = $dir . "/__stackcss_" . $hash . ".css";
          if (!is_file($fn)) {
            $style = str_replace(["<style>", "</style>"], "", $style);
            $style = Compilers::scss()->inline($style, options: ["minify" => 1]);
            if (isset($style["error"])) {
              $style = "/*\n{$style["error"]}\n*/";
            }
            if (!is_dir($dir)) {  
              mkdir($dir, 0755, true);
            }
            file_put_contents($fn, $style);    
          }
          $fn = url("") . "/fd/cache/__stackcss_" . $hash . ".css";
          echo "<link rel=\"stylesheet\" href=\"$fn\">";
        }
      ?>';
    });

    //
    if (is_file(\HQ::getenv('CCC::STORAGE_FILE_VIEW_CACHE_CLEAR'))) {
      @unlink(\HQ::getenv('CCC::STORAGE_FILE_VIEW_CACHE_CLEAR'));
      \File::deleteDirectory(app()['config']['view.compiled'], true);
    }

    // set config values
    $configFile = app()->getCachedConfigPath();
    $configFileCustom = \HQ::getConfigFile();
    if (!is_file($configFile) || filemtime($configFile) !== filemtime($configFileCustom)) {
      
      $configCustom = require(\HQ::getConfigFile());
      config(\Arr::dot($configCustom));
      if (isset($configCustom['app']['timezone'])) {
        date_default_timezone_set($configCustom['app']['timezone']);
      }
  
      if (!is_file(\HQ::getenv('CCC::FILE_APP_KEY'))) {
        @mkdir(dirname(\HQ::getenv('CCC::FILE_APP_KEY')), 0777, true);
        $key = base64_encode(\Illuminate\Support\Str::random(32));
        @file_put_contents(\HQ::getenv('CCC::FILE_APP_KEY'), "base64:$key");
      }

      \View::addLocation(\HQ::getenv('CCC::VIEWS_PATH'));

      config([
        'app.key' => @file_get_contents(\HQ::getenv('CCC::FILE_APP_KEY')),
        'app.debug' => \HQ::getDebugMode() || \HQ::getenv('debug'),
        'filesystems.disks.local.root' => \HQ::getenv('CCC::STORAGE_LOCAL_PRIVATE_FILES_PATH'),
        'filesystems.disks.public.root' => \HQ::getenv('CCC::STORAGE_LOCAL_PUBLIC_FILES_PATH'),
        'view.cache' => \HQ::getViewCacheMode(),
        // 'view.paths' => [
        //   \HQ::getenv('CCC::VIEWS_PATH'),
        //   resource_path('views'),
        // ],
        'session.cookie' => \HQ::getAppSlug().'_session',
        'session.path' => \HQ::getCookiePath(),
        'debugbar.storage.open' => true,
        'debugbar.collectors.log' => true,
        'debugbar.inject' => \HQ::getDebugbarShowAlways() || \HQ::getenv('debug'),
      ]);
  
      $config = var_export(config()->all(), true);
      \File::put($configFile, "<?php return array_replace_recursive(".$config.", require(__DIR__.'/config1.php'), [
        'app' => [
          'key' => @file_get_contents(\HQ::getenv('CCC::FILE_APP_KEY')),
          'debug' => \HQ::getDebugMode() || \HQ::getenv('debug'),
        ],
        'filesystems' => [
          'disks' => [
            'local' => [
              'root' => \HQ::getenv('CCC::STORAGE_LOCAL_PRIVATE_FILES_PATH'),
            ],
            'public' => [
              'root' => \HQ::getenv('CCC::STORAGE_LOCAL_PUBLIC_FILES_PATH'),
            ],
          ],
        ],
        'view' => [
          'cache' => \HQ::getViewCacheMode(),
          'paths' => [
            \HQ::getenv('CCC::VIEWS_PATH'),
            resource_path('views'),
          ],
        ],
        'session' => [
          'cookie' => \HQ::getAppSlug().'_session',
          'path' => \HQ::getCookiePath(),
        ],
        'debugbar' => [
          'storage' => [
            'open' => true,
          ],
          'collectors' => [
            'logs' => true,
          ],
          'inject' => \HQ::getDebugbarShowAlways() || \HQ::getenv('debug'),
        ],  
      ]);");

      \touch($configFile, filemtime($configFileCustom));          
      \File::copy($configFileCustom, \File::dirname($configFile).'/config1.php');
    }

    //
    \HQ::onBoot();
  }
}
