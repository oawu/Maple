#!/usr/bin/env php
<?php

// 定義時區
date_default_timezone_set('Asia/Taipei');

// 定義常數
define('MAPLE_CMD', true);
define('DIR', realpath(getcwd()) . DIRECTORY_SEPARATOR);

// 定義臨時用 Error Handler
function failure($errs) {
  is_array($errs) || $errs = [$errs];

  print(QUIET ? json_encode([
    'status' => false,
    'message' => array_map(function($err) { return $err instanceof Error ? $err->getMessage() : $err; }, $errs)
  ]) : ("\n──────────────────────\n ※※※※※ 發生錯誤 ※※※※※\n──────────────────────\n"
    . implode("\n", array_map(function($err) { return ' ◉ ' . ($err instanceof Error ? $err->getMessage() : $err); }, $errs))
    . ($errs ? "\n\n\n" : "\n\n")));

  exit(1);
}


// 載入 Entry
$entry = @file_get_contents($path = DIR . 'System' . DIRECTORY_SEPARATOR . 'Entry.php');
$entry && preg_match_all('/define\s*\((["\'])(?P<kv>(?>[^"\'\\\]++|\\\.|(?!\1)["\'])*)\1?/', $entry, $entry) && $entry['kv'] && in_array('MAPLE', $entry['kv']) || failure('這不是 Maple 8 框架的專案吧！');

include_once $path;

// 取得參數
$file    = array_shift($argv);
$quiet   = strtolower(array_shift($argv));
define('QUIET', $quiet == 'quiet');
$feature = QUIET ? strtolower(array_shift($argv)) : $quiet;

// 執行
Load::systemCmd('Main');
\CMD\Main::start($feature, $argv);

// 結束
exit(0);
