@props([
  'page' => request()->query('page') ?? 1,
])

<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="initial-scale=1, width=device-width" />
    <title>React on Blade Sample</title>
    <link rel="stylesheet" href="{{request()->root()}}/fd/css/normalize.css">
    <link rel="stylesheet" href="{{request()->root()}}/fd/css/preflight.css">
    <link rel="stylesheet" href="{{request()->root()}}/fd/css/style.css">
    <link rel="stylesheet" href="{{request()->root()}}/css/palette">
  </head>
  <body>
    <div id="app"></div>

    <noscript>
      <div style="height:100vh; display:flex; align-items:center; justify-content:center;">You need to enable JavaScript to run this app.</div>
    </noscript>
    
    <script async src="https://unpkg.com/es-module-shims/dist/es-module-shims.js"></script>
    <script type="importmap">
      {
        "imports": {
          "fd/": "./fd/",
          "modules/": "./jsx/modules/",
          "pages/": "./jsx/pages/",
          "jsx/": "./jsx/",
          "~/": "./"
        }
      }
    </script>

    <script type="module">
      import {main} from "fd/sample/main.js";
      main({{ Js::from(['csrf_token' => request()->session()->token(), 'page' => $page]) }});
    </script>

  </body>
</html>
