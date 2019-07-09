<?php

namespace CMD\Clean;

class Cli extends \CMD\Layout\Cli {
  private static function clean($path) {
    filesDelete($path, true);
    
    $files = array_values(array_map(function($name) use ($path) { return (is_dir($tmp = $path . $name) ? '目錄：' : '檔案：') . Tool::depath($tmp . (is_dir($tmp) ? DIRECTORY_SEPARATOR : '')); }, array_filter(scandir(PATH_FILE_CACHE), function($name) { return !in_array($name, ['.', '..', '.DS_Store']); })));
    $files && self::fail(array_merge(['無法刪除檔案如下：'], $files));
    
    self::success('清除成功');
  }

  public static function get($action) {
    \Load::system('Env') ?: self::fail('載入 System/Env 失敗！');
    \Load::systemFunc('File') ?: self::fail('載入 System/Func/File 失敗！');

    switch ($action) {
      case 'cache': self::clean(PATH_FILE_CACHE); break;
      case 'tmp':   self::clean(PATH_FILE_TMP);   break;
      default:      self::fail('不明原因錯誤！');   break;
    }
  }
}
