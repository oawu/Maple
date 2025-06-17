<?php

namespace Cmd;

use \Cmd\Result\Group;
use \Cmd\Result\Group\Step;

final class Create {
  public static function model(string $name, array $argv): Result {
    return Result::create('', static function () use ($name, $argv): void {
      [
        'name' => $name,
        'images' => $images,
        'files' => $files
      ] = Group::create('建立 Model', static function () use ($name, $argv) {
        [$images, $files] = Step::create('取得 Uploader', fn() => self::_uploader($argv));

        Step::create('檢查 App/Model 目錄是否可以寫入', static function () {
          if (!is_writable(PATH_APP_MODEL)) {
            throw new \Exception('App/Model 目錄無法寫入');
          }
        });

        $file = PATH_APP_MODEL . $name . '.php';

        Step::create('檢查是否重複的 Model', static function () use ($file) {
          if (file_exists($file)) {
            throw new \Exception('已經存在：' . $file);
          }
        });

        Step::create('新增 Model', static function () use ($file, $name, $images, $files) {
          @\Helper\File::write($file, Template::php('Model', [
            'model' => $name,
            'images' => $images,
            'files' => $files
          ]));

          if (!file_exists($file)) {
            throw new \Exception('新增「' . $name . '」失敗');
          }
        });

        return [
          'name' => $name,
          'images' => $images,
          'files' => $files
        ];
      });

      Group::create('建立完成', static function () use ($name, $images, $files) {
        Step::create('Model 名稱', $name);
        $images && Step::create('圖片 Uploader', implode(', ', $images));
        $files && Step::create('檔案 Uploader', implode(', ', $files));
      });
    });
  }
  public static function migration(array $argvs): Result {
    return Result::create('', static function () use ($argvs): void {
      [
        'version'  => $version,
        'filename'  => $filename,
      ] = Group::create('建立 Migration', static function () use ($argvs): array {
        Step::create('檢查 Migration 目錄是否可以寫入', static function (): void {
          if (!is_writable(PATH_MIGRATION)) {
            throw new \Exception('無法寫入 ' . PATH_MIGRATION);
          }
        });

        Step::create('檢查資源', static function (): void {
          $pathKey = PATH_SYSTEM . '_Key.php';
          $pathEnv = PATH_SYSTEM . '_Env.php';

          if (!(file_exists($pathKey) && file_exists($pathEnv) && is_readable($pathKey) && is_readable($pathEnv))) {
            throw new \Exception('請先初始專案，執行指令「php Maple init」');
          }

          require_once $pathEnv;
          require_once $pathKey;
          require_once PATH_SYSTEM . 'Core/Model.php';
        });

        [
          'type'  => $type,
          'name'  => $name,
          'column' => $column,
          'action'  => $action,
          'file'  => $file,
          'version'  => $version,
          'filename'  => $filename,
        ] = Step::create('取得版本', static function () use ($argvs): array {
          $type     = array_shift($argvs);
          $name     = array_shift($argvs);
          $action   = array_shift($argvs) ?? '';
          $column   = array_shift($argvs) ?? '';
          $argvs    = implode(' ', $argvs) ?: '';

          $version  = Migration::getLatestVersion() + 1;
          $filename = trim($type . ' ' . $name . ' ' . $action . ' ' . $column . ' ' . $argvs);
          $file = PATH_MIGRATION . sprintf('%03s-%s.php', $version, $filename);

          return [
            'type'  => $type,
            'name'  => $name,
            'column' => $column,
            'action'  => $action,
            'file'  => $file,
            'version'  => $version,
            'filename'  => $filename,
          ];
        });

        Step::create('檢查是否重複的 Migration', static function () use ($file): void {
          if (file_exists($file)) {
            throw new \Exception('已經存在：' . $file);
          }
        });
        Step::create('新增 Migration', static function () use ($file, $type, $name, $action, $column): void {
          @\Helper\File::write($file, Template::php('Migration', [
            'type' => $type == 'alter' && $action !== ''
              ? $type . '-' . $action
              : $type,
            'name' => $name,
            'column' => $column
          ]));

          if (!file_exists($file)) {
            throw new \Exception($file . '新增失敗');
          }
        });

        return [
          'version'  => $version,
          'filename'  => $filename,
        ];
      });

      Group::create('建立完成', static function () use ($version, $filename): void {
        Step::create('版本', $version);
        Step::create('名稱', $filename);
      });
    });
  }

  private static function _uploader(array $argv): array {
    $images = [];
    $files = [];

    $name = null;

    foreach ($argv as $token) {
      if (in_array($token, ['-P', '--pic'])) {
        $name = 'images';
      } else if (in_array($token, ['-F', '--file'])) {
        $name = 'files';
      } else if (isset($$name)) {
        $$name[] = $token;
      }
    }

    return [$images, $files];
  }
}
