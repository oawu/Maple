<?php

abstract class Err {
  private static array $_funcs = [];

  private static function _emit(int $code, array $messages, array $traces, array $details) {
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

    foreach (self::$_funcs as $func) {
      $func($code, $messages, $traces, $details);
    }
  }

  public static function emitError(int $no, string $message, string $file, int $line): void {
    $message = trim($message);

    $details = [
      ['title' => '類型', 'content' => \Response::ERROR_LEVEL[$no] ?? $no],
      ['title' => '訊息', 'content' => $message],
      ['title' => '位置', 'content' => $file . '(' . $line . ')']
    ];

    self::_emit(
      500,
      [$message],
      debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT),
      $details
    );
  }

  public static function emitException(\Throwable $exception): void {
    $message = trim($exception->getMessage());

    $details = [
      ['title' => '類型', 'content' => get_class($exception)],
      ['title' => '訊息', 'content' => $message],
      ['title' => '位置', 'content' => $exception->getFile() . '(' . $exception->getLine() . ')']
    ];

    if ($exception instanceof \Error\NotFound) {
      self::_emit(
        404,
        [$message],
        $exception->getTrace(),
        $details
      );
      return;
    }

    if ($exception instanceof \Error\GG) {
      self::_emit(
        $exception->getStatusCode(),
        $exception->getMessages(),
        $exception->getTrace(),
        $details
      );
      return;
    }

    self::_emit(
      500,
      [$message],
      $exception->getTrace(),
      $details
    );
  }

  public static function on(callable $func) {
    self::$_funcs[] = $func;
  }
}