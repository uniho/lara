<?php

namespace Unsta;

use Exception;
use Illuminate\Contracts\Filesystem\LockTimeoutException;
use Illuminate\Filesystem\LockableFile;

class ArrayStore extends \Illuminate\Cache\ArrayStore
{
  public function __construct(private $cache, private $key)
  {
    parent::__construct();
  }

  public function get($key)
  {
    $this->storage = $this->getStorage();
    return parent::get($key);
  }

  public function put($key, $value, $seconds)
  {
    $this->storage = $this->getStorage();
    parent::put($key, $value, $seconds);
    $this->putStorage($this->storage);
    return true;
  }

  public function increment($key, $value = 1)
  {
    $this->storage = $this->getStorage();
    $r = parent::increment($key, $value);
    $this->putStorage($this->storage);
    return $r;
  }

  public function forget($key)
  {
    $this->storage = $this->getStorage();
    $r = parent::forget($key, $value);
    $this->putStorage($this->storage);
    return $r;
  }

  public function preg_forget($reg)
  {
    $array = $this->getStorage();
    $newArray = [];
    $forgets = [];
    foreach ($array as $key => $item) {
      if ($reg && preg_match($reg, $key)) {
        $forgets[] = $key;        
        continue;
      }
      if (!$item['expiresAt'] || $this->currentTime() < $item['expiresAt']) {
        $newArray[$key] = $item;
      }
    }  
    $this->putStorage($newArray);
    return $forgets;
  }

  public function flush()
  {
    $this->cache->forget($this->key);
    return true;
  }

  public function gc()
  {
    $this->preg_forget(false);
  }

  public function getStorage()
  {
    return $this->cache->get($this->key) ?: [];
  }

  public function putStorage($payload)
  {
    if (is_array($payload) && count($payload)) {
      $this->cache->forever($this->key, $payload);
    } else {
      $this->cache->forget($this->key);
    }
  }
}
