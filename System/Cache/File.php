<?php

namespace Cache;

use \Cache;
use \Config;
use \Log;
use \Helper\File as _File;

final class File extends Cache {
  const PERMISSIONS = 0777;

  private ?string $_path = null;
  private bool $_success = false;

  public function __construct(string $prefix = '', ?string $path = null) {
    if ($path === null) {
      $path = Config::get('Cache', 'CacheFile', 'path');
    }

    if (!is_string($path)) {
      Log::warning('CacheFile 錯誤，儲存的目錄路徑格式錯誤！');
      return;
    }

    if (!is_dir($path)) {
      Log::warning('CacheFile 錯誤，儲存的目錄格式錯誤！');
      return;
    }
    if (!is_writable($path)) {
      Log::warning('CacheFile 錯誤，路徑無法寫入，路徑：' . $path);
      return;
    }

    $this->_path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $prefix;
    $this->_success = true;
  }

  public function get(string $key) {
    if (!$this->_success) {
      return null;
    }
    $path = $this->_path . $key;

    if (!is_file($path)) {
      return null;
    }
    $data = _File::read($path);
    if ($data === null) {
      return null;
    }

    $data = unserialize($data);

    if (isset($data['time'], $data['data']) && time() <= $data['time'] + $data['ttl']) {
      return $data['data'];
    }
    $this->delete($key);
    return null;
  }
  public function save(string $key, $data, int $ttl = 60): bool {
    if (!$this->_success) {
      return false;
    }

    $path = $this->_path . $key;

    $data = serialize([
      'time' => time(),
      'data' => $data,
      'ttl' => $ttl
    ]);

    if (!_File::write($path, $data)) {
      return false;
    }

    @chmod($path, File::PERMISSIONS);
    return true;
  }
  public function delete(string $key): bool {
    if (!$this->_success) {
      return false;
    }

    $path = $this->_path . $key;
    if (!is_file($path)) {
      return false;
    }

    @unlink($path);
    return true;
  }
  public function clean(): bool {
    return $this->_success
      ? _File::delete($this->_path, false, true)
      : false;
  }
  public function info(): ?array {
    return $this->_success
      ? _File::dirInfo($this->_path)
      : null;
  }
  public function meta(string $key): ?array {
    if (!$this->_success) {
      return null;
    }

    $path = $this->_path . $key;

    if (!is_file($path)) {
      return null;
    }

    $data = unserialize(_File::read($path));

    if (!is_array($data)) {
      return null;
    }

    $mtime = filemtime($path);
    if (!isset($data['ttl'], $data['time'])) {
      return null;
    }

    return [
      'expire' => $data['time'] + $data['ttl'],
      'mtime'  => $mtime
    ];
  }
}
