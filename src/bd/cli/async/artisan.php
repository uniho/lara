#!/usr/bin/env php
<?php

define('LARAVEL_START', microtime(true));

// Register the Composer autoloader...
require __DIR__.'/../../modules/autoload.php';
require __DIR__.'/../../laravel/vendor/autoload.php';

// Bootstrap Laravel and handle the command...
$app = require_once __DIR__.'/../../laravel-ext/bootstrap/app.php';
$status = $app->handleCommand(new \Symfony\Component\Console\Input\ArgvInput);

exit($status);
