<?php

namespace CMD {
  use function \fileWrite;

  class FeatureCreate {

    private static function uploader($argv) {
      $now = null; $imageColumns = []; $fileColumns = [];

      foreach ($argv as $token)
        in_array($token, ['-P', '--pic', '-F', '--file'])
          ? in_array($token, ['-P', '--pic'])
            ? $now = 'imageColumns'
            : $now = 'fileColumns'
          : isset($$now) && array_push($$now, $token);

      return [$imageColumns, $fileColumns];
    }

    public static function model($modelName, $argv) {
      Display::main('建立 Model');

      list($imageColumns, $fileColumns) = self::uploader($argv);

      Display::title('取得 Image Uploader');
      Display::success('完成');
      
      Display::title('取得 File Uploader');
      Display::success('完成');

      Display::title('檢查 App/Model 目錄是否可以寫入');
      is_writable(PATH_APP_MODEL) ? Display::success(true, '可以寫入') : Display::failure('App/Model 目錄無法寫入！');

      Display::title('檢查是否重複的 Model');
      file_exists($file = PATH_APP_MODEL . $modelName . '.php') ? Display::failure($modelName . ' 已經存在！') : Display::success(true, '未重複');

      Display::title('新增 Model');
      @fileWrite($file, Template::php('Model', ['modelName' => $modelName, 'imageColumns' => $imageColumns, 'fileColumns' => $fileColumns]));
      file_exists($file) ? Display::success() : Display::failure($modelName . ' 新增失敗！');

      Display::main('建立完成');

      return print(QUIET
        ? json_encode(['status' => true, 'name' => $modelName, 'image' => $imageColumns, 'file' => $fileColumns])
        : Display::list(
          'Model 名稱 ─ ' . $modelName,
          $imageColumns ? '圖片 Uploader ─ ' . implode(', ', $imageColumns) : '',
          $fileColumns  ? '檔案 Uploader ─ ' . implode(', ', $fileColumns): ''
        ));
    }

    public static function migration($argv) {
      $type     = array_shift($argv);
      $name     = array_shift($argv);
      $action   = array_shift($argv) ?? '';
      $column   = array_shift($argv) ?? '';
      $argv     = implode(' ', $argv) ?: '';
      $version  = Migration::latestVersion() + 1;
      $filename = trim($type . ' ' . $name . ' ' . $action . ' ' . $column . ' ' . $argv);
      
      Display::main('建立 Migration');

      Display::title('檢查 Migration 目錄是否可以寫入');
      is_writable(PATH_MIGRATION) ? Display::success(true, '可以寫入') : Display::failure('Migration 無法寫入！');

      Display::title('檢查是否重複的 Migration');
      file_exists($file = PATH_MIGRATION . sprintf('%03s-%s.php', $version, $filename)) ? Display::failure('Migration 名稱重複！') : Display::success(true, '未重複');

      Display::title('新增 Model');
      @fileWrite($file, Template::php('Migration', ['type' => $type == 'alter' && $action !== '' ? $type . '-' . $action : $type, 'name' => $name, 'column' => $column]));
      file_exists($file) ? Display::success() : Display::failure($filename . ' 新增失敗！');

      Display::main('建立完成');

      return print(QUIET
        ? json_encode(['status' => true, 'version' => $version, 'filename' => $filename])
        : Display::list(
          '版本 ─ ' . $version,
          '名稱 ─ ' . $filename,
        ));
    }
  }
}
