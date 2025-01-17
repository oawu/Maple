<?php

abstract class Request {
  public static $router = null;
  private static $method = null;
  private static $segments = null;
  private static $params = null;

  private static $ip = null;
  private static $headers = null;
  private static $input = null;
  private static $hasSanitizeGlobals = null;

  public static $obj = null;

  public static function clean() {
    self::$method = null;
    self::$router = null;
    self::$segments = null;
    self::$params = null;

    self::$ip = null;
    self::$headers = null;
    self::$input = null;
    self::$hasSanitizeGlobals = null;

    return true;
  }

  public static function headers($index = null, $xssClean = true) {
    $tmp = function($headers, $index) {
      if ($index === null) {
        return $headers;
      }

      $headers = array_change_key_case($headers, CASE_LOWER);
      $index = strtolower($index);

      return $headers[$index] ?? null;
    };

    if (self::$headers !== null) {
      return $tmp(self::fetchFromArray(self::$headers, null, $xssClean), $index);
    }

    if (function_exists('apache_request_headers')) {
      self::$headers = [];
      foreach (apache_request_headers() as $key => $value) {
        self::$headers[ucwords($key, '-')] = $value;
      }
    } else {
      isset($_SERVER['CONTENT_TYPE']) && self::$headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];

      foreach ($_SERVER as $key => $val) {
        if (sscanf($key, 'HTTP_%s', $header) === 1) {
          self::$headers[str_replace(' ', '-', ucwords(strtolower($header), ' '))] = $_SERVER[$key];
        }
      }
    }

    return $tmp(self::fetchFromArray(self::$headers, null, $xssClean), $index);
  }

  public static function ip() {
    if (self::$ip !== null) {
      return self::$ip;
    }

    self::$ip = self::servers('REMOTE_ADDR');

    if (!self::validIp(self::$ip)) {
      self::$ip = '0.0.0.0';
    }

    return self::$ip;
  }

  public static function userAgent($xssClean = null) {
    return self::fetchFromArray($_SERVER, 'HTTP_USER_AGENT', $xssClean);
  }

  public static function cookies($index = null, $xssClean = true) {
    return self::fetchFromArray($_COOKIE, $index, $xssClean);
  }

  public static function setCookie($name, $value, $expire = 0, $domain = null, $path = null, $prefix = null, $secure = null, $httponly = null) {
    if (is_array($name)) {
      foreach (['value', 'expire', 'domain', 'path', 'prefix', 'secure', 'httponly', 'name'] as $item) {
        if (isset($name[$item])) {
          $$item = $name[$item];
        }
      }
    }
    if ($prefix === null) { $prefix = config('Cookie', 'prefix'); }
    if ($domain === null) { $domain = config('Cookie', 'domain'); }
    if ($secure === null) { $secure = config('Cookie', 'secure'); }
    if ($path === null) { $path = config('Cookie', 'path'); }
    if ($httponly === null) { $httponly = config('Cookie', 'httponly'); }

    $expire = time() + $expire;

    setcookie($prefix . $name, $value, $expire, $path, $domain, $secure, $httponly);
  }

  private static function cleanInputKeys($str, $fatal = true) {
    if (!preg_match('/^[a-z0-9:_\/|-]+$/i', $str)) {
      if ($fatal === true) {
        return GG('有不合法的字元！', 503);
      }
      return false;
    }

    if (UTF8_ENABLED === true) {
      return cleanStr($str);
    }

    return $str;
  }

  private static function cleanInputData($str) {
    if (is_array ($str)) {
      $t = [];
      foreach (array_keys($str) as $key) {
        $t[self::cleanInputKeys($key)] = self::cleanInputData($str[$key]);
      }
      return $t;
    }

    if (UTF8_ENABLED === true) {
      $str = cleanStr($str);
    }

    $str = Security::removeInvisibleCharacters($str, false);

    return preg_replace('/(?:\r\n|[\r\n])/', PHP_EOL, $str);
  }

  private static function sanitizeGlobals() {
    if (self::$hasSanitizeGlobals) {
      return ;
    }

    foreach ($_GET as $key => $val) {
      $_GET[self::cleanInputKeys($key)] = self::cleanInputData($val);
    }

    if (is_array($_POST)) {
      foreach ($_POST as $key => $val) {
        $_POST[self::cleanInputKeys($key)] = self::cleanInputData($val);
      }
    }

    if (is_array($_COOKIE)) {
      unset($_COOKIE['$Version'], $_COOKIE['$Path'], $_COOKIE['$Domain']);

      foreach ($_COOKIE as $key => $val) {
        if (($cookieKey = self::cleanInputKeys($key)) !== false) {
          $_COOKIE[$cookieKey] = self::cleanInputData($val);
        } else {
          unset($_COOKIE[$key]);
        }
      }
    }

    $_SERVER['PHP_SELF'] = strip_tags($_SERVER['PHP_SELF']);

    self::$hasSanitizeGlobals = true;
  }

  private static function fetchFromArray(&$array, $index = null, $xssClean = null) {
    self::sanitizeGlobals();

    $index = $index === null ? array_keys($array) : $index;

    if (is_array($index)) {
      $output = [];
      foreach ($index as $key) {
        $output[$key] = self::fetchFromArray($array, $key, $xssClean);
      }
      return $output;
    }

    if (isset($array[$index])) {
      $value = $array[$index];
    } else if (($count = preg_match_all('/(?:^[^\[]+)|\[[^]]*\]/', $index, $matches)) > 1) {
      $value = $array;

      for ($i = 0; $i < $count; $i++) {
        $key = trim($matches[0][$i], '[]');

        if ($key === '') {
          break;
        }

        if (isset($value[$key])) {
          $value = $value[$key];
        } else {
          return null;
        }
      }
    } else {
      return null;
    }

    if ($xssClean === null) {
      $xssClean = config('Other', 'globalXssFiltering');
    }

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
    if (self::$segments === null) {
      $segments = [];

      if (self::method() == 'cli') {
        $segments = arrayFlatten(array_map(function($argv) { return explode('/', $argv); }, array_slice($_SERVER['argv'], 1)));
      } else {
        $tmp = parse_url('http://__' . $_SERVER['REQUEST_URI']);
        if ($tmp && isset($tmp['path'])) {
          $segments = array_filter(explode('/', $tmp['path']), function($t) { return $t !== ''; });
        }
      }

      self::$segments = array_values(array_map(function ($t) {
        return urldecode($t);
      }, $segments));
    }

    return self::$segments;
  }

  public static function method() {
    if (self::$method === null) {
      $method = 'get';

      if (PHP_SAPI === 'cli' || defined('STDIN')) {
        $method = 'cli';
      }

      if (isset($_POST['_method'])) {
        $method = strtolower($_POST['_method']);
      }

      if (isset($_SERVER['REQUEST_METHOD'])) {
        $method = strtolower($_SERVER['REQUEST_METHOD']);
      }

      self::$method = $method;
    }

    return self::$method;
  }

  public static function notFound() {
    // $args = func_get_args();
    // $args && call_user_func_array('Log::warning', $args);
    return GG('迷路惹！', 404);
  }

  private static function execMiddleware() {
    $return = null;

    foreach (self::$router->mids() as $mid) {
      if (strpos($mid, '@') === false) {
        $mid = $mid . '@' . 'index';
      }

      list($class, $method) = explode('@', $mid);

      if (!Load::middleware($class)) {
        return self::notFound('載入 Middleware Class 失敗！', '路徑：' . PATH_APP_MIDDLEWARE . $class . '.php');
      }

      $className = '\\Middleware\\' . $class;
      $mid = new $className();

      if (!method_exists($mid, $method)) {
        return self::notFound('Middleware 沒有「' . $method . '」method！', '路徑：' . PATH_APP_MIDDLEWARE . $className . '.php');
      }

      try {
        $mid->$method($return);
      } catch (MapleException $e) {
        return $e;
      }
    }

    return $return;
  }

  public static function execController() {
    Router::init();
    $allRouter = &Router::all();

    if (!isset($allRouter[self::method()])) {
      return self::notFound('Router 內沒有此 Method，此次請求為：' . self::method());
    }

    foreach ($allRouter[self::$method] as $segment => $obj) {
      if (preg_match('#^' . $segment . '$#', implode('/', self::segments()), $matches)) {

        self::$params = [];
        foreach (array_filter(array_keys($matches), 'is_string') as $key) {
          self::$params[$key] = $matches[$key];
        }

        self::$router =& $obj;

        break;
      }
    }

    if (!self::$router)
      return self::notFound('找不到此 Request 所屬 Router！');

    $middleware = self::execMiddleware();
    if ($middleware instanceof MapleException) {
      Response::$code = $middleware->getStatusCode();
      return call_user_func_array('ifError', $middleware->getMessages());
    }

    $func = self::$router->func();
    if ($func !== null) {
      $result = null;
      try {
        Log::benchmark('ExecController');
        $result = is_callable($func) ? $func($middleware) : $func;
        Log::benchmark('ExecController');
      } catch (MapleException $e) {
        Response::$code = $e->getStatusCode();
        return call_user_func_array('ifError', $e->getMessages());
      }
      return $result;
    }

    $path = self::$router->path();
    $class = self::$router->class();
    $method = self::$router->method();

    if (!isset($path, $class, $method)) {
      return self::notFound('找不到 Router 基本參數！', '路徑：' . $path . '.php', 'Class：' . $class, 'Method：' . $method);
    }

    if (!Load::controller($path)) {
      return self::notFound('載入 Controller Class 失敗！', '路徑：' . $path . '.php');
    }

    if (!class_exists($class)) {
      return self::notFound('Controller Class 不存在！', '請檢查「' . $path . '.php」檔案的 Class 名稱是否正確！');
    }

    $result = null;
    try {
      $obj = new $class($middleware);

      if (!method_exists($obj, $method)) {
        GG('迷路惹！', 404);
      }

      Log::benchmark('ExecController');
      $result = call_user_func_array([self::$obj =& $obj, $method], Request::params());
      Log::benchmark('ExecController');
    } catch (MapleException $e) {
      Response::$code = $e->getStatusCode();
      return call_user_func_array('ifError', $e->getMessages());
    }

    return $result;
  }

  private static function parseInput() {
    if (isset(self::$input)) {
      return self::$input;
    }

    $contentType = Request::headers('Content-Type');

    $result = preg_match('/^\s*(?P<type>application\/x-www-form-urlencoded)(\s*;\s*)?(?P<other>.*)?\s*$/i', $contentType, $matches);
    if ($result && $matches && $matches['type']) {
      $payload = self::inputStream();
      parse_str($payload, $data);
      return self::$input = ['forms' => $data, 'files' => []];
    }

    $result = preg_match('/^\s*(?P<type>multipart\/form-data)(\s*;\s*)?boundary=(?P<boundary>.*)?\s*$/i', $contentType, $matches);
    if (!($result && $matches && $matches['type'] && $matches['boundary'])) {
      return self::$input = ['forms' => [], 'files' => []];
    }

    $payload = self::inputStream();

    $boundary = '--' . $matches['boundary'];

    $forms = [];
    $files = [];

    $parts = explode($boundary, $payload);
    foreach ($parts as $part) {
      $part = trim($part);

      if (empty($part)) {
        continue;
      }

      if (preg_match('/\s*Content-Disposition\s*:\s*form-data;\s*name\s*=\s*"(?P<key>[^"]+)";\s*filename\s*=\s*"(?P<name>[^"]+)"\s*/i', $part, $matches) && $matches && $matches['key'] && $matches['name']) {
        $key = $matches['key'];
        $name = $matches['name'];
        $type = preg_match('/\s*Content-Type\s*:\s*(?P<type>[^\r\n]+)/', $part, $matches) && $matches && $matches['type'] ? $matches['type'] : 'application/octet-stream';

        $index = strpos($part, "\r\n\r\n");
        if ($index === false) {
          continue;
        }

        $data = trim(substr($part, $index + 4));
        $tmpName = tempnam(ini_get('upload_tmp_dir'), 'Maple_');
        $bytes = @file_put_contents($tmpName, $data);

        if ($bytes === false) {
          continue;
        }

        array_push($files, urlencode($key) . '=' . urlencode(json_encode([
          'name' => $name,
          'type' => $type,
          'tmp_name' => $tmpName,
          'error' => 0,
          'size' => strlen($data)
        ])));
        continue;
      }

      if (preg_match('/\s*Content-Disposition\s*:\s*form-data;\s*name\s*=\s*"(?P<key>[^"]+)"\s*/i', $part, $matches) && $matches && $matches['key']) {
        $index = strpos($part, "\r\n\r\n");
        array_push($forms, urlencode($matches['key']) . '=' . urlencode($index === false ? '' : trim(substr($part, $index + 4))));
        continue;
      }
    }
    parse_str(implode('&', $files), $files);
    $files = self::cover1($files);

    parse_str(implode('&', $forms), $forms);
    return self::$input = ['forms' => $forms, 'files' => $files];
  }

  private static function cover1($files) {
    $new = [];
    foreach ($files as $key => $file) {
      $new[$key] = is_array($file) ? self::cover1($file) : json_decode($file, true);
    }
    return $new;
  }

  private static function cover2($array, $prefix = '') {
    $result = [];
    if(is_array($array)) {
      foreach($array as $key => $value) {
        $result = $result + self::cover2($value, $prefix . '[' . $key . ']');
      }
    } else {
      $result[$prefix] = $array;
    }

    return $result;
  }

  public static function forms($index = null, $xssClean = true) {
    if (Request::method() == 'post') {
      return self::fetchFromArray($_POST, $index, $xssClean);
    }
    self::parseInput('form');
    return self::fetchFromArray(self::$input['forms'], $index, $xssClean);
  }

  public static function files($index = null) {
    if (Request::method() != 'post') {
      self::parseInput('form');
      return self::$input['files'];
    }

    $news = [];
    foreach ($_FILES as $key1 => $file) {
      foreach ($file as $type => $val1) {
        foreach (self::cover2($val1, $key1) as $key2 => $val2) {
          $news[$key2] ?? $news[$key2] = [];
          $news[$key2][$type] = $val2;
        }
      }
    }

    $files = [];
    foreach ($news as $key => $new) {
      array_push($files, urlencode($key) . '=' . urlencode(json_encode($new)));
    }

    parse_str(implode('&', $files), $files);
    return self::cover1($files);
  }

  public static function queries($index = null, $xssClean = true) {
    return self::fetchFromArray($_GET, $index, $xssClean);
  }

  public static function rawText() {
    $contentType = Request::headers('Content-Type');

    $result = preg_match('/^\s*(?P<type>text\/plain)(\s*;\s*)?(?P<other>.*)?\s*$/i', $contentType, $matches);
    if ($result && $matches && $matches['type']) {
      return self::inputStream();
    }

    $result = preg_match('/^\s*(?P<type>application\/json)(\s*;\s*)?(?P<other>.*)?\s*$/i', $contentType, $matches);
    if ($result && $matches && $matches['type']) {
      return self::inputStream();
    }

    return null;
  }

  public static function rawJson() {
    $contentType = Request::headers('Content-Type');

    $result = preg_match('/^\s*(?P<type>application\/json)(\s*;\s*)?(?P<other>.*)?\s*$/i', $contentType, $matches);
    if (!($result && $matches && $matches['type'])) {
      return null;
    }

    $body = self::inputStream();
    return isJson($body) ? $body : null;
  }

  private static function inputStream($index = null, $xssClean = null) {
    $body = '';
    $data = fopen('php://input', 'r');
    while ($chunk = fread($data, 1024)) {
      $body .= $chunk;
    }
    fclose($data);
    return $body;
  }
}

if (!function_exists('baseURL')) {
  function baseURL() {
    static $baseURL;

    if ($baseURL === null) {
      $baseURL = config('Other', 'baseURL');

      if ($baseURL === null && isset($_SERVER['HTTP_HOST'])) {
        $protocol = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
        $baseURL = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/';
      }

      $baseURL || QQ('尚未設定 baseURL！', '請先至 config/[環境/]Other.php 內設定 baseURL');
      $baseURL = rtrim($baseURL, '/') . '/';
    }

    return $baseURL . trim(preg_replace('/\/+/', '/', implode('/', arrayFlatten(func_get_args()))), '/');
  }
}
