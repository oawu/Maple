<?php

namespace CMD\Migration;

use \CMD\Layout\Menu    as Menu;
use \CMD\Layout\Check   as Check;
use \CMD\Layout\Input   as Input;
use \CMD\Layout\Subitme as Subitme;
use \CMD\Str            as Str;
use \CMD\Tool           as Tool;
use \CMD\Display        as Display;

class Layout {
  public static function validatorVersion() {
    $args    = func_get_args();
    $version = array_shift($args);

    if (!is_numeric($version))
      return '請輸入正確的版號格式。';

    $version = (int)$version;

    $keys = array_keys(\Migration::files());
    $last = end($keys);

    return $version < 0 || $version > $last ? [
      '請輸入正確的版號範圍。',
      '目前可用的版號範圍為' . 0 . ' ~ ' . $last
    ] : null;
  }

  public static function submenu() {
    $now   = \Migration::nowVersion();
    $files = \Migration::files(true);

    return array_map(function($file) use ($now) {
      return Subitme::create($file['ver'] <= $now, $file['ver'], preg_replace('/^\d{3}-/', '', basename($file['name'], '.php')), $file['at']);
    }, $files);
  }

  public static function gotoVersionFirst() {
    $args    = func_get_args();
    $check   = array_shift($args);

    return self::gotoVersion($check, 0);
  }

  public static function gotoVersionLatest() {
    $args    = func_get_args();
    $check   = array_shift($args);

    $keys = array_keys(\Migration::files());
    $last = end($keys);

    return self::gotoVersion($check, $last);
  }

  public static function gotoVersion() {
    $args    = func_get_args();
    $input   = array_shift($args);
    $version = array_shift($args);
    $version = (int)$version;

    Display::title('執行');
    Display::markArrowLine('執行 Migration 中，請稍候 ..' . Display::LN);

    $now   = \Migration::nowVersion();
    $error = \Migration::to($version);
    $error && Display::error(array_map(function($key, $err) { return $key . '：' . $err; }, array_keys($error), $error));

    $input->showTips();
    Display::title('請輸入以下資訊');
    Display::markArrowLine('請輸入要更新的版本號' . Display::markSemicolon() . Display::colorBoldWhite($version) . Display::LN);

    Display::title('請輸入以下資訊');
    Display::markArrowLine('請確認以上資訊是否正確？[y：確定, n：取消]' . Display::markSemicolon() . Display::colorBoldWhite('確定') . Display::LN);

    Display::title('完成');
    Display::markListLine('執行 Migration 成功。');
    Display::markListLine('Migration 已從' . Display::colorBoldWhite('第 ' . $now . ' 版') . '更新至' . Display::colorBoldWhite('第 ' . $version . ' 版') . '。');
    Display::markListLine('Migration 目前版本是' . Display::colorBoldWhite('第 ' . \Migration::nowVersion() . ' 版') . '。');
    echo Display::LN;
  }

  public static function get() {
    if (!\Load::system('Env'))
      return null;

    try {
      \Load::systemLib('Migration');
    } catch (\Exception $e) {
      return null;
    }

    $item1 = Check::create('更新至最新版', 'Update to the latest version')
                  ->appendTip('Migration 將會更新至' . \Xterm::gray('最新版本', true) . '。')
                  ->appendTip(Display::controlC())
                  ->thing('\CMD\Migration\Layout::gotoVersionLatest');

    $item2 = Input::create('輸入更新版號', 'Enter the version number')
                  ->isCheck()
                  ->appendTip('Migration 將會更新至' . \Xterm::gray('您輸入的版本', true) . '。')
                  ->appendTip('降版可能會使資料被刪除，系統' . Display::colorBoldWhite('不會') . '幫您保留資料，建議請先判斷是否備份資料後在執行。')
                  ->appendTip(Display::controlC())
                  ->appendInput('請輸入要更新的版本號', true, '/[0-9]+/')
                  ->setValidator('\CMD\Migration\Layout::validatorVersion')
                  ->thing('\CMD\Migration\Layout::gotoVersion');

    $item3 = Check::create('降版至最開始', 'Down to the first version')
                  ->appendTip('Migration 將會還原至' . \Xterm::gray('最一開始的版本', true) . '。')
                  ->appendTip('系統' . Display::colorBoldWhite('不會') . '幫您保留資料，降版可能會使資料被刪除，建議請先判斷是否備份資料後在執行。')
                  ->appendTip(Display::controlC())
                  ->thing('\CMD\Migration\Layout::gotoVersionFirst');

    return Menu::create('執行 Migration', 'Migration Update')
               ->setSubitems('CMD\Migration\Layout::submenu')
               ->appendItem($item1)
               ->appendItem($item2)
               ->appendItem($item3);
  }
}
