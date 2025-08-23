<?php

namespace RestApi;

final class Procedures 
{
  const PREG_QUERY = '{^/v(?P<_ver>[\d]+)/(?P<_role>[\w\d\-]+)/(?P<_cmd>[\w\d-]+)/(?P<_arg>[^\/\?&]+)(?:/|&|$)}';
  const ERRMSG_NOT_AUTHORIZED = 'You are not authorized to access this page.';

  //
  public static function handle($request)
  {
    try {
      if (preg_match(self::PREG_QUERY, $request->query('rest_route'), $match)) {
        foreach ($match as $key => $value) {
          if (!is_int($key)) $request->query->add([$key => $value]);
        }  
        $request->query->remove('rest_route');

        $role = $request['_role'];
        if ($role === 'internal') {
          $ips = request()->ips();
          $ipAddr = $ips[array_key_last($ips)];
          $allowed = \HQ::getenv('INTERNAL_REST_API_ALLOWED_IPS', []);
          $headerKey = $request->header('X-Internal-Rest-Api-Key');
          $secretKey = \HQ::getenv('INTERNAL_REST_API_KEY');
          if (!$headerKey || $headerKey !== $secretKey || !in_array($ipAddr, $allowed)) {
            throw new \Exception(self::ERRMSG_NOT_AUTHORIZED);
          }
        } else if ($role != 'anon') {
          if (!self::hasRole($role)) {
            throw new \Exception(self::ERRMSG_NOT_AUTHORIZED);
          }
        }

        $cmd = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $request['_cmd'])))); // camel case
        $class = "\\Rest\\v{$request['_ver']}\\$role\\$cmd";

        switch ($request->method()) {
          case 'GET':
            if ($cmd == 'token') {
              return ['data' => $request->session()->token()];
            }
            if (!class_exists($class)) throw new \Exception('bad cmd');
            return $class::get($request);

          case 'POST':
            if (!class_exists($class)) throw new \Exception('bad cmd');
            return $class::post($request);

        }
        throw new \Exception('bad method');
      }
      throw new \Exception('bad route');
    } catch (\Exception $e) {
      return ['error' => ['message' => $e->getMessage()]];
    }
  }

  //
  public static function check($request)
  {
    if (preg_match(self::PREG_QUERY, $request->query('rest_route'), $match)) {
      $result = [];
      foreach ($match as $key => $value) {
        if (!is_int($key)) $result[$key] = $value;
      }  
      return $result;
    }
    return false;
  }

  //
  public static function isAdmin()
  {
    $user = \Auth::user();
    return \HQ::getSuperUser() || ($user && class_exists('\Models\UserEx') && \Models\UserEx::find($user->id)->isAdmin());
  }

  //
  public static function hasRole($role)
  {
    $user = \Auth::user();
    return \HQ::getSuperUser() || ($user && class_exists('\Models\UserEx') && \Models\UserEx::find($user->id)->hasRole($role));
  }
}
