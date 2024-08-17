<?php

namespace Unsta;

use Exception;
use Illuminate\Contracts\Filesystem\LockTimeoutException;
use Illuminate\Filesystem\LockableFile;

class CacheStore extends \Illuminate\Cache\ArrayStore
{
  public function __construct(private $cache, private $key)
  {
    parent::__construct();
  }

  public function get($key)
  {
    $array = $this->cache->get($this->key);
    if (!$array) return null;
    $this->storage = $array;
    return parent::get($key);
  }

  public function put($key, $value, $seconds)
  {
    $array = $this->cache->get($this->key);
    $this->storage = $array ?: [];
    parent::put($key, $value, $seconds);
    $this->cache->forever($this->key, $this->storage);
    return true;
  }

  public function increment($key, $value = 1)
  {
    $array = $this->cache->get($this->key);
    $this->storage = $array ?: [];
    $r = parent::increment($key, $value);
    $this->cache->forever($this->key, $this->storage);
    return $r;
  }

  public function forget($key)
  {
    $array = $this->cache->get($this->key);
    $this->storage = $array ?: [];
    $r = parent::forget($key, $value);

    if ($this->storage && count($this->storage)) {
      $this->cache->forever($this->key, $this->storage);
    } else {
      $this->cache->forget($this->key);
    }

    return $r;
  }

  public function flush()
  {
    $this->cache->forget($this->key);
    return true;
  }

  public function gc()
  {
    $array = $this->cache->get($this->key);
    if (!$array) return;
    $newArray = [];
    foreach ($array as $item) {
      if ($this->currentTime() < $item['expiresAt']) {
        $newArray[] = $item;
      }
    }  
    if (count($newArray)) {
      $this->cache->forever($this->key, $newArray);
    } else {
      $this->cache->forget($this->key);
    }
  }

}
