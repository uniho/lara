<?php

namespace RestApi;

final class Procedures 
{
    const PREG_QUERY = '{^/v(?P<_ver>[\d]+)/(?P<_role>[\w\d\-]+)/(?P<_cmd>[\w\d-]+)/(?P<_arg>[^\/\?&]+)(?:/|&|$)}';

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
                    if (!defined('SSS::JWT_SECRET_FOR_INTERNAL_REST_API')) {
                        throw new \Exception(\HQ::ERRMSG_NOT_AUTHORIZED);
                    }
                    
                    $i = false;
                    if ($request->has('_sindex')) {
                        $i = $request['_sindex'];
                        if (!isset(\SSS::JWT_SECRET_FOR_INTERNAL_REST_API[$i]['key'])) {
                            throw new \Exception('bad _sindex');
                        }
                    } else {
                        $i = array_key_first(\SSS::JWT_SECRET_FOR_INTERNAL_REST_API);
                    }

                    $secret = \SSS::JWT_SECRET_FOR_INTERNAL_REST_API[$i]['key'];
                    $claims = \HQ::jwtValidate($secret, $request->bearerToken());

                    if (
                      // sub のチェック  
                      (isset(\SSS::JWT_SECRET_FOR_INTERNAL_REST_API[$i]['sub']) &&
                      \SSS::JWT_SECRET_FOR_INTERNAL_REST_API[$i]['sub'] && $claims['sub'] &&
                      \SSS::JWT_SECRET_FOR_INTERNAL_REST_API[$i]['sub'] !== $claims['sub'])
                        ||
                      // jti のチェック  
                      (isset(\SSS::JWT_SECRET_FOR_INTERNAL_REST_API[$i]['jti']) &&
                      \SSS::JWT_SECRET_FOR_INTERNAL_REST_API[$i]['jti'] && $claims['jti'] &&
                      \SSS::JWT_SECRET_FOR_INTERNAL_REST_API[$i]['jti'] !== $claims['jti'])
                        ||
                      // 有効期限が10分を超えるものは許可しない
                      ($claims['exp']->getTimestamp() - $claims['nbf']->getTimestamp() > 10 * 60)
                    ) {
                        throw new \Exception(\HQ::ERRMSG_NOT_AUTHORIZED);
                    }

                    $request->query->add(['_claims' => $claims]);

                } else if ($role != 'anon') {
                    if (!self::hasRole($role)) {
                        throw new \Exception(\HQ::ERRMSG_NOT_AUTHORIZED);
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
