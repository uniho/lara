<?php

// use Illuminate\Foundation\Inspiring;
// use Illuminate\Support\Facades\Artisan;

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote')->hourly();

//
\Artisan::command('cache_gc', function () {
  \HQ::cache()->gc(0);
  \Unsta\FloodControl::gc();
});

\HQ::onConsole(); // ※
 