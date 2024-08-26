<?php

namespace Utils;

final class DB
{
  public static function count($connect, $select, $params = [])
  {
    $f = 'count(*)';
    $r = \DB::connection($connect)->select("SELECT $f $select", $params);
    if (!isset($r[0])) return null;
    return (int)$r[0]->$f;
  }
}
