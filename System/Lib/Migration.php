<?php

class Migration {
  private static $obj = null;
  private static $gets = [];

  private static function createSql($modelName) {
    if (!$dbConfig = config('Database'))
      return false;

    if (!isset($dbConfig['encoding']))
      return false;
    
    $dbcollat = $dbConfig['encoding'] ? $dbConfig['encoding'] . '_unicode_ci' : 'utf8mb4_unicode_ci';
    
    return !\_M\Connection::instance()->query("CREATE TABLE `" . $modelName . "` ("
            . "`id` int(11) unsigned NOT NULL AUTO_INCREMENT,"
            . "`version` varchar(5) COLLATE " . $dbcollat . " NOT NULL DEFAULT '0' COMMENT '版本',"
            . "`updateAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',"
            . "`createAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '新增時間',"
            . "PRIMARY KEY (`id`)"
          . ") ENGINE=InnoDB DEFAULT CHARSET=" . $dbConfig['encoding'] . " COLLATE=" . $dbcollat . ";");
  }

  public static function init() {
    $modelName = config('Migration', 'modelName');

    if (!class_exists($modelName))
      eval("class " . $modelName . " extends M\Model {}");

    $sth = null;
    if ($error = \_M\Connection::instance()->query('SHOW TABLES LIKE ?;', [$modelName], $sth))
      gg('連接 MySQL 失敗！', $error);

    $obj = $sth->fetch(PDO::FETCH_NUM);

    is_array($obj) && $obj = array_shift($obj);
    if ($obj !== $modelName && !self::createSql($modelName))
      gg('Migration 錯誤，產生 ' . $modelName . ' 資料表失敗！');

    self::$obj = $modelName::first();
    self::$obj || self::$obj = $modelName::create(['version' => '0']);
    self::$obj || gg('Migration 初始化失敗！');
  }

  public static function files() {
    
    $files = array_values(array_filter(array_map(function($file) {

      $path = PATH_MIGRATION . $file;
      if (!is_readable($path))
        return null;

      $ext = pathinfo($file, PATHINFO_EXTENSION);
      if ($ext != 'php')
        return null;

      $format = config('Migration', 'fileFormat');
      if (!$format)
        return null;

      if (!preg_match_all($format, $file, $matches))
        return null;

      if (!($matches['vers'] && $matches['name']))
        return null;

      $data = include($path);
      
      if (!isset($data['up'], $data['at'], $data['down']))
        return null;

      return [
        'ver' => (int) array_shift($matches['vers']),
        'name' => array_shift($matches['name']),
        'path' => $path,
        'ups' => is_array($data['up']) ? $data['up'] : [$data['up']],
        'downs' => is_array($data['down']) ? $data['down'] : [$data['down']],
        'at' => $data['at'],
      ];
    }, scandir(PATH_MIGRATION) ?: [])));

    $files = array_combine(array_column($files, 'ver'), $files);
    ksort($files);

    return $files;
  }

  public static function nowVersion() {
    return $now = (int)self::$obj->version;
  }

  private static function run($tmps, $isUp, $to, &$error = null) {
    $last = !$isUp && $to ? array_pop($tmps) : null;

    foreach ($tmps as $file) {
      foreach ($file[$isUp ? 'ups' : 'downs'] as $sql)
        if ($sql)
          if ($error = \_M\Connection::instance()->query($sql, [])) {
            $error = [
              '檔案位置' => $file['path'],
              '錯誤原因' => $error,
              'SQL 語法' => $sql,
            ];
            return false;
          }

      self::$obj->version = $file['ver'];
      
      if (!self::$obj->save())
        return false;
    }

    if ($isUp)
      return true;

    $version = $last ? $last['ver'] : 0;

    self::$obj->version = $version;
    self::$obj->save();

    return true;
  }

  public static function to($to = null) {
    $now = self::nowVersion();
    $files = self::files();

    $tmps = array_keys($files);
    $to !== null || $to = end($tmps);

    if ($to == $now)
      return null;

    $tmps = [];

    if ($isUp = $to > $now)
      foreach ($files as $version => $file)
        $version > $now && $version <= $to && array_push($tmps, $file);
    else
      foreach ($files as $version => $file)
        $version <= $now && $version >= $to && array_unshift($tmps, $file);

    $error = null;
    if (!self::run($tmps, $isUp, $to, $error))
      return $error;

    return null;
  }
}

Migration::init();