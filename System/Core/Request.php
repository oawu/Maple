<?php

abstract class Request {
  private static $method = null;
  private static $router = null;
  private static $segments = null;
  private static $params = null;
  
  private static $ip = null;
  private static $headers = null;
  private static $input = null;
  private static $hasSanitizeGlobals = null;

  public static $controllerClass = null;
  public static $controllerMethod = null;
  public static $controllerMiddlewares = null;
  public static $controllerObject = null;

  public static function clean() {
    self::$method = null;
    self::$router = null;
    self::$segments = null;
    self::$params = null;
  
    self::$ip = null;
    self::$headers = null;
    self::$input = null;
    self::$hasSanitizeGlobals = null;
  
    self::$controllerClass = null;
    self::$controllerMethod = null;
    self::$controllerObject = null;
    return true;
  }

  public static function headers($index = null, $xssClean = true) {
    $tmp = function($headers, $index) {
      if ($index === null)
        return $headers;

      $headers = array_change_key_case($headers, CASE_LOWER);
      $index = strtolower($index);

      return $headers[$index] ?? null;
    };

    if (self::$headers !== null)
      return $tmp(self::fetchFromArray(self::$headers, null, $xssClean), $index);

    if (function_exists('apache_request_headers')) {
      self::$headers = apache_request_headers();
    } else {
      isset($_SERVER['CONTENT_TYPE']) && self::$headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];

      foreach ($_SERVER as $key => $val)
        if (sscanf($key, 'HTTP_%s', $header) === 1)
          self::$headers[str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower($header))))] = $_SERVER[$key];
    }

    return $tmp(self::fetchFromArray(self::$headers, null, $xssClean), $index);
  }

  public static function ip() {
    if (self::$ip !== null)
      return self::$ip;

    self::$ip = self::servers('REMOTE_ADDR');

    return !self::validIp(self::$ip)
      ? self::$ip = '0.0.0.0'
      : self::$ip;
  }

  public static function userAgent($xssClean = null) {
    return self::fetchFromArray($_SERVER, 'HTTP_USER_AGENT', $xssClean);
  }

  public static function cookies($index = null, $xssClean = true) {
    return self::fetchFromArray($_COOKIE, $index, $xssClean);
  }

  public static function setCookie($name, $value, $expire = 0, $domain = null, $path = null, $prefix = null, $secure = null, $httponly = null) {
    if (is_array($name))
      foreach (['value', 'expire', 'domain', 'path', 'prefix', 'secure', 'httponly', 'name'] as $item)
        if (isset($name[$item]))
          $$item = $name[$item];

    $prefix   !== null || $prefix   = config('Cookie', 'prefix');
    $domain   !== null || $domain   = config('Cookie', 'domain');
    $secure   !== null || $secure   = config('Cookie', 'secure');
    $path     !== null || $path     = config('Cookie', 'path');
    $httponly !== null || $httponly = config('Cookie', 'httponly');
    $expire = time() + $expire;

    setcookie($prefix . $name, $value, $expire, $path, $domain, $secure, $httponly);
  }

  private static function cleanInputKeys($str, $fatal = true) {
    if (!preg_match('/^[a-z0-9:_\/|-]+$/i', $str))
      return $fatal === true ? false : GG('有不合法的字元！', 503);
      
    return UTF8_ENABLED === true ? cleanStr($str) : $str;
  }

  private static function cleanInputData($str) {
    if (is_array ($str)) {
      $t = [];
      foreach (array_keys($str) as $key)
        $t[self::cleanInputKeys($key)] = self::cleanInputData($str[$key]);
      return $t;
    }

    UTF8_ENABLED !== true || $str = cleanStr($str);
    $str = Security::removeInvisibleCharacters($str, false);

    return preg_replace('/(?:\r\n|[\r\n])/', PHP_EOL, $str);
  }

  private static function sanitizeGlobals() {
    if (self::$hasSanitizeGlobals) return ;

    foreach ($_GET as $key => $val)
      $_GET[self::cleanInputKeys($key)] = self::cleanInputData($val);

    if (is_array($_POST))
      foreach ($_POST as $key => $val)
        $_POST[self::cleanInputKeys($key)] = self::cleanInputData($val);

    if (is_array($_COOKIE)) {
      unset($_COOKIE['$Version'], $_COOKIE['$Path'], $_COOKIE['$Domain']);

      foreach ($_COOKIE as $key => $val)
        if (($cookieKey = self::cleanInputKeys($key)) !== false)
          $_COOKIE[$cookieKey] = self::cleanInputData($val);
        else
          unset($_COOKIE[$key]);
    }

    $_SERVER['PHP_SELF'] = strip_tags($_SERVER['PHP_SELF']);

    self::$hasSanitizeGlobals = true;
  }

  private static function fetchFromArray(&$array, $index = null, $xssClean = null) {
    self::sanitizeGlobals();

    $index = $index === null ? array_keys($array) : $index;

    if (is_array($index)) {
      $output = [];
      foreach ($index as $key)
        $output[$key] = self::fetchFromArray($array, $key, $xssClean);
      return $output;
    }

    if (isset ($array[$index])) {
      $value = $array[$index];
    } else if (($count = preg_match_all('/(?:^[^\[]+)|\[[^]]*\]/', $index, $matches)) > 1) {
      $value = $array;

      for ($i = 0; $i < $count; $i++) {
        $key = trim($matches[0][$i], '[]');
        if ($key === '')
          break;

        if (isset ($value[$key]))
          $value = $value[$key];
        else
          return null;
      }
    } else {
      return null;
    }

    $xssClean !== null || $xssClean = config('Other', 'globalXssFiltering');
    return $xssClean ? Security::xssClean($value) : $value;
  }

  public static function servers($index, $xssClean = true) {
    return self::fetchFromArray($_SERVER, $index, $xssClean);
  }

  private static function validIp($ip, $which = '') {
    switch (strtolower($which)) {
      case 'ipv4':
        $which = FILTER_FLAG_IPV4;
        break;

      case 'ipv6':
        $which = FILTER_FLAG_IPV6;
        break;

      default:
        $which = null;
        break;
    }

    return (bool)filter_var($ip, FILTER_VALIDATE_IP, $which);
  }

  public static function params($key = null) {
    return $key !== null
      ? array_key_exists($key, self::$params) ? self::$params[$key] : null
      : self::$params;
  }

  public static function segments() {
    return self::$segments ?? self::$segments = array_map(function ($t) { return urldecode($t); }, self::method() != 'cli'
      ? ($tmp = parse_url('http://__' . $_SERVER['REQUEST_URI'])) && isset($tmp['path'])
        ? array_filter(explode('/', $tmp['path']), function($t) { return $t !== ''; })
        : []
      : arrayFlatten(array_map(function($argv) { return explode('/', $argv); }, array_slice($_SERVER['argv'], 1)))
    );
  }

  public static function method() {
    return self::$method ?? self::$method = strtolower(PHP_SAPI === 'cli' || defined('STDIN')
      ? 'cli'
      : ($_POST['_method'] ?? $_SERVER['REQUEST_METHOD'] ?? 'get'));
  }

  public static function notFound() {
    $args = func_get_args();
    $args && call_user_func_array('Log::warning', $args);
    return GG('迷路惹！', 404);
  }

  public static function execController() {
    Router::init();
    $allRouter = &Router::all();

    if (!isset($allRouter[self::method()]))
      return self::notFound('Router 內沒有此 Method，此次請求為：' . self::method());

    foreach ($allRouter[self::$method] as $segment => $obj) {
      if (preg_match('#^' . $segment . '$#', implode('/', self::segments()), $matches)) {

        self::$params = [];
        foreach (array_filter(array_keys($matches), 'is_string') as $key)
          self::$params[$key] = $matches[$key];

        self::$router =& $obj;

        break;
      }
    }

    if (!self::$router)
      return self::notFound('找不到此 Request 所屬 Router！');

    self::$controllerMiddlewares = [];

    foreach (self::$router->mids() as $mid) {
      strpos($mid, '@') !== false || $mid = $mid . '@' . 'index';
      list($class, $method) = explode('@', $mid);

      if (!Load::middleware($class))
        return self::notFound('載入 Middleware Class 失敗！', '路徑：' . PATH_APP_MIDDLEWARE . $class . '.php');
      
      $class = '\\Middleware\\' . $class;
      $mid = new $class();

      if (!method_exists($mid, $method))
        return self::notFound('Middleware 沒有「' . $method . '」method！', '路徑：' . PATH_APP_MIDDLEWARE . $class . '.php');

      try {
        array_push(self::$controllerMiddlewares, ['obj' => $mid, 'result' => $mid->$method()]);
      } catch (MapleException $e) {
        Response::$code = $e->getStatusCode();
        return call_user_func_array('ifError', $e->getMessages());
      }
    }

    $func = self::$router->func();
    if ($func !== null) {
      try {
        Log::benchmark('ExecController');
        $result = is_callable($func) ? $func() : $func;
        Log::benchmark('ExecController');
      } catch (MapleException $e) {
        Response::$code = $e->getStatusCode();
        return call_user_func_array('ifError', $e->getMessages());
      }
      return $result;
    }

    $path = self::$router->path();
    $class = self::$controllerClass = self::$router->class();
    $method = self::$controllerMethod = self::$router->method();

    if (!isset($path, $class, $method))
      return self::notFound('找不到 Router 基本參數！', '路徑：' . $path . '.php', 'Class：' . $class, 'Method：' . $method);

    if (!Load::controller($path))
      return self::notFound('載入 Controller Class 失敗！', '路徑：' . $path . '.php');

    if (!class_exists($class))
      return self::notFound('Controller Class 不存在！', '請檢查「' . $path . '.php」檔案的 Class 名稱是否正確！');

    $exec = function($class, $method) {
      try {
        $obj = new $class();
        method_exists($obj, $method) || GG('迷路惹！', 404);

        Log::benchmark('ExecController');
        $result = call_user_func_array([self::$controllerObject =& $obj, $method], Request::params());
        Log::benchmark('ExecController');
        return $result;
      } catch (MapleException $e) {
        Response::$code = $e->getStatusCode();
        return call_user_func_array('ifError', $e->getMessages());
      }
    };

    $exec = $exec($class, $method);

    return is_callable($exec)
      ? $exec()
      : $exec;
  }

  private static function parseInput() {
    if (isset(self::$input)) return self::$input;

    $body = self::inputStream();
    $boundary = substr($body, 0, strpos($body, "\r\n"));

    if (empty($boundary) && substr($header = Request::headers('Content-Type'), 0, strpos($header, ";")) == 'application/x-www-form-urlencoded') {
      parse_str($body, $data);
      return self::$input = ['forms' => $data, 'files' => []];
    }

    $type = Request::headers('Content-Type');
    if (substr($type, 0, strpos($type, ";")) != 'multipart/form-data') {
      return self::$input = ['forms' => [], 'files' => []];
    }

    $forms = [];
    $files = [];
    $parts = array_slice(explode($boundary, $body), 1);

    foreach ($parts as $part) {
      if ($part == "--\r\n" || $part == "--")
        break;

      $part = ltrim($part, "\r\n");
      list($rawHeaders, $body) = explode("\r\n\r\n", $part, 2);

      $headers = [];
      $rawHeaders = explode("\r\n", $rawHeaders);

      foreach ($rawHeaders as $header) {
        list($name, $value) = explode(':', $header);
        $headers[strtolower($name)] = ltrim($value, ' ');
      }

      if (!(isset($headers['content-disposition']) && ($content = $headers['content-disposition'])))
        break;

      $filename = null;
      $tmpname = null;

      preg_match('/^(.+); *name="([^"]+)"(; *filename="([^"]+)")?/', $content, $matches);
      list(, , $key) = $matches;

      isset($matches[4])
        ? array_push($files, urlencode($key) . '=' . urlencode(json_encode(['name' => $matches[4], 'type' => trim($value), 'tmp_name' => tempnam(ini_get('upload_tmp_dir'), pathinfo($matches[4], PATHINFO_FILENAME)), 'error' => 0, 'size' => strlen($body)])))
        : array_push($forms, urlencode($key) . '=' . urlencode(substr($body, 0, strlen($body) - 2)));
    }

    parse_str(implode('&', $files), $files);
    $files = self::cover1($files);

    parse_str(implode('&', $forms), $forms);
    return self::$input = ['forms' => $forms, 'files' => $files];
  }
  
  private static function cover1($files) {
    $new = [];
    foreach ($files as $key => $file)
      $new[$key] = is_array($file) ? self::cover1($file) : json_decode($file, true);
    return $new;
  }

  private static function cover2($array, $prefix = '') {
    $result = [];
    if(is_array($array))
      foreach($array as $key => $value)
        $result = $result + self::cover2($value, $prefix . '[' . $key . ']');
    else
      $result[$prefix] = $array;

    return $result;
  }

  public static function forms($index = null, $xssClean = true) {
    if (Request::method() == 'post')
      return self::fetchFromArray($_POST, $index, $xssClean);
    self::parseInput('form');
    return self::fetchFromArray(self::$input['forms'], $index, $xssClean);
  }

  public static function files($index = null) {
    if (Request::method() != 'post') {
      self::parseInput('form');
      return self::$input['files'];
    }

    $news = [];
    foreach ($_FILES as $key1 => $file)
      foreach ($file as $type => $val1)
        foreach (self::cover2($val1, $key1) as $key2 => $val2) {
          $news[$key2] ?? $news[$key2] = [];
          $news[$key2][$type] = $val2;
        }

    $files = [];
    foreach ($news as $key => $new)
      array_push($files, urlencode($key) . '=' . urlencode(json_encode($new)));

    parse_str(implode('&', $files), $files);
    return self::cover1($files);
  }

  public static function queries($index = null, $xssClean = true) {
    return self::fetchFromArray($_GET, $index, $xssClean);
  }

  public static function rawText() {
    if (!in_array(Request::headers('Content-Type'), ['text/plain', 'application/json'])) return null;
    return self::inputStream();
  }

  public static function rawJson() {
    if (!in_array(Request::headers('Content-Type'), ['application/json'])) return null;
    $body = self::inputStream();
    return isJson($body) ? $body : null;
  }

  private static function inputStream($index = null, $xssClean = null) {
    $body = '';
    $data = fopen('php://input', 'r');
    while ($chunk = fread($data, 1024)) $body .= $chunk;
    fclose($data);
    return $body;
  }
}

if (!function_exists('baseURL')) {
  function baseURL() {
    static $baseURL;

    if ($baseURL === null) {
      $baseURL = config('Other', 'baseURL');

      if ($baseURL === null && isset($_SERVER['HTTP_HOST']))
        $baseURL = (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off'
          ? 'https'
          : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/';

      $baseURL || QQ('尚未設定 baseURL！', '請先至 config/[環境/]Other.php 內設定 baseURL');
      $baseURL = rtrim($baseURL, '/') . '/';
    }

    return $baseURL . trim(preg_replace('/\/+/', '/', implode('/', arrayFlatten(func_get_args()))), '/');
  }
}
