<?php

namespace CMD {
  use function \config;

  class FeatureMigration {
    private static $obj = null;
    private static $connection = null;
    private static $modelName = null;
    private static $className = null;

    private static function genConnection() {
      Display::title('建立資料庫連線');
      $dbConfig = config('Database');

      try {
        $config = new \M\Core\Config();
        self::$connection = new \M\Core\Connection($config->hostname($dbConfig['hostname'])->username($dbConfig['username'])->password($dbConfig['password'])->encoding($dbConfig['encoding'])->database($dbConfig['database']));
      } catch (\PDOException $e) {
        Display::failure('PDO 連線錯誤！' . $e->getMessage());
      }
      Display::success(true, '完成');
    }

    private static function genModel() {
      Display::title('建立 Migration Model');
      self::$modelName = config('Migration', 'modelName');
      self::$className = '\m\\' . self::$modelName;
      class_exists(self::$modelName) || eval("namespace M; class " . self::$modelName . " extends Model {}");
      class_exists(self::$className) ? Display::success(true, '完成') : Display::failure('無法建立 Migration Model！');
    }

    private static function hasTable(&$error = null) {
      $sth = null;
      if ($error = self::$connection->query('SHOW TABLES LIKE ?;', [self::$modelName], $sth)) return false;
      $obj = $sth->fetch(\PDO::FETCH_NUM);
      is_array($obj) && $obj = array_shift($obj);
      return $obj === self::$modelName;
    }

    private static function genTable() {
      Display::title('檢查 Migration Table');

      $result = self::hasTable($error);
      $error === null || Display::failure($error);
      if ($result) return Display::success();
      else QUIET || print("不存在\n");

      return self::createSQL();
    }

    private static function createSQL() {
      Display::title('建立 Migration Table');

      $dbConfig = config('Database');
      $dbCollat = $dbConfig['encoding'] ? $dbConfig['encoding'] . '_unicode_ci' : 'utf8mb4_unicode_ci';

      $error = self::$connection->query("CREATE TABLE `" . self::$modelName . "` ("
        . "`id` int(11) unsigned NOT NULL AUTO_INCREMENT,"
        . "`version` varchar(5) COLLATE " . $dbCollat . " NOT NULL DEFAULT '0' COMMENT '版本',"
        . "`updateAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',"
        . "`createAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '新增時間',"
        . "PRIMARY KEY (`id`)"
      . ") ENGINE=InnoDB DEFAULT CHARSET=" . $dbConfig['encoding'] . " COLLATE=" . $dbCollat . ";");
      
      $error === null || Display::failure($error);
      $result = self::hasTable($error);

      return $error === null
        ? $result
          ? Display::success()
          : Display::failure('Migration 錯誤，產生 ' . self::$modelName . ' 資料表失敗！')
        : Display::failure($error);
    }

    private static function genObject() {
      Display::title('建立 Model 物件');
      $className = self::$className;
      self::$obj = $className::first();
      self::$obj || self::$obj = $className::create(['version' => '0']);
      return self::$obj ? Display::success() : \failure('Migration 初始化失敗！');
    }

    private static function init() {
      Display::main('初始 Migration 工具');
      self::genConnection();
      self::genModel();
      self::genTable();
      self::genObject();
    }

    private static function files () {
      $format = config('Migration', 'fileFormat');

      $files = array_values(array_filter(array_map(function($file) use ($format) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);

        if (!is_readable($path = PATH_MIGRATION . $file) || $ext != 'php' || $format === null || !preg_match_all($format, $file, $matches) || !($matches['vers'] && $matches['name']))
          return null;

        $data = include($path);
        
        if (!isset($data['up'], $data['at'], $data['down']))
          return null;

        return [
          'version'   => (int) array_shift($matches['vers']),
          'name'  => array_shift($matches['name']),
          'path'  => $path,
          'ups'   => is_array($data['up']) ? $data['up'] : [$data['up']],
          'downs' => is_array($data['down']) ? $data['down'] : [$data['down']],
          'at'    => $data['at'],
        ];
      }, scandir(PATH_MIGRATION) ?: [])));

      return $files;
    }

    private static function todos($files, $now, $goal) {
      $todos = [];

      if ($now < $goal) {
        usort($files, function($a, $b) { return $a['version'] - $b['version']; });
        foreach ($files as $file)
          $now < $file['version'] && $file['version'] <= $goal && array_push($todos, ['sqls' => $file['ups'], 'name' => $file['name'], 'version' => $file['version'], 'goal' => $file['version'], 'path' => $file['path'], 'isUp' => true]);
      }
      
      if ($now > $goal) {
        usort($files, function($a, $b) { return $b['version'] - $a['version']; });
        
        foreach ($files as $file)
          $now >= $file['version'] && $file['version'] > $goal && array_push($todos, ['sqls' => $file['downs'], 'name' => $file['name'], 'version' => $file['version'], 'goal' => $goal, 'isUp' => false]);

        for ($i = 0, $l = count($todos); $i < $l - 1; $i++)
          $todos[$i]['goal'] = $todos[$i + 1]['version'];
      }

      return $todos;
    }

    private static function run($todos) {
      Display::main('執行 Migration');

      foreach ($todos as $todo) {
        Display::title(($todo['isUp'] ? '更新至' : '調降至') . ' 第 ' . $todo['goal'] . ' 版');

        foreach ($todo['sqls'] as $sql)
          $sql && ($error = self::$connection->query($sql, [], $tmp, \PDO::FETCH_ASSOC, false)) && Display::failure([
            '檔案位置：' . $todo['path'],
            '錯誤原因：' . $error,
            'SQL 語法：' . $sql]);

        self::$obj->version = $todo['goal'];
        self::$obj->save() ? Display::success() : Display::failure('更新版本號碼失敗！');
      }
    }

    public static function refresh($top) {
      QUIET || print("\n執行 Migration --refresh\n");
      self::init();
      self::run(array_merge(self::todos(self::files(), 0 + self::$obj->version, 0), self::todos(self::files(), 0, $top)));
      Display::main('完成更新');
      return print(QUIET
        ? json_encode(['status' => true, 'version' => self::$obj->version])
        : Display::list('目前版本 ─ ' . self::$obj->version));
    }

    public static function version($version) {
      QUIET || print("\n執行 Migration\n");
      self::init();
      self::run(self::todos(self::files(), 0 + self::$obj->version, $version));
      Display::main('完成更新');
      return print(QUIET
        ? json_encode(['status' => true, 'version' => self::$obj->version])
        : Display::list('目前版本 ─ ' . self::$obj->version));
    }
  }
}
