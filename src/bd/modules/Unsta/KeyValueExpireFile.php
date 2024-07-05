<?php

namespace Unsta;

class KeyValueExpireFile
{
  protected $databasePath;
  protected $sysID;
  protected $dirMode = 0700;
  protected $fileMode = 0600;

  public function __construct(string $databasePath = '')
  {
    $databasePath = trim($databasePath);
    if (empty($databasePath)) {
      throw new \Exception('data directory can not be empty');
    }
    $this->databasePath = $databasePath;

    // if (!is_dir($this->databasePath)) {
    //   mkdir($this->databasePath, $this->dirMode, true);
    // }

    $this->sysID = '_kvef_sys_';
  }

  protected function keyencode($key)
  {
    return str_ireplace('%2f', DIRECTORY_SEPARATOR, urlencode($key));
  }

  public function fetch($key, bool &$success = null, int &$expire = null)
  {
    $success = false;
    if (!$key) return false;
    $key = $this->keyencode($key);
    $fn = $this->databasePath.DIRECTORY_SEPARATOR.$key;
    clearstatcache(true, $fn);
    $h = @fopen($fn, 'r');
    if ($h === false) return false;
    try {
      if (!flock($h, LOCK_SH)) return false;
      $fsize = filesize($fn);
      if ($fsize === false) return false;
      if ($fsize > 0) {
        $expire = intval(fread($h, 10));
        if ($expire === 0) return false; // 0 == Error on intval()
        $type = fread($h, 3);
        if ($type === false) return false;
        $data = fread($h, $fsize);
        if ($data === false) return false;
        $data = match($type) {
          'nil' => null,
          'fls' => false,
          'jsn' => json_decode($data, true), 
          'jso' => json_decode($data), 
          'raw' => $data,
          'srl' => unserialize($data),
          default => false,
        };
        if ($type != 'fls' && $data === false) return false;
        if ($expire > time()) {
          $success = true;
          return $data;
        }
      }
      // fclose($h);
      // $h = false;
      // $this->_delete($key);
      return false;
    } finally {
      if ($h) fclose($h);
    }
  }

  public function exists($key)
  {
    $success = false;
    $this->fetch($key, $success);
    return $success;
  }

  public function store($key, $value, int $expire, $storeType = 'json')
  {
    if (!$key) return false;
    $key = $this->keyencode($key);
    $fn = $this->databasePath.DIRECTORY_SEPARATOR.$key;
    $fsize = false;
    $h = @fopen($fn, 'c');
    if ($h === false) {
      $dir = dirname($fn);
      if (is_dir($dir)) return false;
      @mkdir($dir, $this->dirMode, true);
      $h = @fopen($fn, 'c');
      if ($h === false) return false;
    }
    if (!flock($h, LOCK_EX)) return false;
    $fsize = filesize($fn);
    if ($fsize === false) return false;

    try {
      if ($expire > 0) $expire += time();
      $expire = $expire <= 1000000000 || $expire > 9999999999 ? 9999999999 : $expire;
      if (fwrite($h, $expire) === false) return false;
      $data = match($value) {
        null => 'nil',
        false => 'fls',
        default => match($storeType) {
          'json' => 'jsn', 
          'raw', 'string' => 'raw',
          default => 'srl',
        },
      };
      if (fwrite($h, $data) === false) return false;
      $data = match($data) {
        null => true,
        false => true,
        'jsn' => fwrite($h, json_encode($value)), 
        'raw' => fwrite($h, $value),
        default => fwrite($h, serialize($value)),
      };
      if ($data !== false) {
        ftruncate($h, ftell($h));
        fflush($h);
        if ($fsize === 0) {
          fclose($h);
          @chmod($fn, $this->fileMode);
          $h = false;
        }
        return true;
      }
      return false;
    } finally {
      if ($h) fclose($h);
    }
  }

  public function add($key, $value, int $expire = 0, $storeType = 'json')
  {
    if ($this->exists($key)) return false;
    return $this->store($key, $value, $expire, $storeType);
  }

  public function delete($key)
  {
    if (!$key) return false;
    $key = $this->keyencode($key);
    $fn = $this->databasePath.DIRECTORY_SEPARATOR.$key;
    $h = @fopen($fn, 'w');
    if ($h === false) return false;
    if ($h) fclose($h);
    return !is_file($fn) || @unlink($fn);
  }

  public function _preg_delete($dir, $pattern = false, $reg = false, $expiredOnly = false)
  {
    $keys = glob($dir.DIRECTORY_SEPARATOR.($pattern ? $pattern : '*'), GLOB_NOSORT);
    foreach ($keys as $fn) {
      if (is_dir($fn)) {
        if ($fn == $this->databasePath.DIRECTORY_SEPARATOR.$this->sysID) {
          continue;
        }
       
        // 検索順序によっては空ではないと判定されるがまあよしとする
        $isEmpty = (function($dir) {
          $handle = @opendir($dir);
          if (!$handle) return false;
          try {
            while (false !== ($entry = readdir($handle))) {
              if ($entry != "." && $entry != "..") {
                return false;
              }
            }
          } finally {
            closedir($handle);
          }
          return true;
        })($fn);

        if ($isEmpty) {
          rmdir($fn);
        }

        continue;
      }

      $base = urldecode(basename($fn));
      if ($reg && !preg_match($reg, $base)) continue;
      if ($expiredOnly) {
        $h = @fopen($fn, 'r+');
        if ($h === false) continue;
        try {
          if (!flock($h, LOCK_EX | LOCK_NB)) continue;
          $fsize = filesize($fn);
          if ($fsize === false) continue;
          if ($fsize) {
            $expire = intval(fread($h, 10));
            if ($expire === 0) continue; // 0 == Error on intval()
            if ($expire > time()) continue;
            if (!ftruncate($h, 0)) continue;
          }
        } finally {
          if ($h) fclose($h);
        }
      }
      @unlink($fn);
    }
  }

  public function preg_delete($pattern = false, $reg = false, $expiredOnly = false)
  {
    $this->_preg_delete($this->databasePath, $pattern, $reg, $expiredOnly);
  }

  public function gc(int $period = 60*60*24)
  {
    if (!$this->add($this->sysID.'/GC', 1, $period)) return false;
    $this->preg_delete(false, false, true);
    return true;
  }
}
