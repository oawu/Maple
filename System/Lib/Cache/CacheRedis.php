<?php

class CacheRedis extends Cache {
  private $redis = null;

  private $serializeKey = '';
  private $serialized = [];

  public function __construct ($options = []) {
    parent::__construct($options);

    $config = [
      'host' => 'localhost',
      'port' => 6379,
      'timeout' => null,
      'password' => null,
      'database' => null,
      'serializeKey' => '_maple_cache_serialized',
    ];

    if ($tmp = config('Cache', 'CacheRedis'))
      $config = array_merge($config, $tmp);

    isset($config['prefix']) && $this->prefix = $config['prefix'];

    $options = array_merge($config, $options);

    try {
      if (!extension_loaded('redis'))
        throw new RedisException('載入 Redis extension 失敗！');

      if (!class_exists('Redis'))
        throw new RedisException('找不到 Redis 物件，請檢查 phpinfo 是否有開啟 Redis 功能！');

      $this->redis = new Redis();
      
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

    $this->serializeKey = $options['serializeKey'];
    $serialized = $this->redis->sMembers($this->prefix . $this->serializeKey);
    empty($serialized) || $this->serialized = array_flip($serialized);
    $this->success = true;
  }

  public function get($id) {
    if (!$this->success)
      return null;

    $value = $this->redis->get($this->prefix . $id);
    
    if ($value === false)
      return null;

    if (isset($this->serialized[$this->prefix . $id]))
      return unserialize($value);

    return $value;
  }

  public function save($id, $data, $ttl = 60) {
    if (!$this->success)
      return false;

    $id = $this->prefix . $id;

    if (is_array($data) || is_object($data)) {
      if (!$this->redis->sIsMember($this->prefix . $this->serializeKey, $id) && !$this->redis->sAdd($this->prefix . $this->serializeKey, $id))
        return false;

      isset($this->serialized[$id]) || $this->serialized[$id] = true;
      $data = serialize($data);
    } else if (isset($this->serialized[$id])) {
      $this->serialized[$id] = null;
      $this->redis->sRemove($this->prefix . $this->serializeKey, $id);
    }

    return $this->redis->set($id, $data, $ttl) ? true : false;
  }

  public function delete($id) {
    if (!$this->success)
      return false;

    $id = $this->prefix . $id;
    if ($this->redis->delete($id) !== 1)
      return false;

    if (isset($this->serialized[$id])) {
      $this->serialized[$id] = null;
      $this->redis->sRemove($this->prefix . $this->serializeKey, $id);
    }

    return true;
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

  public function metadata($key) {
    if (!$this->success)
      return null;

    $value = $this->get($key);

    if ($value === false)
      return null;

    return [
      'expire' => time() + $this->redis->ttl($key),
      'data' => $value
    ];
  }

  public function __destruct() {
    $this->redis && $this->redis->close() && $this->redis = null;
  }
}
