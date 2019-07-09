<?php

abstract class Cache {
  protected $success = false;
  protected $prefix = '';

  abstract public function get($id);
  abstract public function save($id, $data, $ttl);

  protected function __construct($options) {
    $this->prefix = config('Cache', 'prefix');
    isset($options['prefix']) && $this->prefix = $options['prefix'];
    $this->prefix === null && $this->prefix = '';
  }

  private static $drivers = [];

  public static function getData($driver, $key, $ttl, $closure = null) {
    if (get_called_class() !== 'Cache') {
      $closure = $ttl; $ttl = $key; $key = $driver;
      $driver = get_called_class();
    }

    if (!$driver = self::create($driver)) {
      Log::warning('Cache getData 錯誤，無法取得 driver 類型！');
      return is_callable($closure) ? $closure() : $closure;
    }

    if (!$driver->success())
      return is_callable($closure) ? $closure() : $closure;

    $data = $driver->get($key);
    
    if ($data !== null)
      return $data;

    $data = is_callable($closure) ? $closure() : $closure;

    if (!$driver->save($key, $data, $ttl))
      Log::warning('Cache getData 錯誤，Save 失敗！');

    return $data;
  }

  private static function create($driver, $options = []) {
    if (!empty(self::$drivers[$driver]))
      return self::$drivers[$driver];

    $classes = [
      'file' => 'CacheFile',
      'redis' => 'CacheRedis',
      'CacheFile' => 'CacheFile',
      'CacheRedis' => 'CacheRedis',
    ];

    return !isset($classes[$driver]) ? null : self::$drivers[$driver] = new $classes[$driver]($options);
  }

  public static function __callStatic($method, $args = []) {
    if (!$args)
      return null;

    $key = array_shift($args);
    $expire = array_shift($args);
    $closure = array_shift($args);
    $options = array_shift($args);

    is_string($key) || gg('Cache 請給予字串格式的 key！');
    is_numeric($expire) || $expire = 60;
    is_array($options) || $options = [];

    if (!$class = self::create($method, $options))
      return is_callable($closure) ? $closure() : $closure;

    if (($data = $class->get($key)) !== null)
      return $data;

    $class->save($key, $data = is_callable($closure) ? $closure() : $closure, $expire);

    return $data;
  }

  public function success() {
    return $this->success;
  }
}
