<?php

final class Config {
  private static array $_files = [];
  private static array $_keys = [];

  public static function get(string ...$args) {
    if (!$args) {
      return null;
    }

    $filename = array_shift($args);
    $key  = $filename . '_' . implode('_', $args);

    if (array_key_exists($key, self::$_keys)) {
      return self::$_keys[$key];
    }

    if (!array_key_exists($filename, self::$_files)) {
      $path1 = PATH_CONFIG . ENVIRONMENT . DIRECTORY_SEPARATOR . $filename . '.php';
      $path2 = PATH_CONFIG . $filename . '.php';

      if (file_exists($path1)) {
        self::$_files[$filename] = include_once($path1);
      } else if (file_exists($path2)) {
        self::$_files[$filename] = include_once($path2);
      } else {
        self::$_files[$filename] = null;
      }
    }

    $file = self::$_files[$filename];

    if (!is_array($file)) {
      self::$_keys[$key] = null;
      return null;
    }

    foreach ($args as $arg) {
      $file = is_array($file) && array_key_exists($arg, $file) ? $file[$arg] : null;
    }

    self::$_keys[$key] = $file;
    return $file;
  }
}
