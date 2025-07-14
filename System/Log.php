<?php

abstract class Log {
  private const _PERMISSIONS = 0777;
  private const _DATE_FORMAT = 'H:i:s';
  private const _FILE_FORMAT = 'Ymd';

  private static array $_cache = [];
  private static array $_fopens = [];

  public static function clean(): bool {
    foreach (self::$_fopens as $fopen) {
      if (is_resource($fopen)) {
        fclose($fopen);
      }
    }
    self::$_fopens = [];
    self::$_cache = [];
    return true;
  }
  public static function info(): bool {
    return self::write(self::_format(func_get_args()), 'Info');
  }
  public static function warning(): bool {
    return self::write(self::_format(func_get_args()), 'Warning');
  }
  public static function error(): bool {
    return self::write(self::_format(func_get_args()), 'Error');
  }
  public static function model(): bool {
    return self::write(self::_format(func_get_args()), 'Model');
  }
  public static function query(string $db, string $sql, array $vals, bool $status, float $during): bool {
    static $type;

    // 定義 Type
    if ($type === null) {
      if (defined('MAPLE_CMD')) {
        $type = 'cmd';
      } else {
        $tmp = implode('/', Request::paths());
        $type = (Request::method() == 'cli' ? 'cli' : 'web') . ($tmp ? '：' . $tmp : '');
      }
    }

    // 整理變數
    $_type = ($type ? '' : "\n") . $type;
    $_date = date(self::_DATE_FORMAT);
    $_status = $status ? 'OK' : 'GG';
    $_during = $during . 'ms';
    $_parse = $sql . ' ─ ' . implode(',', $vals);

    // 準備 SQL 模板：將 ? 替換為 %s，並轉義原有的 % 為 %%
    $sqlTemplate = str_replace('%', '%%', $sql);
    $sqlTemplate = preg_replace('/\?/', '%s', $sqlTemplate);

    // 處理參數值：對象轉為字符串
    $parameters = array_map(function ($val) {
      return Helper::dump(is_object($val) ? (string)$val : $val, '', 0);
    }, $vals);

    // 使用 sprintf 將參數填充到 SQL 中
    $_parse = sprintf($sqlTemplate, ...$parameters);

    return self::write($_type . ' ─ ' . $_date . ' ─ ' . $_status . ' ─ ' . $_during . ' ─ ' . $db . ' ─ ' . $_parse . "\n", 'Query');
  }

  private static function _dirNotExistCreate(string $path, int $mode = 0777, bool $recursive = false): bool {
    if (is_dir($path)) {
      return true;
    }

    return self::_umaskMkdir($path, $mode, $recursive);
  }
  private static function _umaskMkdir(string $path, int $mode = 0777, bool $recursive = false): bool {
    if (is_dir($path)) {
      return true;
    }

    $oldmask = umask(0);
    $result = mkdir($path, $mode, $recursive);
    umask($oldmask);
    return $result;
  }
  private static function _umaskChmod(string $path, int $mode = 0777): bool {
    if (!file_exists($path)) {
      return false;
    }

    $oldmask = umask(0);
    $result = chmod($path, $mode);
    umask($oldmask);
    return $result;
  }
  private static function _format(array $args): string {
    return date(self::_DATE_FORMAT)
      . "\n"
      . implode("\n", array_map(fn($arg) => Helper::dump($arg), $args))
      . "\n\n";
  }
  private static function write(string $text, string $prefix): bool {
    $path = PATH_FILE_LOG . $prefix . DIRECTORY_SEPARATOR;

    if (!isset(self::$_cache[$path])) {
      if (!file_exists($path)) {
        @self::_dirNotExistCreate($path, 0777, true);
      }
      self::$_cache[$path] = is_writable($path);
    }

    if (!self::$_cache[$path]) {
      return true;
    }

    $path .= date(self::_FILE_FORMAT) . '.log';
    $newfile = !file_exists($path);

    if (!isset(self::$_fopens[$path])) {
      self::$_fopens[$path] = @fopen($path, 'ab');
      if (!self::$_fopens[$path]) {
        return true;
      }
    }

    $length = strlen($text);
    $written = 0;
    while ($written < $length) {
      if (!isset(self::$_fopens[$path])) {
        break;
      }

      if (@flock(self::$_fopens[$path], LOCK_EX)) { // 獲取鎖
        $result = @fwrite(self::$_fopens[$path], $text, $length - $written);
        @flock(self::$_fopens[$path], LOCK_UN); // 釋放鎖
      } else {
        $result = false;
      }

      if ($result === false) {
        break;
      }
      $written += $result;
    }

    // 設定檔案權限
    if ($newfile) {
      @self::_umaskChmod($path, self::_PERMISSIONS);
    }

    return true;
  }
}

register_shutdown_function(fn() => Log::clean());
