<?php

/*
* Rename this file to `on.php` if you want to use this file. 
*/

namespace HQ;

use Illuminate\Http\Request;

class On
{
    // Called from index.php
    public static function onStart()
    {
        include __DIR__.'/.flags.php';
        include __DIR__.'/.secrets.php';

        \HQ::setenv('CCC::APP_NAME', 'Test App!');

        \HQ::setAppSlug('lara');
        \HQ::setCookiePath('/lara'); // サブディレクトリに導入する場合に指定。ルートの場合は '/'

        \HQ::setDebugMode(true);
        \HQ::setViewCacheMode(!\HQ::getDebugMode());

        // \HQ::setenv('INTERNAL_REST_API_ALLOWED_IPS', []);
        // \HQ::setenv('INTERNAL_REST_API_KEY', \SSS::INTERNAL_REST_API_KEY);

        \HQ::setenv('CCC::PHP_CLI', '/usr/bin/php');
        \HQ::setenv('CCC::NODE_CLI', '~/.nvm/versions/node/v20.18.0/bin/node');

        // CSS breakpoints from bootstrap's default
        \HQ::setenv('STYLES::breakpoints', [
        'sm' => 576, 'md' => 768, 'lg' => 992, 'xl' => 1200, '2xl' => 1400,
        ]);
    } 
  
    // Called from index.php
    public static function onFinish()
    {
        // // Clear Batch Table
        // if (!\HQ::cache()->has('rate_imit_on_prune-batches')) {
        //   \HQ::cache()->put('rate_imit_on_prune-batches', true, 60*60*24);
        //   \Utils\AsyncCLI::runArtisan('queue:prune-batches');
        // }
    } 
  
    // Called from laravel-ext/bootstrap/app.php ->withMiddleware
    // You cant use `debug()` yet.
    public static function onMiddleware($middleware)
    {
        // dump($middleware);

        // Trusted Hosts の設定
        // デフォルトは指定なし。
        // $middleware->trustHosts(at: [ ... ], subdomains: false); で許可するホスト名を指定。at: callback でもよい。
        // なお、config.env.url を使用しないので subdomains: false が必須。
        // $middleware->trustHosts(at: ['test.com'], subdomains: false);

        // Trusted Proxies の設定
        // デフォルトは指定なし。
        // $middleware->trustProxies(at: [ ... ]); でIPアドレスを指定。
        // $middleware->trustProxies(headers: ...); でProxynoのアドレスを取得するヘッダー名を指定。
        // $middleware->trustProxies(at: ['230.123.2.211']);
        // $middleware->trustProxies(headers: xxx);
    } 
  
    // Called from laravel-ext/bootstrap/app.php ->withExceptions
    public static function onExceptions($exceptions)
    {
        $exceptions->render(function (\Exception $e) {
            $title = 'ERROR';
            if (method_exists($e, 'getStatusCode')) {
                $title = $e->getStatusCode() . ' ERROR';
            }
            return response()->view('sample.message', [
                'title' => $title,
                'message' => $message = $e->getMessage() ?: 'Unknown Error',
            ]);
        });
    } 
  
    // Called from App\Providers\AppServiceProvider::boot()
    public static function onBoot()
    {
        if (\HQ::getDebugMode()) {

            \Log::debug(\HQ::getenv('CCC::APP_NAME') . ' boot!');

            if (defined('SSS::maintenanceModeData') && \SSS::maintenanceModeData['secret']) {
                \HQ::setMaintenanceMode([
                    'secret' => \SSS::maintenanceModeData['secret'],
                    'template' => view('sample.message', [
                        'title' => \SSS::maintenanceModeData['title'] ?: 'Page Under Maintenance',
                        'message' => \SSS::maintenanceModeData['message'] ?: 'Sorry for the inconvenience but we’re performing some maintenance at the moment.',
                    ])->render(),
                ]);
            } else {
                \HQ::setMaintenanceMode(false);
            }
        
        }
    } 
  
    // Called from routes/console.php
    // Define the application's command schedule and application's command schedule.
    // See
    //   https://laravel.com/docs/11.x/scheduling
    //   https://laravel.com/docs/11.x/artisan#closure-commands 
    public static function onConsole()
    {
    } 

    // Called from routes/web.php
    public static function onWeb($router)
    {
        // admin
        \Route::prefix('admin')->group(function() {
            include __DIR__."/web_routes/admin.php";

            // Admin root
            \Route::get('/', fn() => redirect('admin/check'));
        });

        // Markdown 使用例
        \Route::get('markdown/{name}', function ($name) {
            abort_unless(\Compilers::markdown()->exists($name), 404, "Markdown [{$name}] not found.");
            $contents = \Compilers::markdown($name, [], [
                'force_compile' => \HQ::getDebugMode() || request()->has('force_compile'),
                'markdown' => [
                'config' => [
                    'renderer' => [
                    'soft_break' => "<br/>\n",
                    ],
                ],
                ],
            ]);
            return response($contents, 200)->header('Content-Type', 'text/plain; charset=utf-8');
        })->where('name', '.*'); // Markdown

        //
        require(__DIR__.'/web_routes/on_error.php');

        // Root の例
        \Route::any('/{name?}', function($name = null) {

            if (($r = \HQ::webOrigin(request())) !== false) return $r;
        
            if (str_contains($name, '..')) abort(403); // for directory traversal !超重要！

            $public_root = \HQ::getenv('CCC::BASE_DIR') . "/.public-root";
            $file = "$public_root/$name"; 

            if (file_exists($file) && is_dir($file)) {
                $file .= "/index.html";
            }
            if (!file_exists($file)) abort(404); 

            if (pathinfo($file, PATHINFO_EXTENSION) !== 'html') {
                // 静的ファイルはそのまま返す
                return response()->file($file);
            }

            $html = \File::get($file);
            
            // cache etc...

            return self::renderHtmlSlot($html);
        })->where('name', '.*');
        
    } // onWeb


    /**
     * Render SSR slots in HTML.
     *
     * @param string        $html
     * @param callable|null $slot_func  Slot resolver.
     *
     * $slot_func signature:
     * @param DOMDocument $dom
     * @param DOMElement  $node
     * @param string      $keyName
     *
     * @return true                 Use default (Blade) resolver
     * @return string               Replace slot with content
     * @return array{content:string, mode?:'inner'|'replace'}
     * @return false|null           Skip (Handle DOM manually if you need)
     *
     * Eg.)
     *   function slot_func($dom, $node, $keyName)
     *   {
     *       $isScriptJson = $node->nodeName === 'script'
     *       && strtolower($node->getAttribute('type')) === 'application/json';
     *       return $isScriptJson ? ['mode' => 'inner', 'content' => <json>] : true; 
     *   }
     * 
     */
    private static function renderHtmlSlot(string $html, $slot_func = null): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);

        // HTML5 対策
        $dom->loadHTML(
            '<?xml encoding="utf-8" ?>' . $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        $xpath = new \DOMXPath($dom);

        // id が slot-ssr: で始まる要素を取得
        $nodes = iterator_to_array($xpath->query(
            '//*[@id and starts-with(@id, "slot-ssr:")]'
        ));

        foreach ($nodes as $node) {
            $id = $node->getAttribute('id');
            // slot-blade:header:1
            [, $rest] = explode('slot-ssr:', $id, 2);
            [$keyName, $unique] = array_pad(explode(':', $rest, 2), 2, null);

            // // キャッシュキー
            // $cacheKey = 'blade-slot:' . md5($blade . serialize($props));

            // $rendered = Cache::remember($cacheKey, 3600, function () use ($blade, $props, $fallback) {
            //     if (! view()->exists($blade)) {
            //         return $fallback;
            //     }

            //     return view($blade, $props + ['slot' => $fallback])->render();
            // });

            $result = true;
            if ($slot_func) {
                $result = $slot_func($dom, $node, $keyName);
            }

            if ($result === true) {
                // from Blade
                if (!view()->exists($keyName)) continue;

                // props
                $props = [];
                if ($node->hasAttribute('data-props')) {
                    try {
                        $props = json_decode($node->getAttribute('data-props'), true, 512, JSON_THROW_ON_ERROR);
                    } catch (\Throwable $e) {
                    }
                }

                // fallback（子HTML）
                $fallback = '';
                foreach ($node->childNodes as $child) {
                    $fallback .= $dom->saveHTML($child);
                }

                $result =  view($keyName, $props + ['fallback' => $fallback])->render();
            }

            $content = is_string($result) ? $result : ($result['content'] ?? false);
            if (is_string($content)) {
                // HTML → DOM ノード化
                $fragment = $dom->createDocumentFragment();

                $isHTML = @$fragment->appendXML(
                    mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8')
                );
                if (!$isHTML) {
                    // <input> <br> のような /> で終わらないなど、曖昧なものも受け入れるための処理
                    $tmp = new \DOMDocument();
                    $htmlToLoad = '<?xml encoding="utf-8" ?>' . $content;
                    @$tmp->loadHTML($htmlToLoad, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

                    foreach ($tmp->childNodes as $child) {
                        $imported = $dom->importNode($child, true);
                        $fragment->appendChild($imported);
                    }

                    // 失敗した場合はテキストとして挿入
                    if (!$fragment->hasChildNodes() && !empty($content)) {
                        $fragment = $dom->createTextNode($content);
                    }
                }

                $mode = $result['mode'] ?? 'replace';
                if ($mode == 'inner') {
                    while ($node->firstChild) {
                        $node->removeChild($node->firstChild);
                    }
                    $node->appendChild($fragment);
                } else {
                    // replace
                    // $node->parentNode->replaceChild($fragment, $node);
                    $parent = $node->parentNode;
                    $ref = $node->nextSibling;
                    $parent->removeChild($node);
                    $parent->insertBefore($fragment, $ref);
                }
            }  
        }

        return $dom->saveHTML();
    }

}
