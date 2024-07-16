
@props([
  'title' => 'NO TITLE',
  'header' => '',
])

<?php
  // Dynamic SCSS
  $style = $__env->yieldPushContent('style');
  if ($style) {
    $hash = sha1($style);
    $key = 'scss_inline_cache/'.substr($hash, 0, 2).'/'.substr($hash, 2, 2).'/'.$hash;
    if (Unsta\KVEFile::exists($key)) {
      $style = Unsta\KVEFile::fetch($key);
    } else {
      $style = Compilers::scss()->inline($style, options: ['minify' => 1]);
      $style = new Illuminate\View\ComponentSlot('<style>' . $style . '</style>');
      Unsta\KVEFile::store($key, $style, 60*60*24*14, 'raw');
    }
  }
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="initial-scale=1, width=device-width" />
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="{{request()->root()}}/fd/css/normalize.css">
    <link rel="stylesheet" href="{{request()->root()}}/fd/css/preflight.css">
    <link rel="stylesheet" href="{{request()->root()}}/fd/css/style.css">
    {{ $header }}
    {{ $style }}
  </head>
  <body>
    {{ $slot }}
  </body>
</html>
