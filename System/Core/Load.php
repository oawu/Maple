<?php

class Load {
  private static $caches = [];

  public static function file($path) {
    $ext  = pathinfo($path, PATHINFO_EXTENSION) != 'php' ? '.php' : '';
    $path = $path . $ext;

    if (isset(self::$caches[$path]))
      return true;

    if (!(is_file($path) && is_readable($path)))
      return false;

    include_once $path;

    return self::$caches[$path] = true;
  }

  public static function clean() {
    self::$caches = null;
    return true;
  }

  public static function middleware($path) {
    return self::file(PATH_APP_MIDDLEWARE . $path);
  }

  public static function controller($path) {
    return self::file(PATH_APP_CONTROLLER . $path);
  }

  public static function lib($path) {
    return self::file(PATH_APP_LIB  . $path);
  }

  public static function func($path) {
    return self::file(PATH_APP_FUNC  . $path);
  }

  public static function system($path) {
    return self::file(PATH_SYSTEM . $path);
  }

  public static function systemCmd($path) {
    return self::file(PATH_SYSTEM_CMD  . $path);
  }

  public static function systemCore($path) {
    return self::file(PATH_SYSTEM_CORE . $path);
  }

  public static function systemModel($path) {
    return self::file(PATH_SYSTEM_MODEL  . $path);
  }

  public static function systemLib($path) {
    return self::file(PATH_SYSTEM_LIB  . $path);
  }

  public static function systemFunc($path) {
    return self::file(PATH_SYSTEM_FUNC  . $path);
  }

  public static function auto() {
    if (is_array(config('Autoload')))
      foreach (config('Autoload') as $method => $files)
        if (is_callable(['Load', $method]))
          foreach ($files as $file)
            Load::$method($file);
  }

  public static function composer() {
    return config('Other', 'loadComposer')
      ? self::file(PATH . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php')
      : true;
  }
}
