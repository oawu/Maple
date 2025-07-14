<?php

namespace Cmd;

class Display {
  private static $_index = 0;

  public static function main(string $title): string {
    self::$_index = 0;
    return "\n◉ " . $title . "\n";
  }
  public static function title(string $title): string {
    return '  ' . ++self::$_index . '. ' . $title . ' ─ ';
  }
  public static function result(string $str): string {
    return $str . "\n";
  }
  public static function list(string ...$items): string {
    $strs = '';
    foreach ($items as $item) {
      $item = trim($item);

      if ($item !== '') {
        $strs .= '  ※ ' . $item . "\n";
      }
    }

    return $strs !== '' ? $strs . "\n" : '';
  }
}
