<?php

namespace Cache;

use \Cache;
use \Config;
use \Redis as _Redis;
use \Log;
use \Exception;

final class Redis extends Cache {
  private string $_prefix = '';
  private string $_serializeKey = '';
  private bool $_success = false;
  private ?_Redis $_redis = null;

  public function __construct(string $prefix = '', array $options = []) {

    $configs = [
      'host' => 'localhost',
      'port' => 6379,
      'timeout' => null,
      'password' => null,
      'database' => null,
      'serializeKey' => '_maple_cache_serialized',
    ];
    $_configs = Config::get('Cache', 'CacheRedis') ?? [];
    foreach ($configs as $key => $value) {
      $configs[$key] = $options[$key] ?? $_configs[$key] ?? $value;
    }

    $this->_prefix = $prefix;
    $this->_serializeKey = $configs['serializeKey'];

    try {
      if (!extension_loaded('redis')) {
        throw new Exception('載入 Redis extension 失敗！');
      }

      if (!class_exists('Redis')) {
        throw new Exception('找不到 Redis 物件，請檢查 phpinfo 是否有開啟 Redis 功能！');
      }

      $this->_redis = new _Redis();

      if (!$this->_redis->connect($configs['host'], $configs['port'], $configs['timeout'])) {
        throw new Exception('連不上 Redis，Host：' . $configs['host'] . '，Port：' . $configs['port'] . '，Timeout：' . $configs['timeout']);
      }

      if (is_string($configs['password']) && $configs['password'] !== '') {
        if (!$this->_redis->auth($configs['password'])) {
          throw new Exception('請確認密碼，密碼：' . $configs['password']);
        }
      }

      if (is_string($configs['database']) && $configs['database'] !== '') {
        if (!$this->_redis->select($configs['database'])) {
          throw new Exception('找不到指定的 Database，Database：' . $configs['database']);
        }
      }
    } catch (Exception $e) {
      return Log::warning('CacheRedis 錯誤，錯誤原因：' . $e->getMessage());
    }

    $this->_success = true;
  }

  public function __destruct() {
    if (!$this->_redis) {
      return;
    }
    $this->_redis->close();
    $this->_redis = null;
  }
  public function get(string $key) {
    if (!$this->_success) {
      return null;
    }

    $key = $this->_prefix . $key;
    $value = $this->_redis->get($key);

    return $value !== false
      ? unserialize($value)
      : null;
  }
  public function save(string $key, $data, int $ttl = 60): bool {
    if (!$this->_success) {
      return false;
    }

    $key = $this->_prefix . $key;
    $data = serialize($data);

    return $this->_redis->set($key, $data, $ttl)
      ? true
      : false;
  }
  public function delete(string $key): bool {
    if (!$this->_success) {
      return false;
    }

    $key = $this->_prefix . $key;
    return $this->_redis->del($key) == 1;
  }
  public function clean(): bool {
    if (!$this->_success) {
      return false;
    }

    if (!$this->_prefix) {
      return $this->_redis->flushDB();
    }

    if ($keys = $this->_redis->keys($this->_prefix . '*')) {
      foreach ($keys as $key) {
        $this->_redis->delete($key);
      }
    }

    return true;
  }
  public function info(): ?array {
    return $this->_success ? $this->_redis->info() : null;
  }
  public function meta(string $key): ?array {
    if (!$this->_success) {
      return null;
    }

    $key = $this->_prefix . $key;
    $value = $this->get($key);
    if ($value === null) {
      return null;
    }

    return [
      'expire' => time() + $this->_redis->ttl($key)
    ];
  }
}
