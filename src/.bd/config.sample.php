<?php

return [

  'app' => [
    
    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => 'UTC',
  ],

  'mail' => defined('SSS::email') ? (\SSS::email ?? null) : null,

  'logging' => [
    'default' => 'daily',
    'channels' => [
      'daily' => [
        'driver' => 'daily',
        'path' => \HQ::getenv('CCC::KEEP_PATH').'/logs/laravel.log',
        'level' => 'debug',
        'days' => 90,
        'replace_placeholders' => true,
      ],
    ],
  ],

  'log-viewer' => [
    'include_files' => [
      \HQ::getenv('CCC::KEEP_PATH').'/logs/*.log' => 'keep',
    ],
  ],

];
