<?php

namespace CMD {
  use function \failure;

  class Display {
    private static $index = 0;

    public static function main($title) {
      self::$index = 0;
      QUIET || print("\n◉ " . $title . "\n");
    }

    public static function title($title) {
      QUIET || print('  ' . ++self::$index . '. ' . $title . ' ─ ');
    }

    public static function success($result = true, $str = '完成') {
      QUIET || print($str . "\n");
      return $result;
    }

    public static function failure($errs = []) {
      QUIET || print("錯誤\n");
      failure($errs);
      exit(1);
    }

    public static function list(...$items) {
      $items = QUIET ? [] : array_filter($items);
      return $items ? implode("\n", array_map(function($item) {
        return '  ※ ' . $item;
      }, $items)) . "\n\n" : '';
    }
  }
}
