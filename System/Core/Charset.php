<?php

ini_set('default_charset', 'UTF-8');

if (extension_loaded('mbstring')) {
  define('MB_ENABLED', true);
  @ini_set('mbstring.internal_encoding', 'UTF-8');
  mb_substitute_character('none');
} else {
  define('MB_ENABLED', false);
}

if (extension_loaded('iconv')) {
  define('ICONV_ENABLED', true);
  @ini_set('iconv.internal_encoding', 'UTF-8');
} else {
  define('ICONV_ENABLED', false);
}

ini_set('php.internal_encoding', 'UTF-8');

if (!MB_ENABLED) {
  if (!function_exists('mb_strlen')) {
    function mb_strlen($str, $encoding = 'UTF-8') {
      return ICONV_ENABLED !== true
        ? strlen($str)
        : iconv_strlen($str, $encoding);
    }
  }

  if (!function_exists('mb_strpos')) {
    function mb_strpos($haystack, $needle, $offset = 0, $encoding = 'UTF-8') {
      return ICONV_ENABLED !== true
        ? strpos($haystack, $needle, $offset)
        : iconv_strpos($haystack, $needle, $offset, $encoding);
    }
  }

  if (!function_exists('mb_substr')) {
    function mb_substr($str, $start, $length = null, $encoding = 'UTF-8') {
      if (ICONV_ENABLED !== true)
        return isset($length)
          ? substr($str, $start, $length)
          : substr($str, $start);

      $length || $length = iconv_strlen($str, $encoding);
      return iconv_substr($str, $start, $length, $encoding);
    }
  }
}

define('UTF8_ENABLED',
  defined('PREG_BAD_UTF8_ERROR')
    && (ICONV_ENABLED === true
    || MB_ENABLED === true));

define('FUNC_OVERLOAD',
  extension_loaded('mbstring')
    && ini_get('mbstring.func_overload'));

if (!function_exists('charsetStrlen')) {
  function charsetStrlen($str) {
    return FUNC_OVERLOAD
      ? mb_strlen($str, '8bit')
      : strlen($str);
  }
}

if (!function_exists('charsetSubstr')) {
  function charsetSubstr($str, $start, $length = null) {
    if (FUNC_OVERLOAD) {
      isset($length) || $length = ($start >= 0 ? charsetStrlen($str) - $start : -$start);
      return mb_substr($str, $start, $length, '8bit');
    }

    return isset($length)
      ? substr($str, $start, $length)
      : substr($str, $start);
  }
}

if (!function_exists('cleanStr')) {
  function cleanStr($str) {
    return (preg_match('/[^\x00-\x7F]/S', $str) === 0) === false
      ? !MB_ENABLED
        ? ICONV_ENABLED
          ? @iconv('UTF-8', 'UTF-8//IGNORE', $str)
          : $str
        : mb_convert_encoding($str, 'UTF-8', 'UTF-8')
      : $str;
  }
}
