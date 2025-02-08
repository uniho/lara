<?php

namespace Unsta;

class ArrayStore extends \Illuminate\Cache\ArrayStore
{
  public function __construct(private $cache, private string $key)
  {
    parent::__construct();
  }

  public function get($key)
  {
  	$lock = $this->block_w();
  	try {
      $this->storage = $this->getStorage();
      return parent::get((string)$key);
    } finally {
      $lock->release();
    }
  }

  public function put($key, $value, $seconds)
  {
  	$lock = $this->block_w();
  	try {
      $this->storage = $this->getStorage();
      parent::put((string)$key, $value, $seconds);
      $this->putStorage($this->storage);
      return true;
    } finally {
      $lock->release();
    }
  }

  public function increment($key, $value = 1)
  {
  	$lock = $this->block_w();
  	try {
      $this->storage = $this->getStorage();
      $r = parent::increment((string)$key, $value);
      $this->putStorage($this->storage);
      return $r;
    } finally {
      $lock->release();
    }
  }

  public function forget($key)
  {
  	$lock = $this->block_w();
  	try {
      $this->storage = $this->getStorage();
      $r = parent::forget((string)$key);
      $this->putStorage($this->storage);
      return $r;
    } finally {
      $lock->release();
    }
  }

  public function preg_forget($reg)
  {
  	$lock = $this->block_w();
  	try {
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
    } finally {
      $lock->release();
    }
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
  
  private function block_w()
  {
    $lock = $this->cache->lock('$_array_store_write_'.$this->key, 10);
    $lock->block(5);
  	return $lock;
  }
}
