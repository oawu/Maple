<?php

/* --------------------------------------------------
 *  定義時區
 * -------------------------------------------------- */

date_default_timezone_set('Asia/Taipei');



/* --------------------------------------------------
 *  定義版號
 * -------------------------------------------------- */

define('MAPLE', '7.1.0');



/* --------------------------------------------------
 *  定義路徑
 * -------------------------------------------------- */

define('PATH',           dirname(__FILE__, 2) . DIRECTORY_SEPARATOR);
define('PATH_APP',       PATH . 'App' .         DIRECTORY_SEPARATOR);
define('PATH_CONFIG',    PATH . 'Config' .      DIRECTORY_SEPARATOR);
define('PATH_FILE',      PATH . 'File' .        DIRECTORY_SEPARATOR);
define('PATH_MIGRATION', PATH . 'Migration' .   DIRECTORY_SEPARATOR);
define('PATH_PUBLIC',    PATH . 'Public' .      DIRECTORY_SEPARATOR);
define('PATH_ROUTER',    PATH . 'Router' .      DIRECTORY_SEPARATOR);
define('PATH_SYSTEM',    PATH . 'System' .      DIRECTORY_SEPARATOR);

define('PATH_APP_CONTROLLER', PATH_APP . 'Controller' . DIRECTORY_SEPARATOR);
define('PATH_APP_FUNC',       PATH_APP . 'Func' . DIRECTORY_SEPARATOR);
define('PATH_APP_LIB',        PATH_APP . 'Lib' . DIRECTORY_SEPARATOR);
define('PATH_APP_MODEL',      PATH_APP . 'Model' . DIRECTORY_SEPARATOR);
define('PATH_APP_VIEW',       PATH_APP . 'View' . DIRECTORY_SEPARATOR);

define('PATH_SYSTEM_CORE',    PATH_SYSTEM . 'Core' . DIRECTORY_SEPARATOR);
define('PATH_SYSTEM_FUNC',    PATH_SYSTEM . 'Func' . DIRECTORY_SEPARATOR);
define('PATH_SYSTEM_LIB',     PATH_SYSTEM . 'Lib' . DIRECTORY_SEPARATOR);
define('PATH_SYSTEM_MODEL',   PATH_SYSTEM . 'Model' . DIRECTORY_SEPARATOR);

define('PATH_FILE_APIDOC',    PATH_FILE . 'ApiDoc' . DIRECTORY_SEPARATOR);
define('PATH_FILE_LOG',       PATH_FILE . 'Log' . DIRECTORY_SEPARATOR);
define('PATH_FILE_CACHE',     PATH_FILE . 'Cache' . DIRECTORY_SEPARATOR);
define('PATH_FILE_SESSION',   PATH_FILE . 'Session' . DIRECTORY_SEPARATOR);
define('PATH_FILE_TMP',       PATH_FILE . 'Tmp' . DIRECTORY_SEPARATOR);

define('PATH_SYSTEM_MODEL_UPLOADER', PATH_SYSTEM_MODEL . 'Uploader' . DIRECTORY_SEPARATOR);


/* --------------------------------------------------
 *  載入初始函式
 * -------------------------------------------------- */

$tmp = function($name) {
  return @include_once PATH_SYSTEM_CORE . $name;
};

$tmp('Common.php') || exit('載入 System/Common 失敗！');
$tmp('Load.php')   || exit('載入 System/Load 失敗！');
$tmp('Status.php') || exit('載入 System/Status 失敗！');
$tmp('View.php')   || exit('載入 System/View 失敗！');
$tmp('GG.php')     || exit('載入 System/GG 失敗！');

$tmp = null; unset($tmp);



/* --------------------------------------------------
 *  檢查環境
 * -------------------------------------------------- */

isPhpVersion('7.0') || exit('PHP 版本太舊，請大於等於 7.0 版本！');



/* --------------------------------------------------
 *  載入核心 1
 * -------------------------------------------------- */

Load::systemCore('Xterm')        ?: gg('載入 System/Xterm 失敗！');
Load::systemCore('Charset')      ?: gg('載入 System/Charset 失敗！');
Load::systemCore('Log')          ?: gg('載入 System/Log 失敗！');
Load::systemCore('ErrorHandler') ?: gg('載入 System/ErrorHandler 失敗！');
Load::systemCore('Benchmark')    ?: gg('載入 System/Benchmark 失敗！');
Load::systemCore('Model.php')    ?: gg('載入 System/Model 失敗！');
