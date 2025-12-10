<?php

use \Error\GG;
use \Error\NotFound;

final class Response {
  public const TYPE_CLI = 'cli';
  public const TYPE_API = 'api';
  public const TYPE_HTML = 'html';
  public const TYPE = [
    self::TYPE_CLI => 'Cli 模式',
    self::TYPE_API => 'API',
    self::TYPE_HTML => '網頁',
  ];

  public const ERROR_LEVEL = [
    E_ALL => '所有(All)',

    E_ERROR => '錯誤(Error)',
    E_WARNING => '警告(Warning)',
    E_NOTICE => '提示(Notice)',

    E_PARSE => '解析錯誤(Parsing Error)',
    E_STRICT => '執行提示(Runtime Notice)',
    E_RECOVERABLE_ERROR => '可修正錯誤(Recoverable Error)',
    E_DEPRECATED => '棄用(Deprecated)',

    E_USER_ERROR => '用戶錯誤(User Error)',
    E_USER_WARNING => '用戶警告(User Warning)',
    E_USER_NOTICE => '用戶提示(User Notice)',
    E_USER_DEPRECATED => '用戶棄用(User Deprecated)',

    E_CORE_ERROR => '核心錯誤(Core Error)',
    E_CORE_WARNING => '核心警告(Core Warning)',

    E_COMPILE_ERROR => '編譯錯誤(Compile Error)',
    E_COMPILE_WARNING => '編譯警告(Compile Warning)',
  ];

  // https://zh.wikipedia.org/wiki/HTTP%E7%8A%B6%E6%80%81%E7%A0%81
  public const STATUS_TEXT = [
    100 => 'Continue',
    101 => 'Switching Protocols',
    102 => 'Processing',
    200 => 'OK',
    201 => 'Created',
    202 => 'Accepted',
    203 => 'Non-Authoritative Information',
    204 => 'No Content',
    205 => 'Reset Content',
    206 => 'Partial Content',
    207 => 'Multi-Status',
    208 => 'Already Reported',
    226 => 'IM Used',
    300 => 'Multiple Choices',
    301 => 'Moved Permanently',
    302 => 'Found',
    303 => 'See Other',
    304 => 'Not Modified',
    305 => 'Use Proxy',
    306 => 'Switch Proxy',
    307 => 'Temporary Redirect',
    308 => 'Permanent Redirect',
    400 => 'Bad Request',
    401 => 'Unauthorized',
    402 => 'Payment Required',
    403 => 'Forbidden',
    404 => 'Not Found',
    405 => 'Method Not Allowed',
    406 => 'Not Acceptable',
    407 => 'Proxy Authentication Required',
    408 => 'Request Timeout',
    409 => 'Conflict',
    410 => 'Gone',
    411 => 'Length Required',
    412 => 'Precondition Failed',
    413 => 'Request Entity Too Large',
    414 => 'Request-URI Too Long',
    415 => 'Unsupported Media Type',
    416 => 'Requested Range Not Satisfiable',
    417 => 'Expectation Failed',
    418 => 'I\'m a teapot',
    420 => 'Enhance Your Calm',
    421 => 'Misdirected Request',
    422 => 'Unprocessable Entity',
    423 => 'Locked',
    424 => 'Failed Dependency',
    425 => 'Too Early',
    426 => 'Upgrade Required',
    428 => 'Precondition Required',
    429 => 'Too Many Requests',
    431 => 'Request Header Fields Too Large',
    444 => 'No Response',
    449 => 'Retry With',
    450 => 'Blocked by Windows Parental Controls',
    451 => 'Unavailable For Legal Reasons',
    460 => 'Client Closed Connection',
    494 => 'Request Header Too Large',
    495 => 'SSL Certificate Error',
    496 => 'SSL Certificate Required',
    497 => 'HTTP Request Sent to HTTPS Port',
    499 => 'Client Closed Request',
    500 => 'Internal Server Error',
    501 => 'Not Implemented',
    502 => 'Bad Gateway',
    503 => 'Service Unavailable',
    504 => 'Gateway Timeout',
    505 => 'HTTP Version Not Supported',
    506 => 'Variant Also Negotiates',
    507 => 'Insufficient Storage',
    508 => 'Loop Detected',
    510 => 'Not Extended',
    511 => 'Network Authentication Required'
  ];

  private static ?bool $_isDisplayError = null;
  private static ?int $_code = null;
  private static ?string $_type = null;
  private static array $_headers = [];

  public static function getIsDisplayError(): bool {
    if (self::$_isDisplayError === null) {
      self::$_isDisplayError = !in_array(strtolower(trim(ini_get('display_errors'))), ['0', 'off', 'none', 'no', 'false', 'null', ''], true);
    }
    return self::$_isDisplayError;
  }
  public static function setCode(int $code): void {
    self::$_code = $code;
  }
  public static function setHeader(string $key, string $value, bool $replace = true, int $code = 0): void {
    self::$_headers[] = [
      'key' => $key,
      'value' => $value,
      'replace' => $replace,
      'code' => $code
    ];
  }
  public static function setType(string $type): void {
    if (array_key_exists($type, self::TYPE)) {
      self::$_type = $type;
    }
  }

  private static function _getType(): string {
    if (self::$_type !== null) {
      return self::$_type;
    }

    if (defined('MAPLE_CMD') && MAPLE_CMD) {
      return self::TYPE_CLI;
    }

    $method = Request::getMethod();
    if ($method === 'CLI') {
      return self::TYPE_CLI;
    }

    $header = Request::getHeaders();
    $accepts = array_filter(array_map('trim', explode(',', $header['Accept'] ?? '')), fn($a) => $a !== '');
    foreach ($accepts as $accept) {
      if (strpos($accept, 'application/json') !== false) {
        return self::TYPE_API;
      }
    }

    return self::TYPE_HTML;
  }
  private static function _executeHeader(): void {
    if (self::_getType() == self::TYPE_CLI) {
      return;
    }

    $code = self::$_code ?? 200;
    $str = self::STATUS_TEXT[$code] ?? '';
    if ($str === '') {
      $code = 500;
      $str = self::STATUS_TEXT[$code] ?? '';
    }
    $headers = [];

    foreach (self::$_headers as $header) {
      $headers[] = $header;
    }

    if (strpos(PHP_SAPI, 'cgi') === 0) {
      $headers = [[
        'key' => 'Status',
        'value' => $code . ' ' . $str,
        'replace' => true,
        'code' => 0
      ]];
    } else {
      $server = Request::getServers();
      $protocol = 'HTTP/1.1';
      if (isset($server['SERVER_PROTOCOL'])) {
        $protocol = $server['SERVER_PROTOCOL'];
      }
      if (!in_array($protocol, ['HTTP/1.0', 'HTTP/1.1', 'HTTP/2'])) {
        $protocol = 'HTTP/1.1';
      }
      $headers[] = [
        'key' => $protocol . ' ' . $code . ' ' . $str,
        'value' => null,
        'replace' => true,
        'code' => $code
      ];
    }

    foreach ($headers as $header) {
      $h = isset($header['value']) ? $header['key'] . ': ' . $header['value'] : $header['key'];
      $r = is_bool($header['replace'] ?? null) ? $header['replace'] : true;
      $c = is_int($header['code'] ?? null) ? $header['code'] : 0;
      header($h, $r, $c);
    }
  }

  private static function _handlerProd(array $messages, array $details, array $traces): void {
    $buffer = ob_get_contents();
    if (ob_get_level() > 0) {
      ob_end_clean();
    }

    self::setHeader('Content-Type', 'text/html; charset=UTF-8');
    self::_executeHeader();

    echo View::create('_/Error/Production')
      ->with('messages', $messages)
      ->with('details', $details)
      ->with('traces', $traces)
      ->with('buffer', $buffer);
  }
  private static function _handlerApi(array $messages, array $details, array $traces): void {
    $buffer = ob_get_contents();
    if (ob_get_level() > 0) {
      ob_end_clean();
    }

    self::setHeader('Content-Type', 'application/json; charset=UTF-8');
    self::_executeHeader();
    echo json_encode(ENVIRONMENT === 'Production'
      ? ['messages' => $messages]
      : ['messages' => $messages, 'details' => $details, 'traces' => array_map(fn($trace) => ['path' => $trace['path'], 'info' => $trace['info']], $traces), 'buffer' => $buffer]);
  }
  private static function _handlerHtml(array $messages, array $details, array $traces): void {
    $buffer = ob_get_contents();
    if (ob_get_level() > 0) {
      ob_end_clean();
    }

    self::setHeader('Content-Type', 'text/html; charset=UTF-8');
    self::_executeHeader();
    echo View::create('_/Error/Html')
      ->with('messages', $messages)
      ->with('details', ENVIRONMENT === 'Production' ? [] : $details)
      ->with('traces', ENVIRONMENT === 'Production' ? [] : $traces)
      ->with('buffer', ENVIRONMENT === 'Production' ? '' : $buffer);
  }
  private static function _handlerCli(array $messages, array $details, array $traces): void {
    $buffer = ob_get_contents();
    if (ob_get_level() > 0) {
      ob_end_clean();
    }

    $view = View::create('_/Error/Cli');
    $path = $view->getPath();

    if ($path === null) {

      $messages = implode("\n", array_map(fn($message) => '  ' . str_replace("\n", "\n  ", $message), $messages ?? []));
      $details = implode("\n", array_map(fn($detail) => '  ◉ ' . $detail['title'] . '：' . $detail['content'], $details ?? []));
      $traces = implode("\n", array_map(fn($trace) => '  ◉ ' . $trace['info'] . "\n" . '    ↳ ' . $trace['path'], $traces ?? []));

      if (!($messages || $details || $traces || $buffer !== '')) {
        echo "\n【錯誤】\n";
      }

      if ($messages) {
        echo "\n【錯誤訊息】\n" . $messages . "\n\n";
      }

      if ($details) {
        echo "\n【錯誤原因】\n" . $details . "\n\n";
      }

      if ($traces) {
        echo "\n【錯誤追蹤】\n" . $traces . "\n\n";
      }

      if ($buffer !== '') {
        echo "\n【緩衝區內容】\n" . ($buffer ? $buffer . "\n" : '') . "\n";
      }

      return;
    }

    echo $view
      ->with('messages', array_filter($messages, fn($m) => $m !== ''))
      ->with('details', $details)
      ->with('traces', $traces)
      ->with('buffer', $buffer);
  }
  private static function _handler(bool $isDisplayError, int $code, array $messages, array $traces, array $details): void {
    $index = 0;

    $traces = array_map(static function (array $trace) use (&$index): array {
      $path = $trace['file'] ?? '[呼叫函式]';
      $line = $trace['line'] ?? null;

      $info = implode('', [
        $trace['class']    ?? '',
        $trace['type']     ?? '',
        $trace['function'] ?? '',
      ]);

      if (isset($trace['args'])) {
        $info .= '(';
        $info .= implode(', ', is_array($trace['args'])
          ? array_map(fn($a) => Helper::dump($a, ''), $trace['args'])
          : [$trace['args']]);
        $info .= ')';
      }
      return [
        'index' => $index++,
        'path' => $path . ($line !== null ? '(' . $line . ')' : ''),
        'info' => $info
      ];
    }, $traces);

    self::setCode($code);
    $type = self::_getType();

    if (in_array($type, [self::TYPE_API, self::TYPE_HTML]) && !$isDisplayError) {
      self::_handlerProd($messages, $details, $traces);
      exit(1);
    }

    switch ($type) {
      case self::TYPE_API:
        self::_handlerApi($messages, $details, $traces);
        break;
      case self::TYPE_CLI:
        self::_handlerCli($messages, $details, $traces);
        break;
      case self::TYPE_HTML:
        self::_handlerHtml($messages, $details, $traces);
        break;
    }

    exit(1);
  }

  private static function _notFoundApi(string $message, array $details, array $traces): void {
    $buffer = ob_get_contents();
    if (ob_get_level() > 0) {
      ob_end_clean();
    }

    self::setHeader('Content-Type', 'application/json; charset=UTF-8');
    self::_executeHeader();
    echo json_encode(ENVIRONMENT === 'Production'
      ? ['messages' => [$message]]
      : ['messages' => [$message], 'details' => $details, 'traces' => array_map(fn($trace) => ['path' => $trace['path'], 'info' => $trace['info']], $traces), 'buffer' => $buffer]);
  }
  private static function _notFoundCli(string $message, array $details, array $traces): void {
    $buffer = ob_get_contents();
    if (ob_get_level() > 0) {
      ob_end_clean();
    }

    echo View::create('_/404/Cli')
      ->with('message', $message)
      ->with('details', $details)
      ->with('traces', $traces)
      ->with('buffer', $buffer);
  }
  private static function _notFoundHtml(string $message, array $details, array $traces): void {
    $buffer = ob_get_contents();
    if (ob_get_level() > 0) {
      ob_end_clean();
    }

    self::setHeader('Content-Type', 'text/html; charset=UTF-8');
    self::_executeHeader();
    echo View::create('_/404/Html')
      ->with('message', ENVIRONMENT === 'Production' ? '' : $message)
      ->with('details', ENVIRONMENT === 'Production' ? [] : $details)
      ->with('traces', ENVIRONMENT === 'Production' ? [] : $traces)
      ->with('buffer', ENVIRONMENT === 'Production' ? '' : $buffer);
  }

  public static function notFound(?NotFound $exception = null): void {
    if ($exception === null) {
      $exception = new NotFound('迷路惹！', 404);
    }

    $message = $exception->getMessage();

    $details = [
      ['title' => '類型', 'content' => get_class($exception)],
      ['title' => '訊息', 'content' => $message],
      ['title' => '位置', 'content' => $exception->getFile() . '(' . $exception->getLine() . ')']
    ];

    $index = 0;
    $traces = array_map(static function (array $trace) use (&$index): array {
      $path = $trace['file'] ?? '[呼叫函式]';
      $line = $trace['line'] ?? null;

      $info = implode('', [
        $trace['class']    ?? '',
        $trace['type']     ?? '',
        $trace['function'] ?? '',
      ]);

      if (isset($trace['args'])) {
        $info .= '(';
        $info .= implode(', ', is_array($trace['args'])
          ? array_map(fn($a) => Helper::dump($a, ''), $trace['args'])
          : [$trace['args']]);
        $info .= ')';
      }
      return [
        'index' => $index++,
        'path' => $path . ($line !== null ? '(' . $line . ')' : ''),
        'info' => $info
      ];
    }, $exception->getTrace());

    self::setCode($exception->getStatusCode());

    switch (self::_getType()) {
      case self::TYPE_API:
        self::_notFoundApi($message, $details, $traces);
        break;
      case self::TYPE_CLI:
        self::_notFoundCli($message, $details, $traces);
        break;
      case self::TYPE_HTML:
        self::_notFoundHtml($message, $details, $traces);
        break;
    }

    exit(1);
  }
  public static function handlerError(int $no, string $message, string $file, int $line): void {
    $message = trim($message);

    $details = [
      ['title' => '類型', 'content' => self::ERROR_LEVEL[$no] ?? $no],
      ['title' => '訊息', 'content' => $message],
      ['title' => '位置', 'content' => $file . '(' . $line . ')']
    ];

    self::_handler(
      self::getIsDisplayError(),
      500,
      [$message],
      debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT),
      $details
    );
  }
  public static function handlerException(\Throwable $exception): void {
    $message = trim($exception->getMessage());

    $details = [
      ['title' => '類型', 'content' => get_class($exception)],
      ['title' => '訊息', 'content' => $message],
      ['title' => '位置', 'content' => $exception->getFile() . '(' . $exception->getLine() . ')']
    ];

    if ($exception instanceof NotFound) {
      self::notFound($exception);
      return;
    }

    if ($exception instanceof GG) {
      self::_handler(
        true,
        $exception->getStatusCode(),
        $exception->getMessages(),
        $exception->getTrace(),
        $details
      );
      return;
    }

    self::_handler(
      self::getIsDisplayError(),
      500,
      [$message],
      $exception->getTrace(),
      $details
    );
  }

  private static function _outputApi($result): void {
    $_ = ob_get_contents();
    if (ob_get_level() > 0) {
      ob_end_clean();
    }

    self::setHeader('Content-Type', 'application/json; charset=UTF-8');
    self::_executeHeader();

    if ($result instanceof \DateTime) {
      echo json_encode($result->format('Y-m-d H:i:s'));
      return;
    }
    if (is_object($result) && method_exists($result, '__toString')) {
      echo json_encode($result->__toString());
      return;
    }
    if (is_object($result) && method_exists($result, 'toString')) {
      echo json_encode($result->toString());
      return;
    }
    if (is_object($result) && method_exists($result, 'toArray')) {
      echo json_encode($result->toArray());
      return;
    }

    echo json_encode($result);
    return;
  }
  private static function _outputHtml($result): void {
    $_ = ob_get_contents();
    if (ob_get_level() > 0) {
      ob_end_clean();
    }

    $isJson = is_array($result) || (is_object($result) && method_exists($result, 'toArray'));
    self::setHeader('Content-Type', $isJson ? 'application/json; charset=UTF-8' : 'text/html; charset=UTF-8');
    self::_executeHeader();

    if ($result === null) {
      echo '';
      return;
    }
    if (is_bool($result)) {
      echo $result ? 'true' : 'false';
      return;
    }
    if (is_numeric($result)) {
      echo (string)$result;
      return;
    }
    if (is_string($result)) {
      echo $result;
      return;
    }
    if (is_array($result)) {
      echo json_encode($result);
      return;
    }
    if ($result instanceof \DateTime) {
      echo $result->format('Y-m-d H:i:s');
      return;
    }
    if (is_object($result) && method_exists($result, '__toString')) {
      echo $result->__toString();
      return;
    }
    if (is_object($result) && method_exists($result, 'toString')) {
      echo $result->toString();
      return;
    }
    if (is_object($result) && method_exists($result, 'toArray')) {
      echo json_encode($result->toArray());
      return;
    }

    echo '';
    return;
  }
  private static function _outputCli($result): void {
    $_ = ob_get_contents();
    if (ob_get_level() > 0) {
      ob_end_clean();
    }

    if ($result === null) {
      echo $_;
      return;
    }
    if (is_bool($result)) {
      echo $result ? 'true' : 'false';
      return;
    }
    if (is_numeric($result)) {
      echo (string)$result;
      return;
    }
    if (is_string($result)) {
      echo $result;
      return;
    }
    if (is_array($result)) {
      echo json_encode($result);
      return;
    }
    if ($result instanceof \DateTime) {
      echo $result->format('Y-m-d H:i:s');
      return;
    }
    if (is_object($result) && method_exists($result, '__toString')) {
      echo $result->__toString();
      return;
    }
    if (is_object($result) && method_exists($result, 'toString')) {
      echo $result->toString();
      return;
    }
    if (is_object($result) && method_exists($result, 'toArray')) {
      echo json_encode($result->toArray());
      return;
    }

    echo Helper::dump($result);
    return;
  }
  public static function output($result): void {
    switch (self::_getType()) {
      case self::TYPE_API:
        self::_outputApi($result);
        break;
      case self::TYPE_CLI:
        self::_outputCli($result);
        break;
      case self::TYPE_HTML:
        self::_outputHtml($result);
        break;
    }
  }
}
