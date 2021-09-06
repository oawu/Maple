<?php

namespace Session {
  final class File extends \Session implements \SessionHandlerInterface {
    const PERMISSIONS = 0600;

    private $path;
    private $handle;
    private $fileNew;

    public function __construct() {
      $this->path = null;
      $this->handle = null;
      $this->fileNew = true;

      $path = \config('Session', 'driver', 'params', 'path');
      $path || \GG('\Session\File 錯誤，存放 Session 目錄不存在！');
      ini_set('session.save_path', $path);
    }

    public function open($path, $name) {
      if ($this->path)
        return $this->succ();

      if (!isset($path) && is_dir($path) && is_writable($path))
        return $this->fail();

      $this->path = $path . $name . '_' . (self::matchIp() ? \Request::ip() . '_' : '');

      return $this->succ();
    }

    public function read($sessionId) {
      if ($this->handle === null) {

        $this->fileNew = !file_exists($this->path . $sessionId);
        $this->handle = fopen($this->path . $sessionId, 'c+b');
        
        if ($this->handle === false)
          return $this->fail();

        if (flock($this->handle, LOCK_EX) === false) {
          fclose($this->handle);
          $this->handle = null;
          return $this->fail();
        }

        $this->sessionId = $sessionId;

        if ($this->fileNew) {
          chmod($this->path . $sessionId, self::PERMISSIONS);
          $this->fingerPrint = md5('');
          return '';
        }
      } else if ($this->handle === false) {
        return $this->fail();
      } else {
        rewind($this->handle);
      }

      $data = '';
      for ($read = 0, $length = filesize($this->path . $sessionId); $read < $length; $read += charsetStrlen($buffer)) {
        if (($buffer = fread($this->handle, $length - $read)) === false)
          break;

        $data .= $buffer;
      }

      $this->fingerPrint = md5($data);
      return $data;
    }

    public function write($sessionId, $sessionData) {
      if ($sessionId !== $this->sessionId && ($this->close() === $this->fail() || $this->read($sessionId) === $this->fail()))
        return $this->fail();

      if (!is_resource($this->handle))
        return $this->fail();

      if ($this->fingerPrint === md5($sessionData))
        return !$this->fileNew && !touch($this->path . $sessionId) ? $this->fail() : $this->succ();

      if (!$this->fileNew) {
        ftruncate($this->handle, 0);
        rewind($this->handle);
      }

      if (($length = strlen($sessionData)) > 0) {
        for ($written = 0; $written < $length; $written += $result)
          if (($result = fwrite($this->handle, substr($sessionData, $written))) === false)
            break;

        if (!is_int($result)) {
          $this->fingerPrint = md5(substr($sessionData, 0, $written));
          return $this->fail();
        }
      }

      $this->fingerPrint = md5($sessionData);
      return $this->succ();
    }

    public function close() {
      if (is_resource($this->handle)) {
        flock($this->handle, LOCK_UN);
        fclose($this->handle);
        $this->handle = $this->fileNew = $this->sessionId = null;
      }

      return $this->succ();
    }

    public function destroy($sessionId) {
      if ($this->close() === $this->succ()) {
        if (file_exists($this->path . $sessionId)) {
          $this->cookieDestroy();

          return unlink($this->path . $sessionId) ? $this->succ() : $this->fail();
        }

        return $this->succ();
      }

      if ($this->path !== null) {
        clearstatcache();

        if (file_exists($this->path . $sessionId)) {
          $this->cookieDestroy();
          return unlink($this->path . $sessionId) ? $this->succ() : $this->fail();
        }

        return $this->succ();
      }

      return $this->fail();
    }

    public function gc($maxLifeTime) {
      if (!is_dir ($this->path) || ($directory = opendir($this->path)) === false)
        return $this->fail();

      $ts = time() - $maxLifeTime;

      $pattern = (self::matchIp() === true) ? '[0-9a-f]{32}' : '';
      $pattern = sprintf('#\A%s' . $pattern . self::sessionIdRegexp() . '\z#', preg_quote(self::cookieName()));

      while (($file = readdir($directory)) !== false)
        if (preg_match($pattern, $file) && is_file($this->path . DIRECTORY_SEPARATOR . $file) && ($mtime = filemtime($this->path . DIRECTORY_SEPARATOR . $file)) !== false && $mtime <= $ts)
          unlink($this->path . DIRECTORY_SEPARATOR . $file);

      closedir($directory);
      return $this->succ();
    }
  }

  final class Database extends \Session implements \SessionHandlerInterface {
    private $modelName = null;
    private $rowExists = false;

    public function __construct() {
      $modelName = \config('Session', 'driver', 'params', 'modelName');
      $modelName
        ? ini_set('session.save_path', $modelName)
        : \GG('\Session\Database 錯誤，請在 Config 設定 modelName');
    }

    private function createSql($modelName) {
      
      if (!$config = \config('Database'))
        return false;

      if (!isset($config['encoding']))
        return false;
      
      $collat = $config['encoding'] ? $config['encoding'] . '_unicode_ci' : 'utf8mb4_unicode_ci';

      return !\M\Core\Connection::instance()->query("CREATE TABLE `" . $modelName . "` ("
        . "`id` int(11) unsigned NOT NULL AUTO_INCREMENT,"
        . "`sessionId` varchar(128) COLLATE " . $collat . " NOT NULL DEFAULT '' COMMENT 'Session ID',"
        . "`ipAddress` varchar(45) COLLATE " . $collat . " NOT NULL DEFAULT '' COMMENT 'IP',"
        . "`timestamp` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Timestamp',"
        . "`data` blob NOT NULL COMMENT 'Data',"
        . "PRIMARY KEY (`id`),"
        . (self::matchIp() ? "KEY `ipAddress_sessionId_index` (`ipAddress`,`sessionId`)" : "KEY `sessionId_index` (`sessionId`)")
      . ") ENGINE=InnoDB DEFAULT CHARSET=" . $config['encoding'] . " COLLATE=" . $collat . ";");
    }

    public function open($modelName, $name) {
      if ($this->modelName !== null)
        return $this->succ();

      if (!$modelName)
        return $this->fail();

      class_exists($modelName) || eval("namespace M; class " . $modelName . " extends Model {}");
      $className = '\M\\' . $modelName;

      if (!class_exists($className))
        return $this->fail();

      $sth = null;
      if ($error = \M\Core\Connection::instance()->query('SHOW TABLES LIKE ?;', [$modelName], $sth))
        return $this->fail();

      $obj = $sth->fetch(\PDO::FETCH_NUM);

      is_array($obj) && $obj = array_shift($obj);

      if ($obj !== $modelName && !$this->createSql($modelName))
        return $this->fail();

      $this->modelName = $modelName;
      return $this->succ();
    }

    public function read($sessionId) {
      $modelName = $this->modelName;
      $className = '\M\\' . $this->modelName;

      if ($this->getLock($sessionId) !== false) {
        $this->sessionId = $sessionId;

        if (!$obj = $className::first(['select' => 'data', 'where' => $this->where($sessionId)])) {
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
      $className = '\M\\' . $this->modelName;

      if ($sessionId !== $this->sessionId) {
        if (!$this->releaseLock() || !$this->getLock($sessionId))
          return $this->fail();

        $this->rowExists = false;
        $this->sessionId = $sessionId;
      } else if ($this->lock === false) {
        return $this->fail();
      }

      if ($this->rowExists === false) {
        if ($className::create(['sessionId' => $sessionId, 'ipAddress' => \Request::ip(), 'timestamp' => time(), 'data' => $sessionData])) {
          $this->fingerPrint = md5($sessionData);
          $this->rowExists = true;
          return $this->succ();
        }
        return $this->fail();
      }

      if (!$obj = $className::first(['select' => 'id, data, timestamp', 'where' => $this->where($sessionId)]))
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
      return $this->lock && !$this->releaseLock()
        ? $this->fail()
        : $this->succ();
    }

    public function destroy($sessionId) {
      $modelName = $this->modelName;
      $className = '\M\\' . $this->modelName;

      if ($this->lock) {
        $obj = $className::first(['select' => 'id, data, timestamp', 'where' => $this->where($sessionId)]);

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
      $className = '\M\\' . $this->modelName;
      return $className::deleteAll(['where' => ['timestamp < ?', time() - $maxLifeTime]]) ? $this->succ() : $this->fail();
    }

    protected function getLock($sessionId) {
      $arg = md5($sessionId . (self::matchIp() ? '_' . \Request::ip() : ''));

      if ($error = \M\Core\Connection::instance()->query('SELECT GET_LOCK("' . $arg . '", 300) AS session_lock', [], $sth))
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

      if ($error = \M\Core\Connection::instance()->query('SELECT RELEASE_LOCK("' . $this->lock . '") AS session_lock', [], $sth))
        return false;

      $obj = $sth->fetch();

      if (!($obj && $obj['session_lock']))
        return false;

      $this->lock = false;
      return true;
    }

    private function where($sessionId) {
      return self::matchIp() ? ['sessionId = ? AND ipAddress = ?', $sessionId, \Request::ip()] : ['sessionId = ?', $sessionId];
    }
  }

  final class Redis extends \Session implements \SessionHandlerInterface {
    private $redis = null; 
    private $lockKey = null; 
    private $keyExists = false; 
    
    private $host = null; 
    private $port = null; 
    private $timeout = null; 
    private $password = null; 
    private $database = null; 
    private $prefix = 'maple_session:'; 

    public function __construct() {
      extension_loaded('redis') || GG('載入 \Session\Redis 失敗，無 Redis 函式！');

      $options = config('Session', 'driver', 'params');
      $options && is_array($options) || GG('\Session\Redis 錯誤，Config 錯誤！');

      foreach ($options as $key => $option)
        $this->$key = $option;

      $this->prefix = (empty($options['prefix']) ? $this->prefix : $options['prefix']) . (self::matchIp() ? \Request::ip() . ':' : '');
      // ini_set('session.save_path', 'a');
    }

    public function open($path, $name) {
      if ($this->redis !== null)
        return $this->succ();

      $this->redis = new \Redis();

      if (!$this->redis->connect($this->host, $this->port, $this->timeout))
        return $this->fail();

      if ($this->password && !$this->redis->auth($this->password))
        return $this->fail();

      if ($this->database && !$this->redis->select($this->database))
        return $this->fail();
      return $this->succ();
    }

    public function read($sessionId) {
      if ($this->redis && $this->getLock($sessionId)) {
        $this->sessionId = $sessionId;
        $data = $this->redis->get($this->prefix . $sessionId);

        is_string($data) ? $this->keyExists = true : $data = '';
        $this->fingerPrint = md5($data);
        return $data;
      }

      return $this->fail();
    }

    public function write($sessionId, $sessionData) {
      if (!($this->redis && $this->lockKey))
        return $this->fail();

      if ($sessionId !== $this->sessionId) {
        if (!($this->releaseLock() && $this->getLock($sessionId)))
          return $this->fail();

        $this->keyExists = false;
        $this->sessionId = $sessionId;
      }

      $this->redis->expire($this->lockKey, 300);

      if ($this->fingerPrint !== ($fingerPrint = md5($sessionData)) || $this->keyExists === false) {
        if ($this->redis->set($this->prefix . $sessionId, $sessionData, self::expiration())) {
          $this->fingerPrint = $fingerPrint;
          $this->keyExists = true;
          return $this->succ();
        }

        return $this->fail();
      }
      return $this->redis->expire($this->prefix . $sessionId, self::expiration()) ? $this->succ() : $this->fail();
    }

    public function close() {
      if ($this->redis === null)
        return $this->succ();
      

      try {
        if ($this->redis->ping() === '+PONG' || $this->redis->ping() === true) {
          $this->releaseLock();

          if ($this->redis->close() === false)
            return $this->fail();
        }

      } catch (RedisException $e) {
        \Log::error('Session 錯誤！', '\Session\Redis->close() 時錯誤！', '錯誤訊息：' . $e->getMessage());
      }

      $this->redis = null;

      return $this->succ();
    }

    public function destroy($sessionId) {
      if (!($this->redis && $this->lockKey))
        return $this->fail();

      $this->redis->delete($this->prefix . $sessionId);
      $this->cookieDestroy();
      return $this->succ();
    }

    public function gc($maxLifeTime) {
      return $this->succ();
    }

    protected function getLock($sessionId) {
      if ($this->lockKey === $this->prefix . $sessionId . ':lock')
        return $this->redis->expire($this->lockKey, 300);
      $attempt = 0;
      $lockKey = $this->prefix . $sessionId . ':lock';
      do {
        $ttl = $this->redis->ttl($lockKey);
        if ($ttl > 0) {
          sleep(1);
          continue;
        }
        if (!$result = $ttl === -2 ? $this->redis->set($lockKey, time(), ['nx', 'ex' => 300]) : $this->redis->setex($lockKey, 300, time()))
          return false;

        $this->lockKey = $lockKey;

        break;
      } while (++$attempt < 5);

      if ($attempt === 30)
        return false;

      return $this->lock = true;
    }

    protected function releaseLock() {
      if ($this->redis && $this->lockKey && $this->lock) {
        if (!$this->redis->del($this->lockKey))
          return false;

        $this->lockKey = null;
        $this->lock = false;
      }

      return true;
    }
    
    public function __destruct() {
      $this->redis && $this->redis->close() && $this->redis = null;
    }
  }
}

namespace {
  if (!interface_exists('SessionHandlerInterface', false)) {
    interface SessionHandlerInterface {
      public function open($savePath, $name);
      public function close();
      public function read($sessionId);
      public function write($sessionId, $sessionData);
      public function destroy($sessionId);
      public function gc($maxlifetime);
    }
  }

  abstract class Session {
    protected $sessionId = null;
    protected $lock = false;
    protected $fingerPrint = '';

    protected function getLock($sessionId) {
      return $this->lock = true;
    }
    protected function releaseLock() {
      return !($this->lock && $this->lock = false);
    }
    protected function cookieDestroy() {
      return setcookie(self::$cookie['name'], null, 1, self::$cookie['path'], self::$cookie['domain'], self::$cookie['secure'], true);
    }
    protected function succ() {
      return true;
    }
    protected function fail() {
      return false;
    }

    private static $matchIp;
    private static $expiration;
    private static $time2Update;
    private static $regenerateDestroy;

    private static $lastRegenerateKey;
    private static $varsKey;
    private static $cookie;
    private static $sessionIdRegexp;

    protected static function expiration() {
      return self::$expiration;
    }

    protected static function matchIp() {
      return self::$matchIp;
    }
    
    protected static function lastRegenerateKey() {
      return self::$lastRegenerateKey;
    }
    
    protected static function cookieName() {
      return self::$cookie['name'];
    }

    protected static function sessionIdRegexp() {
      return self::$sessionIdRegexp;
    }

    public static function init() {
      if (Request::method() == 'cli' || (bool)ini_get('session.auto_start'))
        return null;
      
      $config = \config('Session');

      $driver = '\Session\\' . $config['driver']['type'];
      class_exists($driver) || \GG('Session 錯誤，請檢查 Session Config 的「driver」值！');

      self::$matchIp           = $config['matchIp'] ?: false;
      self::$expiration        = $config['expiration'] ?: 60 * 60 * 24 * 30 * 3;
      self::$time2Update       = $config['time2Update'] ?: 60 * 60 * 24;
      self::$regenerateDestroy = $config['regenerateDestroy'] ?: true;

      self::$lastRegenerateKey = $config['lastRegenerateKey'] ?: '__maple__last__regenerate';
      self::$varsKey           = $config['varsKey'] ?: '__maple__vars';

      $cookie = \config('Cookie');
      self::$cookie['name']   = $config['cookieName'] ?: 'MapleSession';
      self::$cookie['path']   = $cookie['path'];
      self::$cookie['domain'] = $cookie['domain'];
      self::$cookie['secure'] = $cookie['secure'];

      $class = new $driver();
      
      session_set_save_handler($class, true);

      self::configure();

      if (isset($_COOKIE[self::$cookie['name']]) && !(is_string($_COOKIE[self::$cookie['name']]) && preg_match('#\A' . self::sessionIdRegexp() . '\z#', $_COOKIE[self::$cookie['name']])))
        unset($_COOKIE[self::$cookie['name']]);

      session_start();

      if ((empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') && self::$time2Update > 0) {
        if (!isset($_SESSION[self::$lastRegenerateKey]))
          $_SESSION[self::$lastRegenerateKey] = time();
        else if ($_SESSION[self::$lastRegenerateKey] < (time() - self::$time2Update))
          self::sessRegenerate(self::$regenerateDestroy);
        else;
      } else if (isset($_COOKIE[self::$cookie['name']]) && $_COOKIE[self::$cookie['name']] === session_id()) {
        setcookie(self::$cookie['name'],
          session_id(),
          self::$expiration ? time() + self::$expiration : 0,
          self::$cookie['path'],
          self::$cookie['domain'],
          self::$cookie['secure'],
          true);
      }
      self::oaciInitVars();
    }

    private static function configure() {
      ini_set('session.name', self::$cookie['name']);

      session_set_cookie_params(self::$expiration, self::$cookie['path'], self::$cookie['domain'], self::$cookie['secure'], true);

      if (self::$expiration)
        ini_set('session.gc_maxlifetime', self::$expiration = (int)self::$expiration);
      else
        self::$expiration = (int)ini_get('session.gc_maxlifetime');

      // Security is king
      ini_set('session.use_trans_sid', 0);
      ini_set('session.use_strict_mode', 1);
      ini_set('session.use_cookies', 1);
      ini_set('session.use_only_cookies', 1);

      self::configureSessionIdLength();
    }

    private static function configureSessionIdLength() {
      if (PHP_VERSION_ID < 70100) {
        $hashFunc = ini_get('session.hash_function');

        if (ctype_digit($hashFunc)) {
          $hashFunc === '1' || ini_set('session.hash_function', 1);
          $bits = 160;
        } else if (!in_array($hashFunc, hash_algos(), true)) {
          ini_set('session.hash_function', 1);
          $bits = 160;
        } else if (($bits = strlen(hash($hashFunc, 'dummy', false)) * 4) < 160) {
          ini_set('session.hash_function', 1);
          $bits = 160;
        }

        $bitsPerCharacter = (int)ini_get('session.hash_bits_per_character');
        $sidLength        = (int)ceil($bits / $bitsPerCharacter);
      } else {
        $bitsPerCharacter = (int)ini_get('session.sid_bits_per_character');
        $sidLength        = (int)ini_get('session.sid_length');
        
        if (($bits = $sidLength * $bitsPerCharacter) < 160) {
          $sidLength += (int)ceil((160 % $bits) / $bitsPerCharacter);
          ini_set('session.sid_length', $sidLength);
        }
      }

      switch ($bitsPerCharacter) {
        case 4: self::$sessionIdRegexp = '[0-9a-f]';      break;
        case 5: self::$sessionIdRegexp = '[0-9a-v]';      break;
        case 6: self::$sessionIdRegexp = '[0-9a-zA-Z,-]'; break;
      }

      self::$sessionIdRegexp .= '{' . $sidLength . '}';
    }

    private static function oaciInitVars() {
      isset($_SESSION[self::$varsKey]) || $_SESSION[self::$varsKey] = [];

      if (!$_SESSION[self::$varsKey])
        return ;

      $current_time = time();

      foreach ($_SESSION[self::$varsKey] as $key => &$value) {
        if ($value === 'new')
          $_SESSION[self::$varsKey][$key] = 'old';
        else if ($value < $current_time)
          unset($_SESSION[$key], $_SESSION[self::$varsKey][$key]);
      }

      $_SESSION[self::$varsKey] || $_SESSION[self::$varsKey] = [];
    }

    // -------------------------------------

    public static function sessDestroy() {
      return session_destroy();
    }

    public static function sessRegenerate($destroy = null) {
      $_SESSION[self::$lastRegenerateKey] = time();
      return session_regenerate_id(is_bool($destroy) ? $destroy : self::lastRegenerateKey());
    }

    public static function datas() {
      return $_SESSION;
    }

    public static function hasData($key) {
      return isset($_SESSION[$key]);
    }

    // -------------------------------------

    public static function setData($data, $value) {
      $_SESSION[$data] = $value;
      return true;
    }

    public static function getData($key) {
      return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    public static function unsetData($key) {
      is_array($key) || $key = [$key];

      foreach ($key as $k)
        unset($_SESSION[$k]);

      return true;
    }

    // -------------------------------------

    public static function getFlashDataKeys() {
      if (!isset($_SESSION[self::$varsKey]))
        return [];

      return array_values(array_filter(array_keys($_SESSION[self::$varsKey]), function($key) {
        return !is_int($_SESSION[self::$varsKey][$key]);
      }));
    }

    public static function getFlashDatas() {
      if (!isset($_SESSION[self::$varsKey]))
        return [];

      $flashdata = [];

      if ($_SESSION[self::$varsKey])
        foreach ($_SESSION[self::$varsKey] as $key => $value)
          if (!is_int($value) && isset($_SESSION[$key]))
            $flashdata[$key] = $_SESSION[$key];

      return $flashdata;
    }

    public static function setFlashData($data, $value) {
      return self::setData($data, $value) && self::markAsFlash($data);
    }

    public static function getFlashData($key) {
      return isset($_SESSION[self::$varsKey], $_SESSION[self::$varsKey][$key], $_SESSION[$key]) && !is_int($_SESSION[self::$varsKey][$key]) ? $_SESSION[$key] : null;
    }

    public static function markAsFlash($key) {
      if (!isset($_SESSION[$key]))
        return false;

      $_SESSION[self::$varsKey][$key] = 'new';
      return true;
    }

    public static function keepFlashData($key) {
      return self::markAsFlash($key);
    }

    public static function unmarkFlashData($key) {
      if (!isset($_SESSION[self::$varsKey]))
        return true;

      is_array($key) || $key = [$key];

      foreach ($key as $k)
        if (isset($_SESSION[self::$varsKey][$k]) && !is_int($_SESSION[self::$varsKey][$k]))
          unset($_SESSION[self::$varsKey][$k]);

      $_SESSION[self::$varsKey] || $_SESSION[self::$varsKey] = [];

      return true;
    }

    public static function unsetFlashData($key) {
      return self::unmarkFlashData($key) && self::unsetData($key);
    }

    // -------------------------------------

    public static function getTmpKeys() {
      if (!isset($_SESSION[self::$varsKey]))
        return [];

      return array_values(array_filter(array_keys($_SESSION[self::$varsKey]), function($key) {
        return is_int($_SESSION[self::$varsKey][$key]);
      }));

      return $keys;
    }

    public static function getTmpDatas() {
      $tempdata = [];

      if ($_SESSION[self::$varsKey])
        foreach ($_SESSION[self::$varsKey] as $key => &$value)
          if (is_int($value))
            $tempdata[$key] = $_SESSION[$key];

      return $tempdata;
    }

    public static function setTmpData($data, $value, $ttl = 300) {
      return self::setData($data, $value) && self::markAsTmp($data, $ttl);
    }

    public static function getTmpData($key) {
      return isset($_SESSION[self::$varsKey], $_SESSION[self::$varsKey][$key], $_SESSION[$key]) && is_int($_SESSION[self::$varsKey][$key]) ? $_SESSION[$key] : null;
    }

    public static function markAsTmp($key, $ttl = 300) {
      if (!isset($_SESSION[$key]))
        return false;
      
      $_SESSION[self::$varsKey][$key] = time() + $ttl;
      return true;
    }

    public static function unmarkTmp($key) {
      if (!isset($_SESSION[self::$varsKey]))
        return true;

      is_array($key) || $key = [$key];

      foreach ($key as $k)
        if (isset($_SESSION[self::$varsKey][$k]) && is_int($_SESSION[self::$varsKey][$k]))
          unset($_SESSION[self::$varsKey][$k]);

      $_SESSION[self::$varsKey] || $_SESSION[self::$varsKey] = [];

      return true;
    }

    public static function unsetTempData($key) {
      return self::unmarkTmp($key) && self::unsetData($key);
    }
  }

  Session::init();
}
