<?php

namespace CMD\Clean;

use \CMD\Layout\Menu  as Menu;
use \CMD\Layout\Check as Check;
use \CMD\Str          as Str;
use \CMD\Tool         as Tool;
use \CMD\Display      as Display;

class Layout {
  public static function clean() {
    $args  = func_get_args();
    $check = array_shift($args);
    $path  = array_shift($args);
    $title = array_shift($args);

    \Load::systemFunc('File') ?: Display::error('載入 System/Func/File 失敗！');

    filesDelete($path, true);
    
    $files = array_values(array_map(function($name) use ($path) {
      return (is_dir($tmp = $path . $name) ? '目錄：' : '檔案：') . Tool::depath($tmp . (is_dir($tmp) ? DIRECTORY_SEPARATOR : ''));
    }, array_filter(scandir(PATH_FILE_CACHE), function($name) { return !in_array($name, ['.', '..', '.DS_Store']); })));

    $files && Display::error(array_merge(['無法刪除檔案如下：'], $files));
    
    Display::title('完成');
    Display::markListLine('清除' . Display::colorBoldWhite($title) . '成功。');
    echo Display::LN;
  }

  public static function get() {
    if (!\Load::system('Env'))
      return null;

    $items = array_map(function($item) {
      return Check::create('清除' . $item[1] . '目錄', 'Clean ' . $item[2] . ' Dir')
                  ->appendTip('清除' . Display::colorBoldWhite($item[1]) . '(' . \Xterm::gray($item[2], true) . ')目錄。')
                  ->appendTip(Display::controlC())
                  ->thing('\CMD\Clean\Layout::clean', $item);
    }, [
      [PATH_FILE_CACHE, '快取', 'Cache'],
      [PATH_FILE_TMP, '暫存', 'Tmp']
    ]);

    return Menu::create('清除檔案目錄', 'Clean Cache')
               ->appendItems($items);
  }
}