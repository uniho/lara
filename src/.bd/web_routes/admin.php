<?php

// Admin login
\Route::match(['get', 'post'], 'login', function () {

  abort_unless(\HQ::getenv('superUserSecret'), 403);

  $info = 
    (\HQ::getDebugMode() ? "<p style=\"color:orange;\">DEBUG MODE - ON!</p>" : "") .
    (!\HQ::getViewCacheMode() ? "<p style=\"color:orange;\">VIEW CACHE - OFF!</p>" : "") .
    (\HQ::getDebugShowSource() ? "<p style=\"color:red;\">DEBUG SHOW SOURCE - ON!</p>" : "") .
    (\HQ::getDebugbarShowAlways() ? "<p style=\"color:red;\">DEBUGBAR SHOW ALWAYS - ON!</p>" : "");

  if (request()->isMethod('post')) {

    $ips = request()->ips();
    $ipAddr = $ips[array_key_last($ips)];
    $key = 'super_user_login';

    if (!\Unsta\FloodControl::isAllowed('flood_control_'.$key, 100, 60*60, $ipAddr)) {
      // ERROR: 100回/60分、ログイン失敗した(IP毎)
      abort(403, "rate limit");
    }

    \HQ::rateLimitForTheBruteForceAttack('rate_limit_'.$key, 3);

    if (request()->post('secret') === \HQ::getenv('superUserSecret')) {
      // ログイン成功
      \Unsta\FloodControl::clear('flood_control_'.$key, $ipAddr);
      \Auth::logout();
      \HQ::updateSuperUser();
      return view('sample.message-markdown',
        ['title' => \HQ::getenv('CCC::APP_NAME'), 'message' => 'Hello!<hr>'.$info]);
    }

    // ログイン失敗
    \Unsta\FloodControl::register('flood_control_'.$key, 60*60, $ipAddr);
    
    abort(403, "wrong secret");
  }

  if (\HQ::getSuperUser()) {
    return view('sample.message-markdown', 
      ['title' => \HQ::getenv('CCC::APP_NAME'), 'message' => "Already logged in.<hr>".$info]);
  }

  return view('sample.login', []);
});

// Admin logout
\Route::get('logout', function () {
  \HQ::logoutSuperUser();
  return view('sample.message', ['title' => \HQ::getenv('CCC::APP_NAME'), 'message' => 'Thanks, bye!']);
});

// Admin phpinfo
\Route::get('phpinfo', function () {
  abort_unless(\HQ::isAdminUser(), 403);
  phpinfo();
});

// Admin check
\Route::get('check', function () {
  abort_unless(\HQ::isAdminUser(), 403);

  $node = \HQ::getenv('CCC::NODE_CLI');
  $process = \Symfony\Component\Process\Process::fromShellCommandline("$node --version");
  $process->run();
  $nodeIsOk = $process->isSuccessful();
  $msg = "NODE CLI: " . ($nodeIsOk ? trim($process->getOutput()) : 'ERROR!');

  if ($nodeIsOk) {
    $msg .= "\npackage.json: ";
    $cmd = 'cat ' . \HQ::getenv('CCC::CLI_PATH') . '/node/package.json';
    $process = \Symfony\Component\Process\Process::fromShellCommandline($cmd);
    $process->run();
    $msg .= $process->isSuccessful() ? trim($process->getOutput()) : 'ERROR!';
  }

  $msg .= "\n\nPHP CLI: ";
  $php = \HQ::getenv('CCC::PHP_CLI');
  $cmd = ' -v';
  $process = \Symfony\Component\Process\Process::fromShellCommandline("$php $cmd");
  $process->run();
  $phpIsOk = $process->isSuccessful();
  $msg .= $phpIsOk ? trim(explode("\n", $process->getOutput())[0]) : 'ERROR!';

  if ($phpIsOk) {
    $msg .= "\nLaravel: ";
    // $php = \HQ::getenv('CCC::PHP_CLI');
    $cmd = \HQ::getenv('CCC::CLI_PATH') . '/async/artisan.php --version';
    $process = \Symfony\Component\Process\Process::fromShellCommandline("$php $cmd");
    $process->run();
    $msg .= $process->isSuccessful() ? trim($process->getOutput()) : 'ERROR!';
  }

  return view('sample.message', [
    'title' => 'CHECK',
    'message' => $msg,
  ]);            
});
