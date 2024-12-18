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

  'debugbar' => [
    'options' => [
      'logs' => [
        'file' => [
          \HQ::getenv('CCC::KEEP_PATH').'/logs/laravel-' . date('Y-m-d') . '.log',
          \HQ::getenv('CCC::KEEP_PATH').'/logs/laravel-' . date('Y-m-d', strtotime('-1 day')) . '.log',
          \HQ::getenv('CCC::KEEP_PATH').'/logs/laravel-' . date('Y-m-d', strtotime('-2 day')) . '.log',
          \HQ::getenv('CCC::KEEP_PATH').'/logs/laravel-' . date('Y-m-d', strtotime('-3 day')) . '.log',
        ],
      ],
    ],
  ],

  'mail' => [
    'mailers' => [
      'smtp' => [
        'host' =>  'your smtp domain',
        'port' => 587,
        'username' => 'your smtp user',
        'password' => 'your smtp pass',
      ],
    ],
  ],

];
