<?php

define('LARAVEL_START', microtime(true));

require_once '.bd/modules/autoload.php';
require_once '.bd/laravel/vendor/autoload.php';

\HQ::onStart();
\HQ::setenv('debug', true);

$app = require_once '.bd/laravel-ext/bootstrap/app.php';
$app->handleRequest(\Illuminate\Http\Request::capture());
