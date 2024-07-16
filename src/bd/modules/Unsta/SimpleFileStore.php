<?php

namespace Unsta;

class SimpleFileStore extends \Illuminate\Cache\FileStore
{
  public function __construct($directory, $filePermission = null)
  {
    parent::__construct(app()['files'], $directory, $filePermission);
  }

  public function path($key)
  {
    return $this->directory.'/'.$key;
  }
}
