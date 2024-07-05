<?php

final class CachedConfig
{
  protected static $KEY; 

  public static function __init()
  {
    self::$KEY = 'CACHED_CONFIG'; 
  }

  public static function get($key)
  {
    $config = \Unsta\KVEFile::fetch(self::$KEY);
    if (!isset($config[$key])) return null;
    return $config[$key];
  }

  public static function set($key, $val)
  {
    $config = \Unsta\KVEFile::fetch(self::$KEY);
    if (!$config) $config = [];
    $config[$key] = $val;
    return \Unsta\KVEFile::store(self::$KEY, $config);
  }

  public static function exists($key)
  {
    $config = \Unsta\KVEFile::fetch(self::$KEY);
    return isset($config[$key]);
  }

  public static function delete($key)
  {
    $config = \Unsta\KVEFile::fetch(self::$KEY);
    if (!isset($config[$key])) return true;
    unset($config[$key]);
    return \Unsta\KVEFile::store(self::$KEY, $config);
  }
}

CachedConfig::__init();
