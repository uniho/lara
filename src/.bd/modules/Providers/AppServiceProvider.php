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
        'app.debug' => \HQ::getDebugMode(),
        'filesystems.disks.local.root' => \HQ::getenv('CCC::STORAGE_LOCAL_PRIVATE_FILES_PATH'),
        'filesystems.disks.public.root' => \HQ::getenv('CCC::STORAGE_LOCAL_PUBLIC_FILES_PATH'),
        'view.cache' => \HQ::getViewCacheMode(),
        // 'view.paths' => [
        //   \HQ::getenv('CCC::VIEWS_PATH'),
        //   resource_path('views'),
        // ],
        'session.cookie' => \HQ::getAppSlug().'_session',
        'session.path' => \HQ::getCookiePath(),
      ]);
  
      $config = var_export(config()->all(), true);
      \File::put($configFile, "<?php return array_replace_recursive(".$config.", require(__DIR__.'/config1.php'), [
        'app' => [
          'key' => @file_get_contents(\HQ::getenv('CCC::FILE_APP_KEY')),
          'debug' => \HQ::getDebugMode(),
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
      ]);");

      \touch($configFile, filemtime($configFileCustom));          
      \File::copy($configFileCustom, \File::dirname($configFile).'/config1.php');
    }

    //
    \HQ::onBoot();
  }
}
