<?php

// Admin login
\Route::get('login', function () {
  abort_unless(\HQ::getenv('superUserSecret'), 403);

  $info = 
    (\HQ::getDebugMode() ? "<p style=\"color:orange;\">DEBUG MODE - ON!</p>" : "") .
    (!\HQ::getViewCacheMode() ? "<p style=\"color:orange;\">VIEW CACHE - OFF!</p>" : "") .
    (\HQ::getDebugShowSource() ? "<p style=\"color:red;\">DEBUG SHOW SOURCE - ON!</p>" : "") .
    (\HQ::getDebugbarShowAlways() ? "<p style=\"color:red;\">DEBUGBAR SHOW ALWAYS - ON!</p>" : "");

  if (\HQ::getSuperUser()) {
    return view('sample.message-markdown', ['title' => \HQ::getenv('CCC::APP_NAME'), 'message' => "Already logged in.<hr>".$info]);
  }

  \HQ::rateLimitForTheBruteForceAttack('rate_limit_super_user_login', 3);

  if (request()->query('secret') === \HQ::getenv('superUserSecret')) {
    \HQ::updateSuperUser();
    return view('sample.message-markdown', ['title' => \HQ::getenv('CCC::APP_NAME'), 'message' => 'Hello!<hr>'.$info]);
  }

  abort(403, "wrong secret");
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
  $msg = "NODE CLI: " . ($process->isSuccessful() ? trim($process->getOutput()) : 'ERROR!');

  $msg .= "\nPHP CLI: ";
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
