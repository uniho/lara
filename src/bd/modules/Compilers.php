<?php

//
final class Compilers
{
  //
  public static function scss($name = false, $data = [], $options = [])
  {
    $core = new class extends _CompilerCore {

      //
      public function file($file, $data = [], $options = [])
      {
        $compiler = new class($this, $data, $options) extends _Compiler implements \Illuminate\View\Compilers\CompilerInterface
        {
          public function compile($path)
          {
            $this->ensureCompiledDirectoryExists(
              $compiledPath = $this->getCompiledPath($path)
            );

            $params = '';
            if (isset($this->options['minify']) && $this->options['minify']) {
              $params .= ' --minify';
            }

            $node_cli = \HQ::getenv('CCC::NODE_CLI');
            $lightningcss_cli =  \HQ::getenv('CCC::NODE_PATH') . '/lightningcss_cli.js';

            exec("$node_cli $lightningcss_cli $path $params --outfile=$compiledPath 2>&1", $error);
            if (end($error) != 'done!') {
              // エラー発生のため、キャッシュファイルを削除
              \File::delete($compiledPath);
              \File::delete($compiledPath.'.map');
              \Log::error('lightningcss', $error);
              return;
            }
          }
        };
  
        $engine = new \Illuminate\View\Engines\CompilerEngine($compiler, app()['files']);
        return $engine->get($file);
      }

      //
      public function inline($src, $data = [], $options = [])
      {
        $params = '';
        if (isset($options['minify']) && $options['minify']) {
          $params .= ' --minify';
        }

        $node_cli = \HQ::getenv('CCC::NODE_CLI');
        $lightningcss_cli =  \HQ::getenv('CCC::NODE_PATH') . '/lightningcss_cli.js';

        $process = \Symfony\Component\Process\Process::fromShellCommandline("$node_cli $lightningcss_cli $params");
        $process->setInput($src)->run();
        return $process->getOutput();
      }

      //
      public function getFullName($name)
      {
        $body = $name;
        $ext = '.css';
        $p = strrpos($name, '.');
        if ($p !== false) {
          $body = strtr(substr($name, 0, $p), '/', '.');
          $ext = substr($name, $p);
        }
        return \HQ::getenv('CCC::RSS_PATH').'/css/'.strtr($body, '.', '/').$ext;
      }
    };

    if (!$name) {
      return $core;
    }  

    return $core->file($core->getFullName($name), $data, $options);
  }

  //
  public static function markdown($name = false, $data = [], $options = [])
  {
    $core = new class extends _CompilerCore {

      //
      public function file($file, $data = [], $options = [])
      {
        $compiler = new class($this, $data, $options) extends _Compiler implements \Illuminate\View\Compilers\CompilerInterface
        {
          public function compile($path)
          {
            $contents = $this->core->inlineCommonMark($this->files->get($path));
        
            $this->ensureCompiledDirectoryExists(
              $compiledPath = $this->getCompiledPath($path)
            );

            $this->files->put($compiledPath, $contents);
          }
        };

        $engine = new \Illuminate\View\Engines\CompilerEngine($compiler, app()['files']);
        return $this->inlineMustache($engine->get($file), $data, $options);
      }

      //
      public function inline($src, $data = [], $options = [])
      {
        $src = $this->inlineCommonMark($src);
        return $this->inlineMustache($src, $data, $options);
      }

      //
      public function inlineCommonMark($src)
      {
        $config = [];
        // $env = new \League\CommonMark\Environment\Environment($config);
        // $env->addExtension(new \League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension());
        // $env->addExtension(new \League\CommonMark\Extension\GithubFlavoredMarkdownExtension());
        // $env->addExtension(new \League\CommonMark\Extension\FrontMatter\FrontMatterExtension());
        // $converter = new \League\CommonMark\MarkdownConverter($env);
        $converter = new \League\CommonMark\GithubFlavoredMarkdownConverter($config);
        $env = $converter->getEnvironment();
        $env->addExtension(new \League\CommonMark\Extension\FrontMatter\FrontMatterExtension());
    
        $env->addRenderer(
          \League\CommonMark\Extension\CommonMark\Node\Block\FencedCode::class,
          new class implements \League\CommonMark\Renderer\NodeRendererInterface {
            public function render($node, $childRenderer) {
              $render = new \League\CommonMark\Extension\CommonMark\Renderer\Block\FencedCodeRenderer();
              $htmlElement = $render->render($node, $childRenderer);
              $contents = \str_replace('{', '&#123;', $htmlElement->getContents(false));
              $htmlElement->setContents($contents);
              return $htmlElement;
            }
          }
        );
    
        $env->addRenderer(
          \League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode::class,
          new class implements \League\CommonMark\Renderer\NodeRendererInterface {
            public function render($node, $childRenderer) {
              $render = new \League\CommonMark\Extension\CommonMark\Renderer\Block\IndentedCodeRenderer();
              $htmlElement = $render->render($node, $childRenderer);
              $contents = \str_replace('{', '&#123;', $htmlElement->getContents(false));
              $htmlElement->setContents($contents);
              return $htmlElement;
            }
          }
        );
    
        $env->addRenderer(
          \League\CommonMark\Extension\CommonMark\Node\Inline\Code::class,
          new class implements \League\CommonMark\Renderer\NodeRendererInterface {
            public function render($node, $childRenderer) {
              $render = new \League\CommonMark\Extension\CommonMark\Renderer\Inline\CodeRenderer();
              $htmlElement = $render->render($node, $childRenderer);
              $contents = \str_replace('{', '&#123;', $htmlElement->getContents(false));
              $htmlElement->setContents($contents);
              return $htmlElement;
            }
          }
        );
    
        $result = $converter->convert($src);
        $frontMatter = [];
        if ($result instanceof \League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter) {
          $frontMatter = $result->getFrontMatter();
        }
        
        return "---\n" . json_encode($frontMatter) . "\n---\n" . (string)$result;
      }

      //
      public function inlineMustache($src, $data = [], $options = [])
      {
        $frontmatter = '';
        $body = $src;
        if (preg_match('/\\A(?:---(.*?)?\\R---)(\\R.*)?\\Z/s', $src, $match)) {
          $frontmatter = $match[1];
          $body = '';
          if (count($match) > 2) {
            $body = $match[2];
          }
        } 

        $data = array_merge(json_decode($frontmatter, true), $data);

        $node_cli = \HQ::getenv('CCC::NODE_CLI');
        $handlebars_cli =  \HQ::getenv('CCC::NODE_PATH') . '/handlebars_cli.js';

        $process = \Symfony\Component\Process\Process::fromShellCommandline("$node_cli $handlebars_cli");
        $process->setInput("---\n".json_encode($data)."\n---\n".$body)->run();
        return $process->getOutput();
      }

      //
      public function getFullName($name)
      {
        $body = $name;
        $ext = '.md';
        $p = strrpos($name, '.');
        if ($p !== false) {
          $body = strtr(substr($name, 0, $p), '/', '.');
          $ext = substr($name, $p);
        }
        return \HQ::getenv('CCC::RSS_PATH').'/markdowns/'.strtr($body, '.', '/').$ext;
      }
    };

    if (!$name) {
      return $core;
    }  

    return $core->file($core->getFullName($name), $data, $options);
  }

  //
  public static function jsx($name = false, $data = [], $options = [])
  {
    $core = new class extends _CompilerCore {
      
      //
      public function file($file, $data = [], $options = [])
      {
        $compiler = new class($this, $data, $options) extends _Compiler implements \Illuminate\View\Compilers\CompilerInterface
        {
          public function compile($path)
          {
            $this->ensureCompiledDirectoryExists(
              $compiledPath = $this->getCompiledPath($path)
            );

            $params = '--sourcemap';
            if (isset($this->options['minify']) && $this->options['minify']) {
              $params .= ' --minify';
            }
            if (isset($this->options['tsconfig'])) {
              $params .= " --tsconfig-raw='" . json_encode($this->options['tsconfig']) ."'";
            }

            $node_cli = \HQ::getenv('CCC::NODE_CLI');
            $esbuild_cli = \HQ::getenv('CCC::NODE_PATH') . '/node_modules/.bin/esbuild';
            $minifyTemplateLiteral_cli =  \HQ::getenv('CCC::NODE_PATH') . '/node_modules/.bin/minify-template-literal';

            exec("$esbuild_cli $path $params --outfile=$compiledPath 2>&1", $error);
            if (strpos(end($error), 'error') !== false) {
              // エラー発生のため、元のファイルを書き込む
              $contents = \File::get($path);
              \File::put($compiledPath, $contents);
              \Log::error('jsx:esbuild', $error);
              return;
            }

            $error = [];
            exec("$node_cli $minifyTemplateLiteral_cli $compiledPath --remap --outfile=$compiledPath 2>&1", $error);
            if (end($error) != 'done!') {
              \Log::error('jsx:minify-template-literal', $error);
              return;
            }
          }
          
        };

        $engine = new \Illuminate\View\Engines\CompilerEngine($compiler, app()['files']);
        return $engine->get($file);
      }

      //
      public function getFullName($name)
      {
        $body = $name;
        $ext = '.js';
        $p = strrpos($name, '.');
        if ($p !== false) {
          $body = strtr(substr($name, 0, $p), '/', '.');
          $ext = substr($name, $p);
        }
        return \HQ::getenv('CCC::RSS_PATH').'/js/'.strtr($body, '.', '/').$ext;
      }
    };

    if (!$name) {
      return $core;
    }  

    return $core->file($core->getFullName($name), $data, $options);
  }
}

//
abstract class _CompilerCore
{
  abstract public function file($file, $data = [], $options = []);

  public function exists($name)
  {
    return is_file($this->getFullName($name));
  }

  abstract public function getFullName($name);
}

//
class _Compiler extends \Illuminate\View\Compilers\Compiler
{
  public function __construct(protected $core, protected $data, protected $options)
  {
    parent::__construct(app()['files'], app()['config']['view.compiled'], shouldCache: !isset($options['force_compile']) || !$options['force_compile']);
  }
}
