<?php

use \Cache\File;
use \Cache\Redis;

abstract class Cache {
  private static ?File $_file = null;
  private static $_redis = null;

  public static function file(string $key, int $ttl, callable $closure) {
    if (self::$_file === null) {
      self::$_file = new File();
    }

    $data = self::$_file->get($key);

    if ($data !== null) {
      return $data;
    }

    $data = is_callable($closure)
      ? $closure()
      : $closure;

    $result = self::$_file->save($key, $data, $ttl);
    if (!$result) {
      Log::warning('Cache::file 錯誤，Save 失敗！');
    }

    return $data;
  }
  public static function fileClean(?string $key = null): bool {
    if (self::$_file === null) {
      self::$_file = new File();
    }
    return $key === null
      ? self::$_file->clean()
      : self::$_file->delete($key);
  }

  public static function redis(string $key, int $ttl, callable $closure) {
    if (self::$_redis === null) {
      self::$_redis = new Redis();
    }

    $data = self::$_redis->get($key);

    if ($data !== null) {
      return $data;
    }
    $data = is_callable($closure)
      ? $closure()
      : $closure;

    $result = self::$_redis->save($key, $data, $ttl);
    if (!$result) {
      Log::warning('Cache::redis 錯誤，Save 失敗！');
    }

    return $data;
  }
  public static function redisClean($key = null) {
    if (self::$_redis === null) {
      self::$_redis = new Redis();
    }
    return $key === null
      ? self::$_redis->clean()
      : self::$_redis->delete($key);
  }
}
