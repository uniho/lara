<?php

namespace Utils;

final class AsyncCLI
{
  public static function runArtisan($params = '')
  {
    $php = \HQ::getenv('CCC::PHP_CLI');
    $cmd = \HQ::getenv('CCC::CLI_PATH') . '/async/artisan.php ' . $params;
    exec("$php $cmd > /dev/null &");
  }
}
