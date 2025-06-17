<?php

namespace Cmd;

use \Cmd\Result\Group;
use \Cmd\Result\Group\Step;

final class Migration {
  private static $_migration = null;

  public static function version(): int {
    return (int) self::_getMigration()->version;
  }
  public static function refresh(): void {
    $migration = self::_getMigration();

    Group::create('執行 Migration', static function () use ($migration): void {
      ['todos' => $todos, 'isDown' => $isDown] = self::_getToDos(0);
      self::_runTodos($migration, $isDown < 0 ? '調降' : '更新', $todos);

      ['todos' => $todos, 'isDown' => $isDown] = self::_getToDos(null);
      self::_runTodos($migration, $isDown < 0 ? '調降' : '更新', $todos);
    });

    Group::create('完成', fn() => Step::create('目前版本', $migration->version));
  }
  public static function execute(?int $version = null): void {
    $migration = self::_getMigration();

    Group::create('執行 Migration', static function () use ($migration, $version) {
      ['todos' => $todos, 'isDown' => $isDown] = self::_getToDos($version);
      self::_runTodos($migration, $isDown < 0 ? '調降' : '更新', $todos);
    });

    Group::create('完成', fn() => Step::create('目前版本', $migration->version));
  }
  public static function files(): array {
    $format = \Config::get('Migration', 'fileFormat');

    $files = [];
    $_files = @scandir(PATH_MIGRATION) ?: [];

    foreach ($_files as $file) {
      $ext = pathinfo($file, PATHINFO_EXTENSION);
      if ($ext != 'php' || !is_readable($path = PATH_MIGRATION . $file) || $format === null || !preg_match_all($format, $file, $matches) || !($matches['vers'] && $matches['name'])) {
        continue;
      }

      $data = include($path);

      if (!isset($data['up'], $data['at'], $data['down'])) {
        continue;
      }

      $files[] = [
        'version' => (int) array_shift($matches['vers']),
        'name' => array_shift($matches['name']),
        'at' => $data['at'],
        'up' => $data['up'],
        'down' => $data['down'],
      ];
    }

    usort($files, fn($a, $b) => $a['version'] - $b['version']);

    return $files;
  }
  public static function getLatestVersion(): int {
    $files = self::files();
    return $files ? $files[count($files) - 1]['version'] : 0;
  }

  private static function _executeQuery(string $sql, array $params = []): \PDOStatement {
    $stmt = null;
    $error = \Orm\Core\Connection::instance()->runQuery($sql, $params, $stmt);
    if ($error instanceof \Exception) {
      throw $error;
    }
    return $stmt;
  }
  private static function _checkHasTable(string $name): bool {
    $obj = self::_executeQuery('SHOW TABLES LIKE ?;', [$name])->fetch(\PDO::FETCH_NUM);
    if (is_array($obj)) {
      $obj = array_shift($obj);
    }
    return $obj === $name;
  }
  private static function _getMigration() {
    if (self::$_migration !== null) {
      return self::$_migration;
    }

    [
      'migration' => $migration,
      'modelName' => $modelName,
      'className' => $className,
    ] = Group::create('初始 Migration', static function (): array {
      $modelName = \Config::get('Migration', 'modelName');
      $className = '\App\Model\\' . $modelName;

      if (!self::_checkHasTable($modelName)) {
        Step::create('建立 Table', static function () use ($modelName): void {
          self::_executeQuery("CREATE TABLE `" . $modelName . "` ("
            . "`id` int(11) unsigned NOT NULL AUTO_INCREMENT,"
            . "`version` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '版本',"
            . "`updateAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',"
            . "`createAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '新增時間',"
            . "PRIMARY KEY (`id`)"
            . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

          if (!self::_checkHasTable($modelName)) {
            throw new \Exception('產生 ' . $modelName . ' 資料表失敗');
          }
        });
      }

      if (!class_exists($className)) {
        Step::create('建立 Model', static function () use ($modelName, $className): void {
          eval('namespace App\Model; class ' . $modelName . ' extends \Orm\Model {}');

          if (!class_exists($className)) {
            throw new \Exception('建立 Migration Model 失敗');
          }
        });
      }

      $migration = Step::create('取得 Data', static function () use ($className) {
        $migration = $className::one() ?? $className::create(['version' => 0]);

        if (!$migration) {
          throw new \Exception('取得 Migration Data 失敗');
        }

        return $migration;
      });

      return [
        'migration' => $migration,
        'modelName' => $modelName,
        'className' => $className,
      ];
    });

    self::$_migration = $migration;

    return $migration;
  }
  private static function _getToDos(?int $version = null): array {
    $now = self::version();
    $files = self::files();
    $goal = $files ? $files[count($files) - 1]['version'] : 0;

    if ($version !== null) {
      $goal = $version;
    }

    $todos = [];

    if ($goal > $now) {
      foreach ($files as $file) {
        if ($file['version'] > $now && $file['version'] <= $goal) {
          $todos[] = [
            'do' => $file['up'],
            'version' => $file['version'],
            'name' => $file['name'],
            // 'at' => $file['at'],
            // 'up' => $file['up'],
            // 'down' => $file['down'],
          ];
        }
      }

      return [
        'todos' => $todos,
        'isDown' => 0,
      ];
    }

    if ($goal < $now) {
      foreach ($files as $file) {
        if ($file['version'] <= $now && $file['version'] > $goal) {
          $todos[] = [
            'do' => $file['down'],
            'version' => $file['version'] - 1,
            'name' => $file['name'],
            // 'at' => $file['at'],
            // 'up' => $file['up'],
            // 'down' => $file['down'],
          ];
        }
      }

      return [
        'todos' => array_reverse($todos),
        'isDown' => -1,
      ];
    }

    return [
      'todos' => $todos,
      'isDown' => 0,
    ];
  }
  private static function _runTodos($migration, string $title, array $todos): void {
    foreach ($todos as $todo) {
      ['do' => $do, 'name' => $name, 'version' => $version] = $todo;
      $sqls = is_array($do) ? $do : [$do];

      Step::create($title . '至第 ' . $version . ' 版', static function () use ($sqls, $migration, $name): void {
        foreach ($sqls as $sql) {
          try {
            self::_executeQuery($sql);
          } catch (\Exception $e) {
            $migration->save();

            throw new \Exception(implode("\n", [
              '目前版本：' . $migration->version,
              '檔案名稱：' . $name,
              '錯誤原因：' . $e->getMessage(),
              '執行語法：' . $sql
            ]));
          }
        }
      });
      $migration->version = $version;
    }
    $migration->save();
  }
}
