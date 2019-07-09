<?php

/* --------------------------------------------------
 *  定義自己的 Error Handler
 * -------------------------------------------------- */

if (!function_exists('errorHandler')) {
  function errorHandler($severity, $message, $filepath, $line) {
    // 一般錯誤，例如 1/0; 這種錯誤！
    
    $levels = [
      E_ERROR => 'Error',
      E_PARSE => 'Parsing Error',
      E_NOTICE => 'Notice',
      E_STRICT => 'Runtime Notice',
      E_WARNING => 'Warning',
      E_CORE_ERROR => 'Core Error',
      E_USER_ERROR => 'User Error',
      E_USER_NOTICE => 'User Notice',
      E_USER_WARNING => 'User Warning',
      E_CORE_WARNING => 'Core Warning',
      E_COMPILE_ERROR => 'Compile Error',
      E_COMPILE_WARNING => 'Compile Warning',
    ];

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
      new GG($message, 500, ['details' => $details, 'traces' => $traces], false);

    if (!$isError)
      return Log::warning(['details' => $details, 'traces' => $traces]);

    Log::error(['details' => $details, 'traces' => $traces]);
    Status::$code = 500;
    Status::header();
    exit(1);
  }
}

if (!function_exists('exceptionHandler')) {
  function exceptionHandler($exception) {
    // 寫法有誤，例如沒加 ;
    
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
      new GG($message, 500, ['details' => $details, 'traces' => $traces], false);

    Log::error(['details' => $details, 'traces' => $traces]);
    Status::$code = 500;
    Status::header();
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
