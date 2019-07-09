<?php

class Status {
  public static  $isApi = false;
  public static  $headers = [];
  public static  $code = 200;

  private static $funcs = [];
  private static $responseStatusText = [
    // https://zh.wikipedia.org/wiki/HTTP%E7%8A%B6%E6%80%81%E7%A0%81
    100 => 'Continue',              101 => 'Switching Protocols', 102 => 'Processing',
    200 => 'OK',                    201 => 'Created',             202 => 'Accepted',         203 => 'Non-Authoritative Information', 204 => 'No Content',      205 => 'Reset Content',              206 => 'Partial Content',         207 => 'Multi-Status',                  208 => 'Already Reported', 226 => 'IM Used',
    300 => 'Multiple Choices',      301 => 'Moved Permanently',   302 => 'Found',            303 => 'See Other',                     304 => 'Not Modified',    305 => 'Use Proxy',                  306 => 'Switch Proxy',            307 => 'Temporary Redirect',            308 => 'Permanent Redirect',
    400 => 'Bad Request',           401 => 'Unauthorized',        402 => 'Payment Required', 403 => 'Forbidden',                     404 => 'Not Found',       405 => 'Method Not Allowed',         406 => 'Not Acceptable',          407 => 'Proxy Authentication Required', 408 => 'Request Timeout',  409 => 'Conflict',     410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Request Entity Too Large', 414 => 'Request-URI Too Long', 415 => 'Unsupported Media Type', 416 => 'Requested Range Not Satisfiable', 417 => 'Expectation Failed', 418 => 'I\'m a teapot', 420 => 'Enhance Your Caim', 421 => 'Misdirected Request', 422 => 'Unprocessable Entity', 423 => 'Locked', 424 => 'Failed Dependency', 425 => 'Unodered Cellection', 426 => 'Upgrade Required', 428 => 'Precondition Required', 429 => 'Too Many Requests', 431 => 'Request Header Fields Too Large', 444 => 'No Response', 450 => 'Blocked by Windows Parental Controls', 451 => 'Unavailable For Legal Reasons', 494 => 'Request Header Too Large',
    500 => 'Internal Server Error', 501 => 'Not Implemented',     502 => 'Bad Gateway',      503 => 'Service Unavailable',           504 => 'Gateway Timeout', 505 => 'HTTP Version Not Supported', 506 => 'Variant Also Negotiates', 507 => 'Insufficient Storage',          508 => 'Loop Detected',    510 => 'Not Extended', 511 => 'Network Authentication Required'
  ];

  public static function append($header, $replacr = true, $code = null) {
    return array_push(Status::$headers, [$header, $replacr, $code]);
  }

  private static function setByCode() {
    $str = self::$responseStatusText[Status::$code] ?? self::$responseStatusText[Status::$code = 500];

    if (strpos(PHP_SAPI, 'cgi') === 0)
      return Status::append('Status: ' . Status::$code . ' ' . $str, true);

    $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';

    in_array($protocol, ['HTTP/1.0', 'HTTP/1.1', 'HTTP/2']) || $protocol = 'HTTP/1.1';

    return Status::append($protocol . ' ' . Status::$code . ' ' . $str, true, Status::$code);
  }

  public static function header() {
    if (isCli())
      return true;

    $zlibOc = (bool)ini_get('zlib.output_compression');

    self::setByCode();

    foreach (Status::$headers as $header) {
      if (!$header)
        continue;
  
      if ($zlibOc && strncasecmp($header[0], 'content-length', 14) === 0)
        continue;

      switch (count($header)) {
        default: break;
        case 1: @header($header[0]); break;
        case 2: @header($header[0], $header[1]); break;
        case 3: @header($header[0], $header[1], $header[2]); break;
      }
    }
  }

  public static function addFuncs($name, $closure) {
    array_push(self::$funcs, ['name' => $name, 'closure' => $closure]);
  }

  public static function endFuncs() {
    foreach (array_reverse(self::$funcs) as $func)
      if (($closure = $func['closure']) && $closure() !== true)
        if (class_exists('Log'))
          Log::warning('Status::endFuncs 中的「' . $func['name'] . '」 closure 未回傳 true 值！');
  }
}
