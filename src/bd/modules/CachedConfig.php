<?php

final class CachedConfig
{
  protected static $KEY = '__CACHED_CONFIG'; 

  public static function __init($key = false)
  {
    self::$KEY = $key ?: self::$KEY; 
  }

  public static function get($key)
  {
    $config = \HQ::keep_cache()->get(self::$KEY);
    if (!isset($config[$key])) return null;
    return $config[$key];
  }

  public static function set($key, $val)
  {
    $config = \HQ::keep_cache()->get(self::$KEY);
    if (!$config) $config = [];
    $config[$key] = $val;
    return \HQ::keep_cache()->put(self::$KEY, $config);
  }

  public static function exists($key)
  {
    $config = \HQ::keep_cache()->get(self::$KEY);
    return isset($config[$key]);
  }

  public static function delete($key)
  {
    $config = \HQ::keep_cache()->get(self::$KEY);
    if (!isset($config[$key])) return true;
    unset($config[$key]);
    return \HQ::keep_cache()->put(self::$KEY, $config);
  }
}

CachedConfig::__init();
