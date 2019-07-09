<?php

class Output {
  public static function text($str) {
    Status::append('Content-Type: text/html; charset=UTF-8');
    return self::display($str);
  }

  public static function json($json, $code = null) {
    Status::append('Content-Type: application/json; charset=UTF-8');
    return self::display(json_encode($json));
  }

  public static function display($text) {
    Status::header();
    echo $text;
  }

  public static function router($router) {
    if (!$router)
      return new GG('迷路惹！', 404);

    $exec = $router->exec();

    if ($exec === null)
      return self::text('');

    if (is_bool($exec))
      return self::text('');

    if (is_numeric($exec) || is_string($exec))
      return Status::$isApi
        ? self::json(['messages' => [$exec]])
        : self::text($exec);

    if (is_array($exec))
      return self::json($exec);

    if ($exec instanceOf View)
      return self::text($exec->output());

    if ($exec instanceOf \M\Model)
      return self::json($exec->toArray());
  }
}