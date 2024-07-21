<?php

define('__BASE_DIR__', dirname(dirname(__DIR__)));

final class CCC
{
  const APP_NAME = 'lara';

  const BASE_DIR = __BASE_DIR__; // = request()->root()
  const DIR_FD = __BASE_DIR__ . '/fd';
  const DIR_BD = __BASE_DIR__ . '/bd';
  const DIR_LARAVEL = __BASE_DIR__ . '/bd/laravel';

  const FILE_CFG_APP = '/storage/framework/config-app.php';
  const FILE_DEBUG = '/storage/framework/debug.json';
  const FILE_VIEW_CACHE = '/storage/framework/view-cache.json';
  const FILE_VIEW_CACHE_CLEAR = '/storage/framework/view-cache-clear.json';
  const STORAGE_FILE_CFG_APP = self::DIR_LARAVEL . self::FILE_CFG_APP;
  const STORAGE_FILE_DEBUG = self::DIR_LARAVEL . self::FILE_DEBUG;
  const STORAGE_FILE_VIEW_CACHE = self::DIR_LARAVEL . self::FILE_VIEW_CACHE;
  const STORAGE_FILE_VIEW_CACHE_CLEAR = self::DIR_LARAVEL . self::FILE_VIEW_CACHE_CLEAR;

  const STORAGE_LOCAL_PRIVATE_FILES_PATH = self::DIR_LARAVEL . '/storage/app';
  const STORAGE_LOCAL_PUBLIC_FILES_PATH = __BASE_DIR__ . '/storage';

  const KEEP_PATH = __BASE_DIR__ . '/bd/_keep_';
  const VIEWS_PATH = __BASE_DIR__ . '/bd/views';
  const RSS_PATH = __BASE_DIR__ . '/bd/rss';
  const CLI_PATH = __BASE_DIR__ . '/bd/cli';

  const PHP_CLI = 'php';
  const NODE_CLI = 'node';

  // パスワードリセットなどのチャレンジタイム(秒)
  const RESETPASS_CHALLENGE_TIME = 60 * 10;

  const REGEX_PASSWORD = '/^[a-zA-Z0-9!"#$%&\'()\\-=^~@\\[;:\\],.\\/\\|`{+*}<>?_]{6,1024}$/';
  const VALIDATE_PASSWORD = ['required', 'min:6', 'max:1024', 'regex:'.self::REGEX_PASSWORD];
  const VALIDATE_EMAIL = ['required', 'email', 'max:100'];
}
