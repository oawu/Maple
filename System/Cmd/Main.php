<?php

namespace Cmd;

use \Cmd\Result\Group;
use \Cmd\Result\Group\Step;

final class Main {
  public static function create(string $feature, array $argvs): Result {
    switch ($feature) {
      case 'init':
        return self::_init($argvs);
      case 'create':
        return self::_create($argvs);
      case 'migration':
        return self::_migration($argvs);
      case 'clean':
        return self::_clean($argvs);
      default:
        return Help::create();
    }
  }

  private static function _init(array $argvs): Result {
    $env = strtolower(trim(array_shift($argvs) ?? ''));

    switch ($env) {
      case 'development':
      case 'dev':
        return Init::create('Development');
      case 'beta':
        return Init::create('Beta');
      case 'staging':
        return Init::create('Staging');
      case 'production':
      case 'prod':
        return Init::create('Production');
      case 'local':
        return Init::create('Local');
    }

    return Help::create('Init');
  }
  private static function _create(array $argvs): Result {
    $type = trim(array_shift($argvs) ?? '');
    if ($type === '' || !in_array($type, ['-M', '-I', '--model', '--migration'])) {
      return Help::create('Create');
    }

    return in_array($type, ['-M', '--model'])
      ? self::_createModel($argvs)
      : self::_createMigration($argvs);
  }
  private static function _createModel(array $argvs): Result {
    $name = trim(array_shift($argvs) ?? '');
    return $name === ''
      ? Help::create('CreateModel')
      : Create::model($name, $argvs);
  }
  private static function _createMigration(array $argvs): Result {
    return count($argvs) < 2
      ? Help::create('CreateModel')
      : Create::migration($argvs);
  }
  private static function _migration(array $argvs): Result {
    $type = trim(array_shift($argvs) ?? '');
    $isRefresh = in_array($type, ['-R', '--refresh']);

    if ($isRefresh) {
      return Result::create('', static function () use ($type): void {
        Group::create('核心', static function () use ($type): void {
          [
            'pathKey' => $pathKey,
            'pathEnv' => $pathEnv,
          ] = Step::create('檢查', static function (): array {
            $pathKey = PATH_SYSTEM . '_Key.php';
            $pathEnv = PATH_SYSTEM . '_Env.php';

            if (!(file_exists($pathKey) && file_exists($pathEnv) && is_readable($pathKey) && is_readable($pathEnv))) {
              throw new \Exception('請先初始專案，執行指令「php Maple init」');
            }

            return [
              'pathKey' => $pathKey,
              'pathEnv' => $pathEnv,
            ];
          });

          Step::create('載入', static function () use ($pathKey, $pathEnv): void {
            require_once $pathKey;
            require_once $pathEnv;
            require_once PATH_SYSTEM . 'Core/Model.php';
          });
        });

        Migration::refresh();
      });
    }

    if ($type === '') {
      $type = null;
    }

    if (!is_numeric($type) && $type !== null) {
      return Help::create('Migration');
    }

    if (is_numeric($type)) {
      $type = 1 * $type;
      if (!is_int($type) || $type < 0) {
        return Help::create('Migration');
      }
    }

    return Result::create('', static function () use ($type): void {
      $version = Group::create('核心', static function () use ($type): int {
        [
          'pathKey' => $pathKey,
          'pathEnv' => $pathEnv,
        ] = Step::create('檢查', static function (): array {
          $pathKey = PATH_SYSTEM . '_Key.php';
          $pathEnv = PATH_SYSTEM . '_Env.php';

          if (!(file_exists($pathKey) && file_exists($pathEnv) && is_readable($pathKey) && is_readable($pathEnv))) {
            throw new \Exception('請先初始專案，執行指令「php Maple init」');
          }

          return [
            'pathKey' => $pathKey,
            'pathEnv' => $pathEnv,
          ];
        });

        Step::create('載入', static function () use ($pathKey, $pathEnv): void {
          require_once $pathKey;
          require_once $pathEnv;
          require_once PATH_SYSTEM . 'Core/Model.php';
        });

        return (int) Step::create('版本確認', static function () use ($type): int {
          return is_int($type) ? $type : Migration::getLatestVersion();
        });
      });

      Migration::execute($version);
    });
  }
  private static function _clean(array $argvs): Result {
    $dirs = trim(array_shift($argvs) ?? '');

    if ($dirs && !in_array($dirs, ['-C', '--cache', '-T', '--tmp'])) {
      return Help::create('Clean');
    }

    if (in_array($dirs, ['-C', '--cache'])) {
      $dirs = [PATH_FILE_CACHE];
    }

    if (in_array($dirs, ['-T', '--tmp'])) {
      $dirs = [PATH_FILE_TMP];
    }

    if ($dirs === '') {
      $dirs = [PATH_FILE_CACHE, PATH_FILE_TMP];
    }

    return Result::create('', static function () use ($dirs): void {
      Group::create('清除目錄', static function () use ($dirs): void {
        foreach ($dirs as $dir) {
          Step::create('清除 ' . str_replace(PATH, '', $dir), fn() => @\Helper\File::delete($dir, true));
        }
      });

      $files = [];
      foreach ($dirs as $dir) {
        foreach (@\Helper\File::dirInfo($dir) as $file) {
          if (strtolower($file['name']) !== '.ds_store') {
            $files[] = str_replace(PATH, '', $file['path']);
          }
        }
      }

      if ($files) {
        Group::create('無法刪除項目', static function () use ($files): void {
          foreach ($files as $file) {
            Step::create('檔案', $file);
          }
        });
      } else {
        Group::create('完成');
      }
    });
  }
}
