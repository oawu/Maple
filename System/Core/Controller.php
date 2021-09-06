<?php

abstract class Controller {
  private static $middleware = [];

  public static function middleware() {

    if (!$args = func_get_args())
      return self::$middleware;

    $arg = array_shift($args);

    if (is_array($arg))
      return self::$middleware = $arg;

    return $args
      ? self::$middleware[$arg] = array_shift($args)
      : self::$middleware[$arg] ?? null;
  }
}

spl_autoload_register(function($className) {
  return preg_match('/Controller$/', $className)
    && Load::controller('_' . DIRECTORY_SEPARATOR . $className)
    && class_exists($className);
});
