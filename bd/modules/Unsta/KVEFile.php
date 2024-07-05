<?php

namespace Unsta;

final class KVEFile
{
  private static $kvef;

  public static function initialize()
  {
    self::$kvef = new KeyValueExpireFile(storage_path('_KVEF_'));
  }

  public static function fetch($key, bool &$success = null, int &$expire = null)
  {
    return self::$kvef->fetch($key, $success, $expire);
  }

  public static function exists($key)
  {
    return self::$kvef->exists($key);
  }

  public static function store($key, $value, int $expire = 0, $storeType = 'json')
  {
    self::$kvef->gc();
    return self::$kvef->store($key, $value, $expire, $storeType);
  }

  public static function add($key, $value, int $expire = 0, $storeType = 'json')
  {
    self::$kvef->gc();
    return self::$kvef->add($key, $value, $expire, $storeType);
  }

  public static function delete($key)
  {
    self::$kvef->gc();
    return self::$kvef->delete($key);
  }

  public static function _preg_delete($dir, $pattern = false, $reg = false, $expiredOnly = false)
  {
    return self::$kvef->_preg_delete($dir, $pattern, $reg, $expiredOnly);
  }

  public static function preg_delete($pattern = false, $reg = false, $expiredOnly = false)
  {
    self::$kvef->preg_delete($pattern, $reg, $expiredOnly);
  }

  public static function gc(int $period = 60*60*24)
  {
    return self::$kvef->gc($period);
  }
}

//
KVEFile::initialize();
