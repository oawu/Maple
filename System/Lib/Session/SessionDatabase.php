<?php

class SessionDatabase extends Session implements SessionHandlerInterface {
  private $modelName = null;
  private $rowExists = false;

  public function __construct() {
    parent::__construct();

    $modelName = config('Session', 'SessionDatabase', 'modelName');
    $modelName || gg('SessionDatabase 錯誤，請在 Config 設定 modelName');

    ini_set('session.save_path', $modelName);
  }

  private function createSql($modelName) {
    if (!$dbConfig = config('Database'))
      return false;

    if (!isset($dbConfig['encoding']))
      return false;
    
    $dbcollat = $dbConfig['encoding'] ? $dbConfig['encoding'] . '_unicode_ci' : 'utf8mb4_unicode_ci';

    return !\_M\Connection::instance()->query("CREATE TABLE `" . $modelName . "` ("
      . "`id` int(11) unsigned NOT NULL AUTO_INCREMENT,"
      . "`sessionId` varchar(128) COLLATE " . $dbcollat . " NOT NULL DEFAULT '' COMMENT 'Session ID',"
      . "`ipAddress` varchar(45) COLLATE " . $dbcollat . " NOT NULL DEFAULT '' COMMENT 'IP',"
      . "`timestamp` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Timestamp',"
      . "`data` blob NOT NULL COMMENT 'Data',"
      . "PRIMARY KEY (`id`),"
      . (Session::matchIp() ? "KEY `ipAddress_sessionId_index` (`ipAddress`,`sessionId`)" : "KEY `sessionId_index` (`sessionId`)")
    . ") ENGINE=InnoDB DEFAULT CHARSET=" . $dbConfig['encoding'] . " COLLATE=" . $dbcollat . ";");
  }

  public function open($modelName, $name) {
    if ($this->modelName !== null)
      return $this->succ();

    if (!$modelName)
      return $this->fail();

    if (!class_exists($modelName))
      eval("class " . $modelName . " extends M\Model {}");

    if (!class_exists($modelName))
      return $this->fail();

    $sth = null;
    if ($error = \_M\Connection::instance()->query('SHOW TABLES LIKE ?;', [$modelName], $sth))
      return $this->fail();

    $obj = $sth->fetch(PDO::FETCH_NUM);

    is_array($obj) && $obj = array_shift($obj);

    if ($obj !== $modelName && !$this->createSql($modelName))
      return $this->fail();

    $this->modelName = $modelName;
    return $this->succ();
  }

  public function read($sessionId) {
    $modelName = $this->modelName;

    if ($this->getLock($sessionId) !== false) {
      $this->sessionId = $sessionId;

      if (!$obj = $modelName::first(['select' => 'data', 'where' => $this->where($sessionId)])) {
        $this->rowExists = false;
        $this->fingerPrint = md5('');
        return '';
      }

      $result = $obj->data;
      $this->fingerPrint = md5($result);
      $this->rowExists = true;
      return $result;
    }
    
    $this->fingerPrint = md5('');
    return '';
  }

  public function write($sessionId, $sessionData) {
    $modelName = $this->modelName;

    if ($sessionId !== $this->sessionId) {
      if (!$this->releaseLock() || !$this->getLock($sessionId))
        return $this->fail();

      $this->rowExists = false;
      $this->sessionId = $sessionId;
    } else if ($this->lock === false) {
      return $this->fail();
    }

    if ($this->rowExists === false) {
      if ($modelName::create(['sessionId' => $sessionId, 'ipAddress' => Input::ip(), 'timestamp' => time(), 'data' => $sessionData])) {
        $this->fingerPrint = md5($sessionData);
        $this->rowExists = true;
        return $this->succ();
      }
      return $this->fail();
    }

    if (!$obj = $modelName::first(['select' => 'id, data, timestamp', 'where' => $this->where($sessionId)]))
      return $this->fail();

    $obj->timestamp = time();

    if ($this->fingerPrint !== md5($sessionData))
      $obj->data = $sessionData;

    if ($obj->save()) {
      $this->fingerPrint = md5($sessionData);
      return $this->succ();
    }

    return $this->fail();
  }

  public function close() {
    return ($this->lock && !$this->releaseLock()) ? $this->fail() : $this->succ();
  }

  public function destroy($sessionId) {
    $modelName = $this->modelName;

    if ($this->lock) {
      $obj = $modelName::first(['select' => 'id, data, timestamp', 'where' => $this->where($sessionId)]);

      if ($obj && !$obj->delete())
        return $this->fail();
    }

    if ($this->close() === $this->succ()) {
      $this->cookieDestroy();
      return $this->succ();
    }

    return $this->fail();
  }

  public function gc($maxLifeTime) {
    $modelName = $this->modelName;
    return $modelName::deleteAll(['where' => ['timestamp < ?', time() - $maxLifeTime]]) ? $this->succ() : $this->fail();
  }

  protected function getLock($sessionId) {
    $arg = md5($sessionId . (Session::matchIp() ? '_' . Input::ip() : ''));

    if ($error = \_M\Connection::instance()->query('SELECT GET_LOCK("' . $arg . '", 300) AS session_lock', [], $sth))
      return false;

    $obj = $sth->fetch();

    if (!($obj && $obj['session_lock']))
      return false;

    $this->lock = $arg;
    return true;
  }

  protected function releaseLock() {
    if (!$this->lock)
      return true;

    if ($error = \_M\Connection::instance()->query('SELECT RELEASE_LOCK("' . $this->lock . '") AS session_lock', [], $sth))
      return false;

    $obj = $sth->fetch();

    if (!($obj && $obj['session_lock']))
      return false;

    $this->lock = false;
    return true;
  }

  private function where($sessionId) {
    return Session::matchIp() ? ['sessionId = ? AND ipAddress = ?', $sessionId, Input::ip()] : ['sessionId = ?', $sessionId];
  }
}
