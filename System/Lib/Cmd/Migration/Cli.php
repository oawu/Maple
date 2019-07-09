<?php

namespace CMD\Migration;

class Cli extends \CMD\Layout\Cli {
  private static function now() {
    self::success(
      \Migration::nowVersion()
    );
  }

  private static function gotoVersion($version) {
    $version = (int)$version;

    $now  = \Migration::nowVersion();
    $errors = \Migration::to($version);
    $errors && self::fail(array_map(function($key, $err) { return $key . '：' . $err; }, array_keys($errors), $errors));

    self::success(
      'Migration 已從第 ' . $now . ' 版更新至第 ' . $version . ' 版。',
      'Migration 目前版本是第 ' . \Migration::nowVersion() . ' 版。'
    );
  }

  private static function gotoVersionLatest() {
    $version = array_keys(\Migration::files());
    $version = end($version);
    return self::gotoVersion($version);
  }

  private static function gotoVersionFirst() {
    return self::gotoVersion(0);
  }

  public static function get($action) {
    \Load::system('Env') ?: self::fail('載入 System/Env 失敗！');
    \Load::systemLib('Migration') ?: self::fail('載入 System/Lib/Migration 失敗！');

    switch ($action) {
      case 'new':
        self::gotoVersionLatest();
        break;
      
      case 'ori':
        self::gotoVersionFirst();
        break;
      
      case 'now':
        self::now();
        break;
      
      default:
        self::fail('不明原因錯誤！');
        break;
    }
  }
}
