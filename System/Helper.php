<?php

final class Helper {
  public static function explodePath(string $path, string $separator = '/', array $includes = ['\\', '/']): array {
    foreach ($includes as $include) {
      if ($include !== $separator) {
        $path = str_replace($include, $separator, $path);
      }
    }

    $parts = explode($separator, $path);

    $paths = [];
    foreach ($parts as $part) {
      $part = trim($part);
      if ($part !== '' && $part !== '.') {
        $paths[] = $part;
      }
    }

    return $paths;
  }
  /**
   * 檢查陣列是否為「列表陣列」（索引為連續的 0, 1, 2, ...）
   * @param array $arr 檢查的陣列
   * @return bool 是列表陣列則回傳 true，否則回傳 false
   *
   * @example
   * arrayIsList(['a', 'b'])                     // true
   * arrayIsList(['a' => 1, 'b' => 2])           // false
   * arrayIsList([1 => 'a', 2 => 'b'])           // false
   * arrayIsList([1 => 'a', 3 => 'c', 2 => 'b']) // false
   * arrayIsList([])                             // true
   */
  public static function arrayIsList(array $arr): bool {
    if ($arr === []) {
      return true;
    }
    return array_keys($arr) === range(0, count($arr) - 1);
  }
  public static function dump($val, string $ln = "\n", string $spaceStr = ' ', int $level = 0): string {
    $base = 2;
    $level = ($ln === '') ? 0 : $level;
    $space = str_repeat($spaceStr, $level * $base);


    // 處理基本類型
    if ($val === null) {
      return $space . 'null';
    }
    if (is_bool($val)) {
      return $space . ($val ? 'true' : 'false');
    }
    if (is_string($val)) {
      return $space . '"' . $val . '"';
    }
    if (is_numeric($val)) {
      return $space . (string)$val;
    }

    // 處理陣列
    if (is_array($val)) {
      $tmp = [];
      if (self::arrayIsList($val)) {
        foreach ($val as $v) {
          $tmp[] = self::dump($v, $ln, $spaceStr, $level + 1);
        }
      } else {
        foreach ($val as $k => $v) {
          $key = self::dump($k, $ln, $spaceStr, $level + 1);
          $val = ltrim(self::dump($v, $ln, $spaceStr, $level + 1), $spaceStr);
          $tmp[] = $key . ': ' . $val;
        }
      }

      return $space . '[' . ($tmp ? $ln . implode(', ' . $ln, $tmp) . $ln . $space : '') . ']';
    }

    // 處理 Exception
    if ($val instanceof \Exception) {
      return $space . '"' . $val->getMessage() . '"';
    }

    // 處理具 `__toString()` 的物件
    if (is_object($val) && method_exists($val, '__toString')) {
      return $space . '"' . (string)$val . '"';
    }

    // 處理一般物件
    if (is_object($val) && !method_exists($val, '__toString')) {
      return $space . 'Object(' . get_class($val) . ')';
    }

    // 預設使用 var_dump
    ob_start();
    var_dump($val);
    $resutl = ob_get_contents();
    ob_get_clean();

    return $resutl;
  }
  public static function benchmark(?string $key = null): array {
    static $_benchmark = [];

    $format = fn(array $data): array => [
      'time' => microtime(true) - $data['time'],
      'memory' => round((memory_get_usage() - $data['memory']) / 1024 / 1024, 4) // MB
    ];

    if ($key === null) {
      return array_map($format, $_benchmark);
    }

    if (isset($_benchmark[$key])) {
      return $format($_benchmark[$key]);
    }

    $_benchmark[$key] = [
      'time' => microtime(true),
      'memory' => memory_get_usage()
    ];

    return $_benchmark[$key];
  }
}
