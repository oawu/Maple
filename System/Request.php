<?php

use \Request\Payload;

final class Request {
  private static ?array $_servers = null;
  private static ?array $_other = null;
  private static ?array $_method = null;
  private static ?array $_headers = null;
  private static ?array $_cookies = null;
  private static ?array $_queries = null;
  private static ?array $_paths = null;
  private static array $_params = [];

  public static function getServers(): array {
    if (self::$_servers !== null) {
      return self::$_servers['val'];
    }
    self::$_servers = [
      'val' => isset($_SERVER) && is_array($_SERVER) ? $_SERVER : []
    ];
    return self::$_servers['val'];
  }
  public static function getUserAgent(): string {
    if (self::$_other !== null && array_key_exists('userAgent', self::$_other)) {
      return self::$_other['userAgent'];
    }
    if (self::$_other === null) {
      self::$_other = [];
    }

    $server = self::getServers();
    $userAgent = $server['HTTP_USER_AGENT'] ?? '';
    $userAgent = is_string($userAgent) ? $userAgent : '';
    self::$_other['userAgent'] = $userAgent;
    return $userAgent;
  }
  public static function getIp(): string {
    if (self::$_other !== null && array_key_exists('ip', self::$_other)) {
      return self::$_other['ip'];
    }
    if (self::$_other === null) {
      self::$_other = [];
    }

    $server = self::getServers();
    $ip = $server['REMOTE_ADDR'] ?? '0.0.0.0';
    $ip = filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    self::$_other['ip'] = $ip;
    return $ip;
  }
  public static function getMethod(): string {
    if (self::$_method !== null) {
      return self::$_method['val'];
    }

    if (PHP_SAPI === 'cli' || defined('STDIN')) {
      self::$_method['val'] = 'CLI';
      return self::$_method['val'];
    }

    $method = 'get';

    $server = self::getServers();

    $_method = $server['REQUEST_METHOD'] ?? '';
    if (is_string($_method) && $_method !== '') {
      $method = strtoupper(trim($_method));
    }

    if ($method === 'POST') {
      $data = Payload::getData();
      $_method = $data['_method'] ?? '';
      if (is_string($_method) && $_method !== '') {
        $method = strtoupper(trim($_method));
      }
    }

    $method = ['val' => strtoupper(is_string($method) && $method !== '' ? $method : 'get')];
    self::$_method = $method;
    return $method['val'];
  }
  public static function getHeaders(): array {
    if (self::$_headers !== null) {
      return self::$_headers['val'];
    }

    $header = [];
    if (function_exists('apache_request_headers')) {
      foreach (apache_request_headers() as $key => $val) {
        $key = ucwords(strtolower($key), '-');
        $header[$key] = $val;
      }
    } else {
      if (isset($_SERVER['CONTENT_TYPE'])) {
        $header['Content-Type'] = $_SERVER['CONTENT_TYPE'];
      }

      foreach ($_SERVER as $key => $val) {
        if (sscanf($key, 'HTTP_%s', $_headers) === 1) {
          $newKey = str_replace(' ', '-', ucwords(strtolower($_headers), ' '));
          $header[$newKey] = $_SERVER[$key];
        }
      }
    }

    $headers = ['val' => is_array($header) ? $header : []];
    self::$_headers = $headers;
    return $headers['val'];
  }
  public static function getCookies(): array {
    if (self::$_cookies !== null) {
      return self::$_cookies['val'];
    }

    $cookie = ['val' => isset($_COOKIE) && is_array($_COOKIE) ? $_COOKIE : []];
    self::$_cookies = $cookie;
    return $cookie['val'];
  }
  public static function getQueries(): array {
    if (self::$_queries !== null) {
      return self::$_queries['val'];
    }

    $queries = ['val' => isset($_GET) && is_array($_GET) ? $_GET : []];
    self::$_queries = $queries;
    return $queries['val'];
  }
  public static function getPaths(): array {
    if (self::$_paths !== null) {
      return self::$_paths['val'];
    }

    $paths = [];

    if (self::getMethod() == 'CLI') {
      $server = self::getServers();
      $_paths = array_slice($server['argv'] ?? [], 1);
      foreach ($_paths as $path) {
        $tmps = Helper::explodePath($path, '/', ['/']);
        foreach ($tmps as $tmp) {
          $paths[] = $tmp;
        }
      }

      $paths = ['val' => is_array($paths) ? $paths : []];
      self::$_paths = $paths;
      return $paths['val'];
    }

    $server = self::getServers();

    $tmp = parse_url('http://__' . $server['REQUEST_URI'] ?? '');

    if ($tmp && isset($tmp['path'])) {
      $_paths = Helper::explodePath(urldecode($tmp['path']), '/', ['/']);
      foreach ($_paths as $path) {
        $paths[] = urldecode($path);
      }
    }

    $paths = ['val' => is_array($paths) ? $paths : []];
    self::$_paths = $paths;
    return $paths['val'];
  }
  public static function getParams(): array {
    return self::$_params;
  }
  public static function setParams(array $params): void {
    self::$_params = $params;
  }
  public static function servers(): array {
    return self::getServers();
  }
  public static function userAgent(): string {
    return self::getUserAgent();
  }
  public static function ip(): string {
    return self::getIp();
  }
  public static function method(): string {
    return self::getMethod();
  }
  public static function headers(): array {
    return self::getHeaders();
  }
  public static function cookies(): array {
    return self::getCookies();
  }
  public static function queries(): array {
    return self::getQueries();
  }
  public static function paths(): array {
    return self::getPaths();
  }
  public static function params(): array {
    return self::getParams();
  }
}
