<?php

$requiredVersion = '7.4.0';

if (version_compare(PHP_VERSION, $requiredVersion, '<')) {
  echo sprintf('PHP 版本需求不符合：目前版本 %s，需要 %s 或更高版本！', PHP_VERSION, $requiredVersion);
  exit(1);
}

define('MAPLE', '9.0.1');

// 自動載入類別
spl_autoload_register(static function ($newClassName): void {
  if (class_exists($newClassName)) {
    return;
  }

  $tokens = explode('\\', $newClassName);

  $path = PATH . implode(DIRECTORY_SEPARATOR, $tokens) . '.php';
  if (file_exists($path) && is_file($path) && is_readable($path)) {
    require_once $path;
  }

  $path = PATH_SYSTEM . implode(DIRECTORY_SEPARATOR, $tokens) . '.php';
  if (file_exists($path) && is_file($path) && is_readable($path)) {
    require_once $path;
  }
});


// 載入錯誤處理
require_once PATH_SYSTEM . 'Core/ErrorHandler.php';

// 載入字符集
require_once PATH_SYSTEM . 'Core/Charset.php';
