<?php

class Url {
  private static $segments;
  private static $baseUrl;

  public static function init() {
    self::$baseUrl = config('Other', 'baseUrl');
    self::$segments = array_map(function ($t) {return urldecode($t); }, isCli() ? self::parseArgv() : self::parseRequestUri());
  }
  
  private static function parseArgv() {
    return arrayFlatten(array_map(function($argv) { return explode('/', $argv); }, array_slice($_SERVER['argv'], 1)));
  }

  private static function parseRequestUri() {
    $tmp = parse_url('http://__' . $_SERVER['REQUEST_URI']);
    return isset($tmp['path']) ? array_filter(explode('/', $tmp['path']), function($t) { return $t !== ''; }) : [];
  }
  
  public static function base() {
    if (!self::$baseUrl && isset($_SERVER['HTTP_HOST']))
      self::$baseUrl = (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http') . '://'. $_SERVER['HTTP_HOST'] . '/';

    self::$baseUrl = rtrim(self::$baseUrl, '/') . '/';
    self::$baseUrl || gg('尚未設定 baseUrl！');

    return self::$baseUrl . trim(preg_replace('/\/+/', '/', implode('/', arrayFlatten(func_get_args()))), '/');
  }
  
  public static function current() {
    return self::base(self::$segments);
  }
  
  public static function segments() {
    return self::$segments;
  }

  public static function refresh() {
    if (!$args = func_get_args())
      return false;

    $url = null;
    is_string($args[0])
      && preg_match('/^(http|https):\/{2}/', $args[0], $matches)
      && $url = $args[0];

    if (!$url)
      if (!$args = array_filter(explode('/', (trim(preg_replace('/\/+/', '/', implode('/', arrayFlatten($args))), '/'))), function ($t) { return $t !== ''; }))
        return false;

    $url || $url = self::base($args);

    Status::append('Refresh:0;url=' . $url);
    Status::$code = 307;
    Status::header();
    exit(0);
  }

  public static function refreshWithSuccessFlash($url, $msg = '', $params = []) {
    class_exists('Session') && Session::setFlashData('flash', ['type' => 'success', 'msg' => $msg, 'params' => $params]);
    return static::refresh($url);
  }

  public static function refreshWithFailureFlash($url, $msg = '', $params = []) {
    class_exists('Session') && Session::setFlashData('flash', ['type' => 'failure', 'msg' => is_array($msg) ? array_shift($msg) : $msg, 'params' => $params]);
    return Url::refresh($url);
  }

  public static function router($name, $params = []) {
    $params = func_get_args();
    $name   = array_shift($params);
    $params = array_merge(Router::aliasAppendParam($name), arrayFlatten($params));
    $router = Router::findByName($name);

    $router || gg('Router 尚未設定「' . $name . '」的名稱！');

    preg_match_all('/\((\?<[^\/]+>)?(\[0-9\]\+|\[\^\/\]\+)\)/', $router->segment(), $matches);
    $matches = array_shift($matches);
    count($matches) == count($params) || gg('參數有誤！', 'Url::router 的「' . $name . '」需要 ' . count($matches) . '個參數！');

    return self::base(preg_replace_callback('/\((\?<[^\/]+>)?(\[0-9\]\+|\[\^\/\]\+)\)/', function($matches) use (&$params) {
      $param = array_shift($params);
      $param instanceof \M\Model && isset($param->id) && $param = $param->id;
      return (string)$param;
    }, $router->segment()));
  }


  // public static function redirect($code = 302) {
  //   if (!$args = func_get_args ())
  //     return false;

  //   $code = array_shift ($args);
  //   $code = !is_numeric ($code) ? isset($_SERVER['SERVER_PROTOCOL'], $_SERVER['REQUEST_METHOD']) && $_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.1' ? $_SERVER['REQUEST_METHOD'] !== 'GET' ? 303 : 307 : 302 : $code;

  //   if (is_string($args[0]) && preg_match('/^(http|https):\/{2}/', $args[0], $matches))
  //     return header('Location: ' . $args[0], true, $code);

  //   if (!$args = array_filter(explode('/', (trim(preg_replace('/\/+/', '/', implode('/', arrayFlatten($args))), '/'))), function ($t) { return $t !== ''; }))
  //     return false;

  //   header('Location: ' . self::base($args), true, $code);
  //   exit;
  // }

  // public static function routerHyperlink($name) {
  //   Load::systemLib('Html.php');
  //   return Hyperlink::create(call_user_func_array('self::router', func_get_args()));
  // }
}

Url::init();