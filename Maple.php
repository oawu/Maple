#!/usr/bin/env php
<?php

date_default_timezone_set('Asia/Taipei');

define('MAPLE_CMD', true);

define('PATH', realpath(getcwd()) . DIRECTORY_SEPARATOR);
define('PATH_SYSTEM', PATH . 'System' . DIRECTORY_SEPARATOR);

// 載入 Entry
$entry = @file_get_contents($path = PATH_SYSTEM . '_Entry.php');

if (!($entry && preg_match_all('/define\s*\((["\'])(?P<kv>(?>[^"\'\\\]++|\\\.|(?!\1)["\'])*)\1?/', $entry, $entry) && $entry['kv'] && in_array('MAPLE', $entry['kv']))) {
  $error = '這不是 Maple 9 框架的專案吧！';

  if (defined('IS_QUIET') && IS_QUIET) {
    echo json_encode([
      'status' => false,
      'message' => $error
    ]);
  } else {
    $message = "\n──────────────────────\n ※※※※※ 發生錯誤 ※※※※※\n──────────────────────\n";
    foreach ($errs as $err) {
      $message .= ' ◉ ' . $error . "\n";
    }
    $message .= "\n\n";
    echo $message;
  }

  exit(1);
}

require_once $path;

require_once PATH_SYSTEM . '_Path.php';


// 取得參數
$file = array_shift($argv);
$quiet = strtolower(array_shift($argv));
define('IS_QUIET', $quiet == 'quiet');
$feature = IS_QUIET ? strtolower(array_shift($argv)) : $quiet;

// 執行
$result = \Cmd\Main::create($feature, $argv);
echo $result->display(IS_QUIET);

// 結束
exit(0);
