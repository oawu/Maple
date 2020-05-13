<?php

class Log {
  const PERMISSIONS = 0777;
  const DATE_FORMAT = 'H:i:s';
  const FILE_FORMAT = 'Ymd';
  private static $cache = [];
  private static $fopens = [];

  private static function logFormat($args) {
    return date(Log::DATE_FORMAT) . "\n" . implode("\n", array_map('dump', $args)) . "\n\n";
  }

  private static function write($text, $prefix) {
    $path = PATH_FILE_LOG . $prefix . DIRECTORY_SEPARATOR;

    if (!isset($cache[$path])) {
      file_exists($path) || dirNotExistCreate($path, 0777, true);
      $cache[$path] = is_writable($path);
    }

    if (!$cache[$path])
      return true;

    $path .= date(Log::FILE_FORMAT) . '.log';
    $newfile = !file_exists($path);

    if (!isset(self::$fopens[$path]))
      if (!self::$fopens[$path] = @fopen($path, 'ab'))
        return true;

    for($written = 0, $length = charsetStrlen($text); $written < $length; $written += $result)
      if (isset(self::$fopens[$path]))
        if (($result = @fwrite(self::$fopens[$path], charsetSubstr($text, $written))) === false)
          break;

    $newfile && @chmod($path, Log::PERMISSIONS);
    return true;
  }

  public static function clean() {
    foreach (self::$fopens as $fopen)
      fclose($fopen);
    self::$fopens = null;
    self::$cache = null;
    return true;
  }
  
  public static function info() {
    return self::write(
      self::logFormat(
        func_get_args()), 'Info');
  }

  public static function warning() {
    return self::write(
      self::logFormat(
        func_get_args()), 'Warning');
  }
  
  public static function error() {
    return self::write(
      self::logFormat(
        func_get_args()), 'Error');
  }

  public static function model() {
    return self::write(
      self::logFormat(
        func_get_args()), 'Model');
  }

  public static function query($sql, $vals, $status, $during, $parse) {
    static $type;

    $new = !$type ? "\n" : '';
    $tmp = implode('/', Request::segments());
    
    $type !== null || $type = !defined('MAPLE_CMD')
      ? Request::method() == 'cli'
        ? 'cli' . ($tmp ? '：' . $tmp : '')
        : 'web' . ($tmp ? '：' . $tmp : '')
      : 'cmd'   . '';

    return self::write($new . $type . ' ─ ' . date(Log::DATE_FORMAT) . ' ─ ' . ($status ? 'OK' : 'GG') . ' ─ ' . $during . 'ms ─ ' . ($parse ? call_user_func_array('sprintf', array_merge([preg_replace_callback('/\?/', function($matches) { return '%s'; }, str_replace('%', '%%', $sql))], array_map(function($val) { return dump(is_object($val) ? (string)$val : $val, ''); }, $vals))) : ($sql . ' ─ ' . implode(',', $vals))) . "\n", 'Query');
  }

  public static function benchmark($key) {
    return is_array($benchmark = benchmark($key)) && isset($benchmark['time'], $benchmark['memory']) ? self::write(date(Log::DATE_FORMAT) . ' ─ ' . $key . ' ─ ' . round($benchmark['time'], 4) . 'S ─ ' . $benchmark['memory'] . 'MB' . "\n", 'Benchmark') : true;
  }
}
