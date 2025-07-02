
@props([
  'title' => 'NO TITLE',
  'header' => '',
  'root' => \JJJ::relativeRoot(),
])

<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="initial-scale=1, width=device-width" />
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="{{$root}}/fd/css/preflight.css">
    <link rel="stylesheet" href="{{$root}}/fd/css/style.css">
    {{ $header }}
    @stackcss
  </head>
  <body>
    {{ $slot }}
  </body>
</html>
