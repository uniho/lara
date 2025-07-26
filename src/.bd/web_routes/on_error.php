<?php

\Route::get('/error/{error}', function ($error) {

  $msg = match($error) {
    'outdated-ios' => 'Your browser might be running on an outdated version of iOS (or iPadOS). Please update your system immediately to avoid security risks.',
    default => false,
  };

  if (request()->getPreferredLanguage() == 'ja') {
    $msg = match($error) {
      'outdated-ios' => 'ご使用中の iOS, iPadOS のバージョンが古すぎるため、このサイトをご利用いただくことができません。',
      default => $msg ?: '不明なエラーが発生しました。',
    };
  }

  if (!$msg) {
    $msg = 'Unknown error.';
  }

  return view('sample.message', ['message' => $msg]);
});
