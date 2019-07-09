<?php

class Log {
  const PERMISSIONS = 0777;
  const DATE_FORMAT = 'H:i:s';

  private static $fopens = [];
  private static $type = null;

  public static function info($message) {
    return self::write(self::logFormat(func_get_args()), 'Info');
  }

  public static function warning($message) {
    return self::write(self::logFormat(func_get_args()), 'Warning');
  }
  
  public static function error($message) {
    return self::write(self::logFormat(func_get_args()), 'Error');
  }

  public static function benchmark($message) {
    return self::write(self::logFormat(func_get_args()), 'Benchmark');
  }

  public static function model($message) {
    return self::write(self::logFormat(func_get_args()), 'Model');
  }

  public static function uploader($message) {
    return self::write(self::logFormat(func_get_args()), 'Uploader');
  }

  public static function saveTool($message) {
    return self::write(self::logFormat(func_get_args()), 'SaveTool');
  }

  public static function thumbnail($message) {
    return self::write(self::logFormat(func_get_args()), 'Thumbnail');
  }

  public static function query($sql, $vals, $status, $time) {
    static $type;

    // $new = !$type ? "\n" . Xterm::black(str_repeat('─', 80), true) . "\n" : '';
    $new = !$type ? "\n" : '';

    $type !== null || $type = !defined('MAPLE_CMD')
      ? isCli()
        ? Xterm::blue('cli', true) . (($tmp = implode(Xterm::gray('/')->dim(), Url::segments())) ? Xterm::black('：', true) . $tmp : '')
        : Xterm::purple('web')     . (($tmp = implode(Xterm::gray('/')->dim(), Url::segments())) ? Xterm::black('：', true) . $tmp : '')
      : Xterm::yellow('cmd')       . '';
    
    return self::write($new . $type . ' ' . Xterm::black('│', true) . ' ' . date(Log::DATE_FORMAT) . ' ' . Xterm::black('➜', true) . ' ' . Xterm::create($time)->color($time < 999 ? $time < 99 ? $time < 9 ? Xterm::L_GRAY : Xterm::L_CYAN : Xterm::L_YELLOW : Xterm::L_RED) . '' . Xterm::create('ms')->color($time < 999 ? $time < 99 ? $time < 9 ? Xterm::GRAY : Xterm::CYAN : Xterm::YELLOW : Xterm::RED) . ' ' . Xterm::black('│', true) . ' ' . ($status ? Xterm::green('OK', true) : Xterm::red('GG')) . ' ' . Xterm::black('➜', true) . ' ' . call_user_func_array('sprintf', array_merge(
      [preg_replace_callback('/\?/', function($matches) { return Xterm::red('%s'); }, Xterm::gray($sql, true))], array_map(function($val) {
        return dump(is_object($val) ? (string)$val : $val, '');
      }, $vals)
    )) . "\n", 'Query');
  }

  private static function logFormat($args) {
    return \Xterm::black('─', true)->blod() . ' Time' . Xterm::create('：')->dim() . Xterm::gray(date(Log::DATE_FORMAT), true) . ' ' . \Xterm::black(str_repeat('─', 63), true)->blod() . "\n"
           . implode("\n" . \Xterm::black(str_repeat('─', 80), true)->dim() . "\n", array_map(function($arg) { return dump($arg); }, $args)) . "\n"
           . \Xterm::black(str_repeat('─', 80), true)->blod() . "\n"
           . "\n\n";
  }

  public static function closeAll() {
    foreach (self::$fopens as $fopen)
      fclose($fopen);
    self::$fopens = [];
    return true;
  }

  private static function write($text, $prefix) {
    $path = PATH_FILE_LOG . $prefix . DIRECTORY_SEPARATOR;
    file_exists($path) || dirNotExistCreate($path, 0777);

    if (!is_writable($path))
      return true;

    $path .= date('Y-m-d') . '.log';
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
}

Status::addFuncs('Log closeAll', function() {
  return Log::closeAll();
});