<?php

namespace _M;

class Connection extends \PDO {
  private static $instance = null;
  private static $pdoOptions = [
    \PDO::ATTR_CASE              => \PDO::CASE_NATURAL,
    \PDO::ATTR_ERRMODE           => \PDO::ERRMODE_EXCEPTION,
    \PDO::ATTR_ORACLE_NULLS      => \PDO::NULL_NATURAL,
    \PDO::ATTR_STRINGIFY_FETCHES => false
  ];

  public static function instance() {
    if (self::$instance)
      return self::$instance;

    try {
      return self::$instance = new Connection();
    } catch (\PDOException $e) {
      new \GG('PDO 連線錯誤！', 500, [
        'msgs' => ['PDO 連線錯誤！', '請檢查 Database Config 設定值。'],
        'traces' => array_map(function($trace) {
          return [
            'path' => $trace['file']      ?? '[呼叫函式]',
            'line' => $trace['line']      ?? null,
            'info' => ($trace['class']    ?? '')
                    . ($trace['type']     ?? '')
                    . ($trace['function'] ?? '')
                    . (isset($trace['args']) ? '(' . implodeRecursive(', ', $trace['args']) . ')' : '')
          ];
        }, $e->getTrace())]);
    }
  }
  
  public function __construct() {
    $config = \config('Database');

    foreach (['hostname', 'username', 'password', 'database', 'encoding'] as $key)
      isset($config[$key]) || \gg('MySQL 連線資訊缺少「' . $key . '」！');

    parent::__construct('mysql:host=' . $config['hostname'] . ';dbname=' . $config['database'], $config['username'], $config['password'], self::$pdoOptions);
    $this->setEncoding($config['encoding']);
  }

  public function setEncoding($encoding) {
    $error = $this->query('SET NAMES ?', [$encoding]);
    $error && \gg('設定編碼格式「' . $encoding . '」失敗！', '錯誤原因：' . $error);
    return $this;
  }

  public function query($sql, $vals = [], &$sth = null, &$error = null, $fetchModel = \PDO::FETCH_ASSOC) {
    try {
      if (!$sth = $this->prepare((string)$sql))
        return '執行 Connection prepare 失敗！';

      $sth->setFetchMode($fetchModel);
      
      $start = \microtime(true);
      $status = $sth->execute($vals);

      \Log::query($sql, $vals, $status, \number_format((\microtime(true) - $start) * 1000, 1));

      if (!$status)
        return '執行 Connection execute 失敗！';

      return null;
    } catch (\PDOException $e) {
      return $e->getMessage();
    }
  }

  public static function close() {
    self::$instance = null;
    return true;
  }
}
