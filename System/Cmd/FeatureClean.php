<?php

namespace CMD {
  use function \filesDelete;

  class FeatureClean {

    public static function depath($path) {
      return str_replace(PATH, '', $path);
    }

    public static function dirs($dirs) {
      Display::main('清除目錄');

      foreach ($dirs as $dir) {
        Display::title('清除 ' . self::depath($dir));
        @filesDelete($dir, true);
        $files = array_values(array_map(function($name) use ($dir) { return (is_dir($tmp = $dir . $name) ? '目錄：' : '檔案：') . self::depath($tmp . (is_dir($tmp) ? DIRECTORY_SEPARATOR : '')); }, array_filter(scandir($dir), function($name) { return !in_array($name, ['.', '..', '.DS_Store']); })));
        $files ? Display::failure(array_merge(['無法刪除檔案如下：'], $files)) : Display::success();
      }

      return print(QUIET ? json_encode([
        'status' => true
      ]) : "\n");
    }
  }
}
