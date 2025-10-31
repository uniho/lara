<?php

namespace Rest\v1\internal;

class hello {

    //
    public static function get($request) {
        return ['data' => $request['_claims']];
    }

    //
    public static function post($request) {
        return ['data' => []];
    }

}
