<?php

// use Illuminate\Foundation\Inspiring;
// use Illuminate\Support\Facades\Artisan;

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote')->hourly();

//
\Artisan::command('cache_gc', function () {
  \HQ::cache_gc_proc();
  $this->info('cache_gc command finished.');
});

\HQ::onConsole(); // ※
 