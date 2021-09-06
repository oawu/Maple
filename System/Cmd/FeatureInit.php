<?php

namespace CMD {
  use function \umaskMkdir;
  use function \fileWrite;
  use function \fileRead;

  class FeatureInit {

    private static function checkDir($title, $path) {
      Display::title($title);
      is_file($path) && Display::failure('已存在相同名稱的檔案');
      is_dir($path) || umaskMkdir($path, 0777, true);
      is_dir($path) || Display::failure('建立失敗');
      return Display::success(true, '完成');
    }

    private static function checkFile($title, $path, $data, $force = false) {
      Display::title($title);
      is_dir($path) && Display::failure('已存在相同名稱的目錄');
      (is_file($path) && !$force) || fileWrite($path, $data);
      is_file($path) || Display::failure('建立失敗');
      return Display::success(true, '完成');
    }

    public static function create($env) {
      QUIET || print("\n專案初始\n");

      $inited = is_file(PATH_FILE . '.inited');

      Display::main('App 目錄');
      self::checkDir('檢查 App 目錄', PATH_APP);
      self::checkDir('檢查 App/Controller 目錄', PATH_APP_CONTROLLER);
      self::checkDir('檢查 App/Middleware 目錄', PATH_APP_MIDDLEWARE);
      self::checkDir('檢查 App/Model 目錄', PATH_APP_MODEL);
      self::checkDir('檢查 App/View 目錄', PATH_APP_VIEW);
      self::checkDir('檢查 App/Func 目錄', PATH_APP_FUNC);
      self::checkDir('檢查 App/Lib 目錄', PATH_APP_LIB);

      if (!$inited) {
        self::checkDir('檢查 App/View/_ 目錄', PATH_APP_VIEW . '_' . DIRECTORY_SEPARATOR);
        self::checkFile('檢查 App/Controller/Main.php 檔案', PATH_APP_CONTROLLER . 'Main.php', Template::php('Controller'));
        self::checkFile('檢查 App/Middleware/Cors.php 檔案', PATH_APP_MIDDLEWARE . 'Cors.php', Template::php('Cors'));
        self::checkFile('檢查 App/View/Main.php 檔案', PATH_APP_VIEW . 'Main.php', Template::get('View'));
        self::checkFile('檢查 App/View/_/Cli404.php 檔案',  PATH_APP_VIEW . '_' . DIRECTORY_SEPARATOR . 'Cli404.php',  Template::php('View/Cli404'));
        self::checkFile('檢查 App/View/_/CliGG.php 檔案',   PATH_APP_VIEW . '_' . DIRECTORY_SEPARATOR . 'CliGG.php',   Template::php('View/CliGG'));
        self::checkFile('檢查 App/View/_/Html404.php 檔案', PATH_APP_VIEW . '_' . DIRECTORY_SEPARATOR . 'Html404.php', Template::get('View/Html404'));
        self::checkFile('檢查 App/View/_/HtmlGG.php 檔案',  PATH_APP_VIEW . '_' . DIRECTORY_SEPARATOR . 'HtmlGG.php',  Template::get('View/HtmlGG'));
      }
      
      Display::main('Config 目錄');
      self::checkDir('檢查 Config 目錄', PATH_CONFIG);
      self::checkDir('檢查 Config/' . $env . ' 目錄', PATH_CONFIG . $env . DIRECTORY_SEPARATOR);
      
      if (!$inited) {
        self::checkFile('檢查 Config/Autoload.php 檔案',  PATH_CONFIG . 'Autoload.php',  Template::get('Config/Autoload'));
        self::checkFile('檢查 Config/Cache.php 檔案',     PATH_CONFIG . 'Cache.php',     Template::get('Config/Cache'));
        self::checkFile('檢查 Config/Cookie.php 檔案',    PATH_CONFIG . 'Cookie.php',    Template::get('Config/Cookie'));
        self::checkFile('檢查 Config/Database.php 檔案',  PATH_CONFIG . 'Database.php',  Template::get('Config/Database'));
        self::checkFile('檢查 Config/Deploy.php 檔案',    PATH_CONFIG . 'Deploy.php',    Template::get('Config/Deploy'));
        self::checkFile('檢查 Config/Extension.php 檔案', PATH_CONFIG . 'Extension.php', Template::get('Config/Extension'));
        self::checkFile('檢查 Config/Migration.php 檔案', PATH_CONFIG . 'Migration.php', Template::get('Config/Migration'));
        self::checkFile('檢查 Config/Model.php 檔案',     PATH_CONFIG . 'Model.php',     Template::get('Config/Model'));
        self::checkFile('檢查 Config/Other.php 檔案',     PATH_CONFIG . 'Other.php',     Template::get('Config/Other'));
        self::checkFile('檢查 Config/Session.php 檔案',   PATH_CONFIG . 'Session.php',   Template::get('Config/Session'));
      }
      
      self::checkFile('檢查 Config/' . $env . '/Database.php 檔案', PATH_CONFIG . $env . DIRECTORY_SEPARATOR . 'Database.php', fileRead(PATH_CONFIG . 'Database.php'));
      self::checkFile('檢查 Config/' . $env . '/Deploy.php 檔案', PATH_CONFIG . $env . DIRECTORY_SEPARATOR . 'Deploy.php', fileRead(PATH_CONFIG . 'Deploy.php'));
      self::checkFile('檢查 Config/' . $env . '/Model.php 檔案', PATH_CONFIG . $env . DIRECTORY_SEPARATOR . 'Model.php', fileRead(PATH_CONFIG . 'Model.php'));
      
      Display::main('System 目錄');
      self::checkFile('檢查 System/Key.php 檔案', PATH_SYSTEM . 'Key.php', Template::php('Key', ['key' => md5(uniqid(mt_rand(), true))]));
      self::checkFile('檢查 System/Env.php 檔案', PATH_SYSTEM . 'Env.php', Template::php('Env', ['env' => $env]), true);

      Display::main('File 目錄');
      self::checkDir('檢查 File 目錄', PATH_FILE);
      self::checkDir('檢查 File/Log 目錄', PATH_FILE_LOG);
      self::checkDir('檢查 File/Cache 目錄', PATH_FILE_CACHE);
      self::checkDir('檢查 File/Session 目錄', PATH_FILE_SESSION);
      self::checkDir('檢查 File/Tmp 目錄', PATH_FILE_TMP);

      Display::main('Public 目錄');
      self::checkDir('檢查 Public 目錄', PATH_PUBLIC);
      self::checkDir('檢查 Public/Storage 目錄', PATH_PUBLIC . 'Storage' . DIRECTORY_SEPARATOR);

      if (!$inited) {
        self::checkFile('檢查 Public/index.php 檔案', PATH_PUBLIC . 'index.php', Template::php('Public'));
        self::checkFile('檢查 Public/.htaccess 檔案', PATH_PUBLIC . '.htaccess', Template::read('Htaccess'));

        Display::main('Router 目錄');
        self::checkDir('檢查 Router 目錄', PATH_ROUTER);
        self::checkFile('檢查 Router/Default.php 檔案', PATH_ROUTER . 'Default.php', Template::php('Router'));
      }

      Display::main('Migration 目錄');
      self::checkDir('檢查 Migration 目錄', PATH_MIGRATION);
      
      $inited || @fileWrite(PATH_FILE . '.inited', date('Y-m-d H:i:s'));

      print(QUIET ? json_encode(['status' => true]) : "\n專案初始完成！\n\n");
    }
  }
}
