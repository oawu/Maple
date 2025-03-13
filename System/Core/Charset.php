<?php

ini_set('default_charset', 'UTF-8');

if (extension_loaded('mbstring')) {
  define('MB_ENABLED', true);

  mb_internal_encoding('UTF-8');
  // @ini_set('mbstring.internal_encoding', 'UTF-8');

  mb_substitute_character('none');
} else {
  define('MB_ENABLED', false);
}

if (extension_loaded('iconv')) {
  define('ICONV_ENABLED', true);

  // iconv_set_encoding('internal_encoding', 'UTF-8');
  // @ini_set('iconv.internal_encoding', 'UTF-8');
} else {
  define('ICONV_ENABLED', false);
}

ini_set('php.internal_encoding', 'UTF-8');
define('UTF8_ENABLED', defined('PREG_BAD_UTF8_ERROR') && (ICONV_ENABLED === true || MB_ENABLED === true));
