<?php

//
final class Compilers
{
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
            $contents = $this->core->inlineCommonMark($this->files->get($path), $this->options['markdown'] ?? []);
        
            $this->ensureCompiledDirectoryExists(
              $compiledPath = $this->getCompiledPath($path)
            );

            $this->files->put($compiledPath, $contents);
          }
        };

        $engine = new \Illuminate\View\Engines\CompilerEngine($compiler, app()['files']);
        return $this->inlineMustache($engine->get($file), $data, $options['mustache'] ?? []);
      }

      //
      public function inline($src, $data = [], $options = [])
      {
        $src = $this->inlineCommonMark($src, $options['markdown'] ?? []);
        return $this->inlineMustache($src, $data, $options['mustache'] ?? []);
      }

      //
      public function inlineCommonMark($src, $options = [])
      {
        $config = $options['config'] ?? [];
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
        $handlebars_cli =  \HQ::getenv('CCC::CLI_PATH') . '/node/handlebars_cli.js';

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
