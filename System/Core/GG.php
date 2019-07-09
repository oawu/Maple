<?php

// 遇到困難了嗎？嘿嘿 就知道你 GG 了！

class GG {
  public function __construct($text, $code = 500, $contents = [], $showMessage = true) {
    Status::$code = $code;

    $text = $contents['msgs'] ?? [$text];

    // 判斷一下哪種介面，將之前畫面先清除
    // outputType 決定錯誤訊息以哪種形式丟出，分別有 json, html, cli
    $outputType = 'json';

    if (isCli()) {
      @system('clear');
      !class_exists('View') || $outputType = 'cli';
    } else {
      @ob_end_clean();
      !class_exists('View') || Status::$isApi || $outputType = 'html';
    }

    $details = $contents['details'] ?? [];

    // 沒有 traces 的話，我幫你找！
    $traces = $contents['traces'] ?? array_map(function($trace) {
      return [
        'path' => $trace['file']      ?? '[呼叫函式]',
        'line' => $trace['line']      ?? null,
        'info' => ($trace['class']    ?? '')
                . ($trace['type']     ?? '')
                . ($trace['function'] ?? '')
                . (isset($trace['args']) ? '(' . implodeRecursive(', ', $trace['args']) . ')' : '')
      ];
    }, debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT));

    $i = 0;
    
    $traces = array_map(function($trace) use (&$i) {
      $trace['index'] = $i++;
      $trace['path'] .= $trace['line'] !== null ? '(' . $trace['line'] . ')' : '';
      unset($trace['line']);
      return $trace;
    }, array_filter($traces, function($trace) {
      return !isset($trace['path']) || $trace['path'] !== __FILE__;
    }));

    $messages = $showMessage ? $text : [];

    // 依據類型顯示錯誤
    switch ($outputType) {
      default:
      case 'json':
        Status::append('Content-Type: application/json; charset=UTF-8');
        Status::header();
        echo json_encode(array_filter(array_merge($messages ? ['messages' => $messages] : [], ['details' => $details, 'traces' => $traces])));
        exit(1);
        break;
      
      case 'cli':
        echo View::create(implode(DIRECTORY_SEPARATOR, ['_', $code === 404 ? 'Cli404.php' : 'CliGG.php']), false)
                 ->with('messages', $messages)
                 ->with('details', $details)
                 ->with('traces',  $traces)
                 ->get();
        exit(1);
        break;

      case 'html':
        Status::append('Content-Type: text/html; charset=UTF-8');
        Status::header();
        echo View::create(implode(DIRECTORY_SEPARATOR, ['_', $code === 404 ? 'Html404.php' : 'HtmlGG.php']), false)
                 ->with('messages', $messages)
                 ->with('details', $details)
                 ->with('traces',  $traces)
                 ->get();
        exit(1);
        break;
    }
  }
}

if (!function_exists('gg')) {
  function gg() {
    $args = func_get_args();
    return $args ? count($args) == 1 ? new GG(array_shift($args), 500) : new GG('', 500, [
      'msgs' => $args,
    ]) : new GG('', 500, [], false);
  }
}

