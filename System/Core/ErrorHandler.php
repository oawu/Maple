<?php

if (!function_exists('GG')) {
  function GG($text, $code = 500, $contents = [], $showMessage = true) {
    Response::$code = $code;

    $messages = $showMessage ? $contents['msgs'] ?? [$text] : [];
    $details  = $contents['details'] ?? [];
    $traces   = $contents['traces'] ?? array_map(function($trace) { return ['path' => $trace['file'] ?? '[呼叫函式]', 'line' => $trace['line'] ?? null, 'info' => ($trace['class'] ?? '') . ($trace['type'] ?? '') . ($trace['function'] ?? '') . (isset($trace['args']) ? '(' . implodeRecursive(', ', $trace['args']) . ')' : '')]; }, debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT));

    $i = 0;
    $traces = array_map(function($trace) use (&$i) { $trace['index'] = $i++; $trace['path'] .= $trace['line'] !== null ? '(' . $trace['line'] . ')' : ''; unset($trace['line']); return $trace; }, array_filter($traces, function($trace) { return !isset($trace['path']) || $trace['path'] !== __FILE__; }));

    if (Request::method() == 'cli') {
      @system('clear');
      $view = View::create(implode(DIRECTORY_SEPARATOR, ['_', Response::$code === 404 ? 'Cli404.php' : 'CliGG.php']));

      echo $view->path() === null
        ? json_encode(
          array_filter(
            array_merge($messages
              ? ['messages' => $messages]
              : [], [
                'details' => $details,
                'traces' => $traces])))
        : $view->with('messages', $messages)
               ->with('details', $details)
               ->with('traces',  $traces);
    } else {
      @ob_end_clean();

      if (Response::$isApi) {
        Response::addHeader('Content-Type: application/json; charset=UTF-8');
        Response::setHeader();
        
        echo json_encode(
          array_filter(
            array_merge($messages
              ? ['messages' => $messages]
              : [], [
                'details' => $details,
                'traces' => $traces])));

      } else {
        Response::addHeader('Content-Type: text/html; charset=UTF-8');
        Response::setHeader();

        $view = View::create(implode(DIRECTORY_SEPARATOR, ['_', Response::$code === 404 ? 'Html404.php' : 'HtmlGG.php']));
        echo $view->with('messages', $messages)
               ->with('details', $details)
               ->with('traces',  $traces);
      }
    }

    exit(1);
  }
}

if (!function_exists('QQ')) {
  function QQ() {
    return ($args = func_get_args())
      ? count($args) == 1
        ? GG(array_shift($args), 500)
        : GG('', 500, ['msgs' => $args])
      : GG('', 500, [], false);
  }
}

/* --------------------------------------------------
 *  定義自己的 Error Handler
 * -------------------------------------------------- */

if (!function_exists('errorHandler')) {
  function errorHandler($severity, $message, $filepath, $line) {
    // 一般錯誤，例如 1/0; 這種錯誤！

    $levels = [
      E_NOTICE => '提示(Notice)',
      E_WARNING => '警告(Warning)',
      E_ERROR => '錯誤(Error)',

      E_PARSE => '解析錯誤(Parsing Error)',
      E_STRICT => '執行提示(Runtime Notice)',
      E_RECOVERABLE_ERROR => '可修正錯誤(Recoverable Error)',
      E_DEPRECATED => '棄用(Deprecated)',
      E_ALL => '所有(All)',

      E_USER_DEPRECATED => '用戶棄用(User Deprecated)',
      E_USER_NOTICE => '用戶提示(User Notice)',
      E_USER_WARNING => '用戶警告(User Warning)',
      E_USER_ERROR => '用戶錯誤(User Error)',
      
      E_CORE_WARNING => '核心警告(Core Warning)',
      E_CORE_ERROR => '核心錯誤(Core Error)',

      E_COMPILE_WARNING => '編譯警告(Compile Warning)',
      E_COMPILE_ERROR => '編譯錯誤(Compile Error)'];


    $isError = ((E_ERROR | E_PARSE | E_COMPILE_ERROR | E_CORE_ERROR | E_USER_ERROR) & $severity) === $severity;

    if (($severity & error_reporting()) !== $severity)
      return;

    $details = [
      ['title' => '類型', 'content' => $levels[$severity] ?? $severity],
      ['title' => '訊息', 'content' => $message],
      ['title' => '位置', 'content' => $filepath . '(' . $line . ')']
    ];

    $traces = array_map(function($trace) {
      return [
        'path' => $trace['file']      ?? '[呼叫函式]',
        'line' => $trace['line']      ?? null,
        'info' => ($trace['class']    ?? '')
                . ($trace['type']     ?? '')
                . ($trace['function'] ?? '')
                . (isset($trace['args']) ? '(' . implodeRecursive(', ', $trace['args']) . ')' : '')
      ];
    }, debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT));

    if (str_ireplace(['off', 'none', 'no', 'false', 'null'], '', ini_get('display_errors')))
      return GG($message, 500, ['details' => $details, 'traces' => $traces], false);

    if (!$isError)
      return Log::warning(['details' => $details, 'traces' => $traces]);

    Log::error(['details' => $details, 'traces' => $traces]);
    Response::$code = 500;
    Response::setHeader();
    exit(1);
  }
}

if (!function_exists('exceptionHandler')) {
  function exceptionHandler($exception) {
    // 寫法有誤，例如沒加 ;
    // 沒定義 func
    
    $message = '有 Exception 未使用 try catch！';

    $details = [
      ['title' => '類型', 'content' => get_class($exception)],
      ['title' => '訊息', 'content' => $exception->getMessage()],
      ['title' => '位置', 'content' => $exception->getFile() . '(' . $exception->getLine() . ')']
    ];

    $traces = array_map(function($trace) {
      return [
        'path' => $trace['file']      ?? '[呼叫函式]',
        'line' => $trace['line']      ?? null,
        'info' => ($trace['class']    ?? '')
                . ($trace['type']     ?? '')
                . ($trace['function'] ?? '')
                . (isset($trace['args']) ? '(' . implodeRecursive(', ', $trace['args']) . ')' : '')
      ];
    }, $exception->getTrace());

    if (str_ireplace(['off', 'none', 'no', 'false', 'null'], '', ini_get('display_errors')))
      return GG($message, 500, ['details' => $details, 'traces' => $traces], false);
    
    Log::error(['details' => $details, 'traces' => $traces]);
    Response::$code = 500;
    Response::setHeader();
    exit(1);
  }
}

if (!function_exists('shutdownHandler')) {
  function shutdownHandler() {
    // 長時間沒反應過來的，例如：while(1) {}

    $lastError = error_get_last();
    isset($lastError['type'])
      && ($lastError['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING))
      && errorHandler($lastError['type'], $lastError['message'], $lastError['file'], $lastError['line']);
  }
}

set_error_handler('errorHandler');
set_exception_handler('exceptionHandler');
register_shutdown_function('shutdownHandler');

class MapleException extends Exception {
  private $messages = [];
  private $statusCode = 400;

  public function __construct($messages) {
    isset($messages[0])
      && is_numeric($messages[0])
      && $this->statusCode = array_shift($messages);

    $this->messages = $messages;

    parent::__construct(implode('、', $this->messages));
  }

  public function getStatusCode() {
    return $this->statusCode;
  }

  public function getMessages() {
    return $this->messages;
  }
}

if (!function_exists('error')) {
  function error() {
    $args = func_get_args();
    throw new MapleException($args);
  }
}

if (!function_exists('ifError')) {
  function ifError($defined = null) {
    static $closure;

    if (is_callable($defined))
      return $closure = $defined;

    if ($closure === null)
      return null;

    return call_user_func_array($closure, func_get_args());
  }
}

if (!function_exists('ifApiError')) {
  function ifApiError($defined = null) {
    Response::$isApi = true;
    return ifError($defined);
  }
}

if (!function_exists('transaction')) {
  function transaction($closure, $code = 400) {
    $result = \M\transaction($closure, $errors);
    return $errors ? error($code, ...array_merge(['資料庫處理錯誤！'], $errors)) : $result;
  }
}

if (!function_exists('rollback')) {
  function rollback($message = null) {
    return \M\rollback($message);
  }
}
