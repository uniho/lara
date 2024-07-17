<?php

namespace Unsta;

final class FloodControl
{
  private static $cache;

  public static function initialize($cache)
  {
    self::$cache = $cache;
  }

  public static function register($name, int $window = 3600, $identifier = NULL)
  {
    // We can't use REQUEST_TIME here, because that would not guarantee
    // uniqueness.
    $time = time();
    $events = self::$cache->get(self::getKey($name, $identifier));
    if (!$events) $events = [];
    $events = array_filter($events, function ($entry) use ($time) {
      return $entry['expire'] > $time;
    });
    $events[] = ['expire' => $time + $window, 'time' => $time];
    self::$cache->put(self::getKey($name, $identifier), $events, $window);
  }

  public static function clear($name, $identifier = NULL)
  {
    self::$cache->forget(self::getKey($name, $identifier));
  }

  public static function isAllowed($name, int $threshold, int $window = 3600, $identifier = NULL)
  {
    $events = self::$cache->get(self::getKey($name, $identifier));
    if (!$events) {
      return $threshold > 0;
    }
    $time = time();
    $limit = $time - $window;
    // $number = count(array_filter($events, function ($entry) use ($limit) {
    //   return $entry['time'] > $limit;
    // }));
    $number = 0;
    foreach ($events as $e) {
      if ((int)$e['expire'] > $time && (int)$e['time'] > $limit) $number++;
    }
    return ($number < $threshold);
  }

  private static function getKey($name, $identifier)
  {
    return $name . "_" . ($identifier ?: $_SERVER["REMOTE_ADDR"]);
  }
}

//
FloodControl::initialize(
  new \Illuminate\Cache\Repository(
    new \Illuminate\Cache\FileStore(app()['files'], storage_path('_FloodControl_'))
  )
);
