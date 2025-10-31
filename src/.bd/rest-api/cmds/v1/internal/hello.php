<?php

namespace Rest\v1\internal;

class hello {

    //
    public static function get($request) {
        return ['data' => 'Hello!'];
    }

    //
    public static function post($request) {
        return ['data' => []];
    }

}
