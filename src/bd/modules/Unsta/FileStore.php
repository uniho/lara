<?php

namespace Unsta;

use Exception;
use Illuminate\Contracts\Filesystem\LockTimeoutException;
use Illuminate\Filesystem\LockableFile;

class FileStore extends \Illuminate\Cache\FileStore
{
  public function __construct($directory, private $storeType = 'serialize', $filePermission = null)
  {
    parent::__construct(app()['files'], $directory, $filePermission);
  }

  public function put($key, $value, $seconds)
  {
    return $this->putPayload($key, $value, $seconds);
  }

  public function add($key, $value, $seconds)
  {
    return $this->putPayload($key, $value, $seconds, true);
  }

  public function path($key)
  {
    return $this->directory.'/'.$key;
  }

  protected function getPayload($key)
  {
    $path = $this->path($key);

    try {
      $file = new LockableFile($path, 'r');
    } catch (Exception) {
      return false;
    }

    try { 
      try {
        $file->getExclusiveLock();
      } catch (LockTimeoutException) {
        return false;
      }

      try {
        if (is_null($expire = $file->read(13))) {
          return $this->emptyPayload();
        }
      } catch (Exception) {
        return $this->emptyPayload();
      }
  
      $st = substr($expire, 10);
      $expire = substr($expire, 0, 10);

      if ($this->currentTime() >= $expire) {
        $this->forget($key);
        return $this->emptyPayload();
      }
  
      try {
        $data = $file->read();
        $data = match($st) {
          'nil' => null,
          'fls' => false,
          'jsn' => json_decode($data, true), 
          'jso' => json_decode($data), 
          'raw' => $data,
          'srl' => unserialize($data),
          default => false,
        };
      } catch (Exception) {
        $this->forget($key);
        return $this->emptyPayload();
      }
  
      $time = $expire - $this->currentTime();

      return compact('data', 'time');

    } finally {
      $file->close();
    }  
  }

  protected function putPayload($key, $value, $seconds, $isAdd = false)
  {
    $this->ensureCacheDirectoryExists($path = $this->path($key));

    $file = new LockableFile($path, 'c+');

    try { 
      try {
        $file->getExclusiveLock();
      } catch (LockTimeoutException) {
        return false;
      }

      if ($isAdd) {
        $expire = $file->read(10);
      }

      if (!$isAdd || empty($expire) || $this->currentTime() >= $expire) {

        $st = match($value) {
          null => 'nil',
          false => 'fls',
          default => match($this->storeType) {
            'json' => 'jsn', 
            'raw', 'string' => 'raw',
            default => 'srl',
          },
        };

        $file->truncate()
          ->write($this->expiration($seconds).$st);

        match($st) {
          'nil', 'fls' => true,
          'jsn' => $file->write(json_encode($value)), 
          'raw' => $file->write($value),
          default => $file->write(serialize($value)),
        };
    
        $this->ensurePermissionsAreCorrect($path);

        return true;
      }

      return false;

    } finally {
      $file->close();
    }
  }

}
