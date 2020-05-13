<?php

/* --------------------------------------------------
 *  判斷 PHP 版本
 * -------------------------------------------------- */

version_compare(PHP_VERSION, '7.1', '>=')
  || exit('PHP 版本太舊，請大於等於 7.1 版本！');



/* --------------------------------------------------
 *  定義時區
 * -------------------------------------------------- */

date_default_timezone_set('Asia/Taipei');



/* --------------------------------------------------
 *  定義版號
 * -------------------------------------------------- */

define('MAPLE', '8.0.0');



/* --------------------------------------------------
 *  定義路徑
 * -------------------------------------------------- */

define('PATH', dirname(__FILE__, 2) . DIRECTORY_SEPARATOR);

define('PATH_APP',            PATH .        'App' .         DIRECTORY_SEPARATOR);
define('PATH_CONFIG',         PATH .        'Config' .      DIRECTORY_SEPARATOR);
define('PATH_FILE',           PATH .        'File' .        DIRECTORY_SEPARATOR);
define('PATH_MIGRATION',      PATH .        'Migration' .   DIRECTORY_SEPARATOR);
define('PATH_PUBLIC',         PATH .        'Public' .      DIRECTORY_SEPARATOR);
define('PATH_ROUTER',         PATH .        'Router' .      DIRECTORY_SEPARATOR);
define('PATH_SYSTEM',         PATH .        'System' .      DIRECTORY_SEPARATOR);

define('PATH_APP_CONTROLLER', PATH_APP .    'Controller' .  DIRECTORY_SEPARATOR);
define('PATH_APP_MIDDLEWARE', PATH_APP .    'Middleware' .  DIRECTORY_SEPARATOR);
define('PATH_APP_MODEL',      PATH_APP .    'Model' .       DIRECTORY_SEPARATOR);
define('PATH_APP_VIEW',       PATH_APP .    'View' .        DIRECTORY_SEPARATOR);
define('PATH_APP_FUNC',       PATH_APP .    'Func' .        DIRECTORY_SEPARATOR);
define('PATH_APP_LIB',        PATH_APP .    'Lib' .         DIRECTORY_SEPARATOR);

define('PATH_SYSTEM_CMD',     PATH_SYSTEM . 'Cmd' .         DIRECTORY_SEPARATOR);
define('PATH_SYSTEM_CORE',    PATH_SYSTEM . 'Core' .        DIRECTORY_SEPARATOR);
define('PATH_SYSTEM_FUNC',    PATH_SYSTEM . 'Func' .        DIRECTORY_SEPARATOR);
define('PATH_SYSTEM_LIB',     PATH_SYSTEM . 'Lib' .         DIRECTORY_SEPARATOR);
define('PATH_SYSTEM_MODEL',   PATH_SYSTEM . 'Model' .       DIRECTORY_SEPARATOR);

define('PATH_FILE_LOG',       PATH_FILE .   'Log' .         DIRECTORY_SEPARATOR);
define('PATH_FILE_CACHE',     PATH_FILE .   'Cache' .       DIRECTORY_SEPARATOR);
define('PATH_FILE_SESSION',   PATH_FILE .   'Session' .     DIRECTORY_SEPARATOR);
define('PATH_FILE_TMP',       PATH_FILE .   'Tmp' .         DIRECTORY_SEPARATOR);

define('PATH_SYSTEM_MODEL_UPLOADER', PATH_SYSTEM_MODEL . 'Uploader' . DIRECTORY_SEPARATOR);



/* --------------------------------------------------
 *  載入核心 1
 * -------------------------------------------------- */

require_once PATH_SYSTEM_CORE . 'Load.php';

Load::systemCore('Common');
Load::systemCore('Request');
Load::systemCore('Response');
Load::systemCore('View');
Load::systemCore('Charset');
Load::systemCore('Log');
Load::systemCore('ErrorHandler');
