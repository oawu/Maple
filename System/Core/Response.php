<?php

abstract class Response {
  public static $code = 200;
  public static $isApi = false;
  private static $headers = [];
  private static $statusText = [ // https://zh.wikipedia.org/wiki/HTTP%E7%8A%B6%E6%80%81%E7%A0%81
    100 => 'Continue',              101 => 'Switching Protocols', 102 => 'Processing',
    200 => 'OK',                    201 => 'Created',             202 => 'Accepted',         203 => 'Non-Authoritative Information', 204 => 'No Content',      205 => 'Reset Content',              206 => 'Partial Content',         207 => 'Multi-Status',                  208 => 'Already Reported', 226 => 'IM Used',
    300 => 'Multiple Choices',      301 => 'Moved Permanently',   302 => 'Found',            303 => 'See Other',                     304 => 'Not Modified',    305 => 'Use Proxy',                  306 => 'Switch Proxy',            307 => 'Temporary Redirect',            308 => 'Permanent Redirect',
    400 => 'Bad Request',           401 => 'Unauthorized',        402 => 'Payment Required', 403 => 'Forbidden',                     404 => 'Not Found',       405 => 'Method Not Allowed',         406 => 'Not Acceptable',          407 => 'Proxy Authentication Required', 408 => 'Request Timeout',  409 => 'Conflict',     410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Request Entity Too Large', 414 => 'Request-URI Too Long', 415 => 'Unsupported Media Type', 416 => 'Requested Range Not Satisfiable', 417 => 'Expectation Failed', 418 => 'I\'m a teapot', 420 => 'Enhance Your Caim', 421 => 'Misdirected Request', 422 => 'Unprocessable Entity', 423 => 'Locked', 424 => 'Failed Dependency', 425 => 'Unodered Cellection', 426 => 'Upgrade Required', 428 => 'Precondition Required', 429 => 'Too Many Requests', 431 => 'Request Header Fields Too Large', 444 => 'No Response', 450 => 'Blocked by Windows Parental Controls', 451 => 'Unavailable For Legal Reasons', 494 => 'Request Header Too Large',
    500 => 'Internal Server Error', 501 => 'Not Implemented',     502 => 'Bad Gateway',      503 => 'Service Unavailable',           504 => 'Gateway Timeout', 505 => 'HTTP Version Not Supported', 506 => 'Variant Also Negotiates', 507 => 'Insufficient Storage',          508 => 'Loop Detected',    510 => 'Not Extended', 511 => 'Network Authentication Required'
  ];

  private static function setByCode() {
    $str = self::$statusText[Response::$code] ?? self::$statusText[Response::$code = 500];

    if (strpos(PHP_SAPI, 'cgi') === 0)
      return Response::addHeader('Status: ' . Response::$code . ' ' . $str, true);

    $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';

    in_array($protocol, ['HTTP/1.0', 'HTTP/1.1', 'HTTP/2']) || $protocol = 'HTTP/1.1';

    return Response::addHeader($protocol . ' ' . Response::$code . ' ' . $str, true, Response::$code);
  }

  public static function addHeader($header, $replacr = true, $code = null) {
    return array_push(self::$headers, [$header, $replacr, $code]);
  }

  public static function setHeader() {
    if (Request::method() == 'cli')
      return true;

    self::setByCode();
    $zlibOc = (bool)ini_get('zlib.output_compression');

    foreach (self::$headers as $header) {
      if (!$header)
        continue;
  
      if ($zlibOc && strncasecmp($header[0], 'content-length', 14) === 0)
        continue;

      switch (count($header)) {
        default:
          break;

        case 1:
          @header($header[0]);
          break;

        case 2:
          @header($header[0], $header[1]);
          break;

        case 3:
          @header($header[0], $header[1], $header[2]);
          break;
      }
    }
  }

  public static function clean($key = null) {
    self::$code = null;
    self::$isApi = null;
    self::$headers = null;
    self::$statusText = null;
    return true;
  }

  public static function redirect($url, $code = 307) {
    Response::$code = $code;
    Response::addHeader('Refresh:0;url=' . $url);
    Response::setHeader();
  }

  public static function output($result) {
    $display = function($type = 'html', $str) {
      switch ($type) {
        case 'json':
          Response::addHeader('Content-Type: application/json; charset=UTF-8');
          $str = json_encode($str);
          break;
        
        default:
        case 'html':
          Response::addHeader('Content-Type: text/html; charset=UTF-8');
          break;
      }

      Response::setHeader();
      echo $str;
      return $str;
    };

    if ($result === null)
      return $display('html', '');

    if (is_bool($result))
      return $display('html', '');

    if (is_array($result))
      return $display('json', $result);

    if ($result instanceOf View)
      return $display('html', $result->output());

    if ($result instanceOf DateTime)
      return $display('html', $result->format('Y-m-d H:i:s'));

    if (is_object($result) && method_exists($result, '__toString'))
      return $display('html', $result->__toString());
    
    if (is_object($result) && method_exists($result, 'toString'))
      return $display('html', $result->toString());

    if (is_object($result) && method_exists($result, 'toArray'))
      return $display('json', $result->toArray());

    if (is_object($result))
      return QQ('Controller 回傳類型不明！', '請檢查「' . $path . '.php」檔案的「' . $method . '」method 內的回傳格式！', '物件「' . get_class($result) . '」沒有 toString 模式。');

    if (is_resource($result))
      return QQ('Controller 回傳類型錯誤！', '不支援 resource 格式。');

    return Response::$isApi
      ? $display('json', ['messages' => [$result]])
      : $display('html', $result);
  }
}
