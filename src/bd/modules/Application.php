<?php

class Application extends \Illuminate\Foundation\Application
{
    public function getBootstrapProvidersPath()
    {
        return dirname(__DIR__).'/laravel-ext/bootstrap/providers.php';
    }
}
