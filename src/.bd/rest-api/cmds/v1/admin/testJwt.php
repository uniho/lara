<?php

namespace Rest\v1\admin;

class testJwt { 

    //
    public static function get($request)
    {
        $jwt = \HQ::jwtCreate(head(\SSS::JWT_SECRET_FOR_INTERNAL_REST_API)['key'], 60*10, ['sub' => 'test']);

        // 本来的には別のサーバーに投げるものであるが、自分に投げてる
        $response = \Http::withToken($jwt)
          ->get('https://yanoco.jp/lara', [
            'rest_route' => '/v1/internal/hello/-',
          ])
        ;

        $response->throw();

        if (!$response->ok()) {
            throw new \Exception('bad test');
        }

        $data = $response->json();

        return $data;
    }

    //
    public static function post($request) {
        return ['data' => []];
    }

}
