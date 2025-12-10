<?php

if (!function_exists('error')) {
  function error(string $message = '', ?int $code = null): void {
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
    $exception = new \Error\GG([$message], $code);
    $exception->setFile($backtrace['file'])->setLine($backtrace['line']);
    throw $exception;
  }
}
if (!function_exists('notFound')) {
  function notFound(string $message = '', int $code = 404): void {
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
    $exception = new \Error\NotFound($message, $code);
    $exception->setFile($backtrace['file'])->setLine($backtrace['line']);
    throw $exception;
  }
}

if (!function_exists('transaction')) {
  function transaction(callable $closure, ?string $db = null, ?string $error = null, ?int $code = null) {
    $result = \Orm\Helper::transaction($db, $closure, $errors);

    if ($errors !== null) {
      error($error ?? '資料庫錯誤！', $code ?? 400);
    }

    return $result;
  }
}

if (!function_exists('tryFunc')) {
  function tryFunc(callable $closure, $return = null) {
    try {
      return $closure();
    } catch (\Throwable $e) {
      return $return ?? $e;
    } catch (\Exception $e) {
      return $return ?? $e;
    }
  }
}

if (!function_exists('_handler')) {
  function _handler(array $traces, array $details): void {
    $traces = array_filter(array_map(static function (array $trace): string {
      $path = $trace['file'] ?? '';
      $line = $trace['line'] ?? null;
      return $path . ($line !== null ? '(' . $line . ')' : '');
    },  $traces), fn($trace) => $trace !== '');

    $type = 'html';
    if (PHP_SAPI === 'cli' || defined('STDIN')) {
      $type = 'cli';
    } else if (function_exists('apache_request_headers')) {
      foreach (apache_request_headers() as $key => $val) {
        $key = ucwords(strtolower($key), '-');
        if ($key === 'Accept') {
          if (strpos($val, 'application/json') !== false) {
            $type = 'api';
            break;
          }
        }
      }
    } else {
      foreach ($_SERVER as $key => $val) {
        if (sscanf($key, 'HTTP_%s', $_header) === 1) {
          $key = str_replace(' ', '-', ucwords(strtolower($_header), ' '));
          if ($key === 'Accept') {
            if (strpos($val, 'application/json') !== false) {
              $type = 'api';
              break;
            }
          }
        }
      }
    }

    if (in_array($type, ['api', 'html'])) {
      $code = 500;
      $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
      in_array($protocol, ['HTTP/1.0', 'HTTP/1.1', 'HTTP/2']) || $protocol = 'HTTP/1.1';
      header($protocol . ' ' . $code . ' Internal Server Error', true, $code);
    }

    $buffer = ob_get_contents();
    ob_end_clean();

    switch ($type) {
      case 'api':
        header('Content-Type: application/json; charset=UTF-8', true, 0);
        echo json_encode(['details' => $details, 'traces' => $traces, 'buffer' => $buffer]);
        break;

      case 'html':
        header('Content-Type: text/html; charset=UTF-8', true, 0);
        $strs = [...array_map(fn($detail) => $detail['title'] . ': ' . $detail['content'], $details), ...($traces ? ['', '追蹤：', ...$traces] : [])];
        echo '<!DOCTYPE html><html lang="zh-Hant"><head><meta http-equiv="Content-Language" content="zh-tw"><meta http-equiv="Content-type" content="text/html; charset=utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no,minimal-ui"><meta name="robots" content="noindex,nofollow,noarchive"><meta name="googlebot" content="noindex,nofollow,noarchive"><title>GG Error | Maple 9</title></head><body><main>';
        echo implode('<br/>', $strs);
        echo '</main></body></html>';
        break;

      case 'cli':
        $strs = [...array_map(fn($detail) => $detail['title'] . ': ' . $detail['content'], $details), ...($traces ? ['', '追蹤：', ...$traces] : [])];
        echo "\n";
        echo implode("\n", $strs);
        echo "\n";
        echo "\n";
        break;
    }

    exit(0);
  }
}

set_error_handler(static function (int $no, string $message, string $file, int $line): void {
  if (class_exists('Err')) {
    Err::emitError($no, $message, $file, $line);
  }

  if (class_exists('Response')) {
    Response::handlerError($no, $message, $file, $line);
  }

  _handler(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT), [
    ['title' => '類型', 'content' => $no],
    ['title' => '訊息', 'content' => trim($message)],
    ['title' => '位置', 'content' => $file . '(' . $line . ')']
  ]);
});

set_exception_handler(static function (\Throwable $exception): void {
  if (class_exists('Err')) {
    Err::emitException($exception);
  }

  if (class_exists('Response')) {
    Response::handlerException($exception);
  }

  _handler($exception->getTrace(), [
    ['title' => '類型', 'content' => get_class($exception)],
    ['title' => '訊息', 'content' => trim($exception->getMessage())],
    ['title' => '位置', 'content' => $exception->getFile() . '(' . $exception->getLine() . ')']
  ]);
});

register_shutdown_function(static function () {
  $error = error_get_last();

  if (!($error && is_array($error) && isset($error['type'], $error['message'], $error['file'], $error['line']))) {
    return;
  }

  if (class_exists('Response')) {
    Response::handlerError($error['type'], $error['message'], $error['file'], $error['line']);
  }

  _handler(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT), [
    ['title' => '類型', 'content' => $error['type']],
    ['title' => '訊息', 'content' => trim($error['message'])],
    ['title' => '位置', 'content' => $error['file'] . '(' . $error['line'] . ')']
  ]);
});

ob_start();
