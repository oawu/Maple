<?php

namespace Cmd;

use \Cmd\Result\Group;
use \Cmd\Result\Group\Step;

class Init {
  public static function create(string $env): Result {
    return Result::create('專案初始', static function () use ($env): void {

      $inited = is_file(PATH_FILE . '.inited');

      Group::create('App 目錄', static function () use ($inited): void {
        self::_checkDirs([
          '檢查 App 目錄' => PATH_APP,
          '檢查 App/Controller 目錄' => PATH_APP_CONTROLLER,
          '檢查 App/Middleware 目錄' => PATH_APP_MIDDLEWARE,
          '檢查 App/Model 目錄' => PATH_APP_MODEL,
          '檢查 App/View 目錄' => PATH_APP_VIEW,
        ]);

        if (!$inited) {
          self::_checkDirs([
            '檢查 App/View/_ 目錄' => PATH_APP_VIEW . '_' . DIRECTORY_SEPARATOR,
            '檢查 App/View/_/404 目錄' => PATH_APP_VIEW . '_' . DIRECTORY_SEPARATOR . '404' . DIRECTORY_SEPARATOR,
            '檢查 App/View/_/Error 目錄' => PATH_APP_VIEW . '_' . DIRECTORY_SEPARATOR . 'Error' . DIRECTORY_SEPARATOR,
          ]);

          self::_checkFiles([
            '檢查 App/Controller/Main.php 檔案' => [
              'path' => PATH_APP_CONTROLLER . 'Main.php',
              'data' => Template::php('Controller')
            ],
            '檢查 App/Middleware/Cors.php 檔案' => [
              'path' => PATH_APP_MIDDLEWARE . 'Cors.php',
              'data' => Template::php('Cors')
            ],

            '檢查 App/View/Main.php 檔案' => [
              'path' => PATH_APP_VIEW . 'Main.php',
              'data' => Template::get('View/Main'),
            ],
            '檢查 App/View/_/404/cli.php 檔案' => [
              'path' => PATH_APP_VIEW . '_' . DIRECTORY_SEPARATOR . '404' . DIRECTORY_SEPARATOR . 'Cli.php',
              'data' => Template::php('View/404/Cli'),
            ],
            '檢查 App/View/_/404/Html.php 檔案' => [
              'path' => PATH_APP_VIEW . '_' . DIRECTORY_SEPARATOR . '404' . DIRECTORY_SEPARATOR . 'Html.php',
              'data' => Template::get('View/404/Html'),
            ],
            '檢查 App/View/_/Error/Cli.php 檔案' => [
              'path' => PATH_APP_VIEW . '_' . DIRECTORY_SEPARATOR . 'Error' . DIRECTORY_SEPARATOR . 'Cli.php',
              'data' => Template::php('View/Error/Cli'),
            ],
            '檢查 App/View/_/Error/Html.php 檔案' => [
              'path' => PATH_APP_VIEW . '_' . DIRECTORY_SEPARATOR . 'Error' . DIRECTORY_SEPARATOR . 'Html.php',
              'data' => Template::get('View/Error/Html'),
            ],
            '檢查 App/View/_/Error/Production.php 檔案' => [
              'path' => PATH_APP_VIEW . '_' . DIRECTORY_SEPARATOR . 'Error' . DIRECTORY_SEPARATOR . 'Production.php',
              'data' => Template::get('View/Error/Production'),
            ],
          ]);
        }
      });

      Group::create('Config 目錄', static function () use ($env, $inited): void {
        self::_checkDirs([
          '檢查 Config 目錄' => PATH_CONFIG,
          '檢查 Config/' . $env . ' 目錄' => PATH_CONFIG . $env . DIRECTORY_SEPARATOR,
        ]);

        if (!$inited) {
          self::_checkFiles([
            '檢查 Config/Cache.php 檔案' => [
              'path' => PATH_CONFIG . 'Cache.php',
              'data' => Template::get('Config/Cache')
            ],
            '檢查 Config/MySql.php 檔案' => [
              'path' => PATH_CONFIG . 'MySql.php',
              'data' => Template::get('Config/MySql')
            ],
            '檢查 Config/Migration.php 檔案' => [
              'path' => PATH_CONFIG . 'Migration.php',
              'data' => Template::get('Config/Migration')
            ],
            '檢查 Config/Model.php 檔案' => [
              'path' => PATH_CONFIG . 'Model.php',
              'data' => Template::get('Config/Model')
            ],
          ]);
        }

        self::_checkFiles([
          '檢查 Config/' . $env . '/MySql.php 檔案' => [
            'path' => PATH_CONFIG . $env . DIRECTORY_SEPARATOR . 'MySql.php',
            'data' => \Helper\File::read(PATH_CONFIG . 'MySql.php')
          ],
          '檢查 Config/' . $env . '/Model.php 檔案' => [
            'path' => PATH_CONFIG . $env . DIRECTORY_SEPARATOR . 'Model.php',
            'data' => \Helper\File::read(PATH_CONFIG . 'Model.php')
          ],
        ]);
      });
      Group::create('System 目錄', static function () use ($env): void {
        self::_checkFiles([
          '檢查 System/_Key.php 檔案' => [
            'path' => PATH_SYSTEM . '_Key.php',
            'data' => Template::php('Key', ['key' => bin2hex(random_bytes(16))])
          ],
          '檢查 System/_Env.php 檔案' => [
            'path' => PATH_SYSTEM . '_Env.php',
            'data' => Template::php('Env', ['env' => $env]),
            'isForce' => true
          ],
        ]);
      });
      Group::create('File 目錄', static function () use ($env, $inited): void {
        self::_checkDirs([
          '檢查 File 目錄' => PATH_FILE,
          '檢查 File/Log 目錄' => PATH_FILE_LOG,
          '檢查 File/Cache 目錄' => PATH_FILE_CACHE,
          '檢查 File/Tmp 目錄' => PATH_FILE_TMP,
        ]);
      });
      Group::create('Public 目錄', static function () use ($env, $inited): void {
        self::_checkDirs([
          '檢查 Public 目錄' => PATH_PUBLIC,
          '檢查 Public/Storage 目錄' => PATH_PUBLIC . 'Storage' . DIRECTORY_SEPARATOR,
        ]);
        self::_checkFiles([
          '檢查 Public/index.php 檔案' => [
            'path' => PATH_PUBLIC . 'index.php',
            'data' => Template::php('Public')
          ],
          '檢查 Public/.htaccess 檔案' => [
            'path' => PATH_PUBLIC . '.htaccess',
            'data' => Template::read('Htaccess')
          ],
        ]);
      });
      Group::create('Router 目錄', static function () use ($env, $inited): void {
        self::_checkDirs([
          '檢查 Router 目錄' => PATH_ROUTER,
        ]);
        self::_checkFiles([
          '檢查 Router/Default.php 檔案' => [
            'path' => PATH_ROUTER . 'Main.php',
            'data' => Template::php('Router')
          ],
        ]);
      });
      Group::create('Migration 目錄', static function () use ($env, $inited): void {
        self::_checkDirs([
          '檢查 Migration 目錄' => PATH_MIGRATION,
        ]);
      });

      if ($inited) {
        return;
      }

      @\Helper\File::write(PATH_FILE . '.inited', date('Y-m-d H:i:s'));
    });
  }

  private static function _umaskMkdir(string $path, int $mode = 0777, bool $recursive = false): bool {
    if (is_dir($path)) {
      return true;
    }

    $oldmask = umask(0);
    $result = mkdir($path, $mode, $recursive);
    umask($oldmask);
    return $result;
  }
  private static function _checkDirs(array $dirs): void {
    foreach ($dirs as $title => $path) {
      Step::create($title, static function () use ($path): void {
        if (is_file($path)) {
          throw new \Exception('已存在相同名稱的檔案，路徑：' . $path);
        }

        if (!is_dir($path)) {
          @self::_umaskMkdir($path, 0777, true);

          if (!is_dir($path)) {
            throw new \Exception('建立失敗，路徑：' . $path);
          }
        }
      });
    }
  }
  private static function _checkFiles(array $files): void {
    foreach ($files as $title => $file) {
      Step::create($title, static function () use ($file) {
        $path = $file['path'];
        $data = $file['data'];
        $isForce = $file['isForce'] ?? false;

        if (is_dir($path)) {
          throw new \Exception('已存在相同名稱的目錄，路徑：' . $path);
        }

        if (!is_file($path) || $isForce) {
          @\Helper\File::write($path, $data);

          if (!is_file($path)) {
            throw new \Exception('建立失敗，路徑：' . $path);
          }
        }
      });
    }
  }
}
