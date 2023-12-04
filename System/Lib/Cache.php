<?php

namespace Cache {

  abstract class Core {
    protected $success = false;

    abstract public function get($key);

    abstract public function delete($key);

    abstract public function save($key, $data, $ttl);

    abstract public function clean();

    abstract public function info();

    abstract public function meta($key);

    protected function __construct($options) {
      $this->prefix = \config('Cache', 'prefix');
      isset($options['prefix']) && $this->prefix = $options['prefix'];
      $this->prefix === null && $this->prefix = '';
    }
  }

  final class File extends Core {
    private $path = null;
    const PERMISSIONS = 0777;

    public function __construct($prefix = '', $path = null) {
      if (!\Load::systemFunc('File'))
        return \Log::warning('CacheFile 錯誤，載入 file 函式錯誤！');

      $path !== null || $path = \config('Cache', 'CacheFile', 'path');

      if (!isset($path))
        return \Log::warning('CacheFile 錯誤，尚未設定儲存的目錄路徑！');
      
      if (!is_string($path))
        return \Log::warning('CacheFile 錯誤，儲存的目錄路徑格式錯誤！');
      
      if (!is_writable($path))
        return \Log::warning('CacheFile 錯誤，路徑無法寫入，路徑：' . $path);

      $this->path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $prefix;
      $this->success = true;
    }

    public function get($key) {
      if (!$this->success)
        return null;

      if (!is_file($this->path . $key))
        return null;

      $data = unserialize(\fileRead($this->path . $key));

      if (isset($data['time'], $data['data']) && time() <= $data['time'] + $data['ttl'])
        return $data['data'];

      $this->delete($key);
      return null;
    }

    public function save($key, $data, $ttl = 60) {
      if (!$this->success)
        return false;

      if (!\fileWrite($this->path . $key, serialize(['time' => time(), 'data' => $data, 'ttl' => $ttl])))
        return false;

      @chmod($this->path . $key, File::PERMISSIONS);
      return true;
    }

    public function delete($key) {
      return $this->success
        ? is_file($this->path . $key)
          ? @unlink($this->path . $key)
          : false
        : false;
    }

    public function clean() {
      return $this->success
        ? \filesDelete($this->path, false, true)
        : false;
    }

    public function info() {
      return $this->success
        ? \dirFilesInfo($this->path)
        : null;
    }

    public function meta($key) {
      if (!$this->success)
        return null;

      if (!is_file($this->path . $key))
        return null;

      $data = unserialize(fileRead($this->path . $key));

      if (!is_array($data))
        return null;

      $mtime = filemtime($this->path . $key);

      return !isset($data['ttl'], $data['time']) ? false : [
        'expire' => $data['time'] + $data['ttl'],
        'mtime'  => $mtime
      ];
    }
  }

  final class Redis extends Core {
    private $prefix = '';
    private $redis = null;
    private $serializeKey = '';

    public function __construct($prefix = '', $options = null) {
      is_array($options) || $options = \config('Cache', 'CacheRedis');

      $config = array_merge([
        'host' => 'localhost',
        'port' => 6379,
        'timeout' => null,
        'password' => null,
        'database' => null,
        'serializeKey' => '_maple_cache_serialized',
      ], $options ?: []);

      $this->prefix = $prefix;
      $this->serializeKey = $options['serializeKey'];

      try {
        if (!extension_loaded('redis'))
          throw new RedisException('載入 Redis extension 失敗！');

        if (!class_exists('Redis'))
          throw new RedisException('找不到 Redis 物件，請檢查 phpinfo 是否有開啟 Redis 功能！');

        $this->redis = new \Redis();
        
        if (!$this->redis->connect($options['host'], $options['port'], $options['timeout']))
          throw new RedisException('連不上 Redis，Host：' . $options['host'] . '，Port：' . $options['port'] . '，Timeout：' . $options['timeout']);

        if ($options['password'])
          if (!$this->redis->auth($options['password']))
            throw new RedisException('請確認密碼，密碼：' . $options['password']);

        if ($options['database'])
          if (!$this->redis->select($options['database']))
            throw new RedisException('找不到指定的 Database，Database：' . $options['database']);

      } catch (RedisException $e) {
        return !Log::warning('CacheRedis 錯誤，錯誤原因：' . $e->getMessage());
      }

      $this->success = true;
    }

    public function get($key) {
      if (!$this->success)
        return null;

      $value = $this->redis->get($this->prefix . $key);

      return $value !== false
        ? unserialize($value)
        : null;
    }
    public function save($key, $data, $ttl = 60) {
      if (!$this->success)
        return false;

      $key = $this->prefix . $key;
      $data = serialize($data);

      return $this->redis->set($key, $data, $ttl) ? true : false;
    }


    public function delete($key) {
      return $this->success && $this->redis->del($this->prefix . $key) == 1;
    }

    public function clean() {
      if (!$this->success)
        return false;

      if (!$this->prefix)
        return $this->redis->flushDB();

      if ($keys = $this->redis->keys($this->prefix . '*'))
        foreach ($keys as $key)
          $this->redis->delete($keys);

      return true;
    }

    public function info() {
      return $this->success ? $this->redis->info() : false;
    }

    public function meta($key) {
      if (!$this->success)
        return null;

      $value = $this->get($this->prefix . $key);

      return $value !== false ? [
        'expire' => time() + $this->redis->ttl($key)
      ] : null;
    }

    public function __destruct() {
      $this->redis && $this->redis->close() && $this->redis = null;
    }
  }
}

namespace {
  abstract class Cache {
    private static $file = null;
    private static $redis = null;

    public static function file($key, $ttl, $closure) {
      self::$file || self::$file = new \Cache\File();
      
      $data = self::$file->get($key);
      
      if ($data !== null)
        return $data;

      self::$file->save($key, $data = is_callable($closure)
        ? $closure()
        : $closure, $ttl) || \Log::warning('Cache::file 錯誤，Save 失敗！');

      return $data;
    }

    public static function fileClean($key = null) {
      self::$file || self::$file = new \Cache\File();
      return $key === null ? self::$file->clean() : self::$file->delete($key);
    }
    public static function redis($key, $ttl, $closure) {
      self::$redis || self::$redis = new \Cache\Redis();

      $data = self::$redis->get($key);

      if ($data !== null)
        return $data;

      self::$redis->save($key, $data = is_callable($closure)
        ? $closure()
        : $closure, $ttl) || \Log::warning('Cache::redis 錯誤，Save 失敗！');

      return $data;
    }
    public static function redisClean($key = null) {
      self::$redis || self::$redis = new \Cache\Redis();
      return $key === null ? self::$redis->clean() : self::$redis->delete($key);
    }
  }
}
