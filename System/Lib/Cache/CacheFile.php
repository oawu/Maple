<?php

class CacheFile extends Cache {
  private $path = null;

  public function __construct($options = []) {
    parent::__construct($options);
    
    if (!Load::systemFunc('File'))
      return !Log::warning('CacheFile 錯誤，載入 file 函式錯誤！');

    $config = ['path' => PATH . 'cache' . DIRECTORY_SEPARATOR];

    if ($tmp = config('Cache', 'CacheFile'))
      $config = array_merge($config, $tmp);

    isset($config['prefix']) && $this->prefix = $config['prefix'];

    $options = array_merge($config, $options);

    if (!isset($options['path']))
      return !Log::warning('CacheFile 錯誤，尚未設定儲存的目錄路徑！');
    
    if (!is_string($options['path']))
      return !Log::warning('CacheFile 錯誤，儲存的目錄路徑格式錯誤！');
    
    if (!is_writable($options['path']))
      return !Log::warning('CacheFile 錯誤，路徑無法寫入，路徑：' . $options['path']);

    $this->path = rtrim($options['path'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $this->success = true;
  }

  public function get($id) {
    if (!$this->success)
      return null;

    $data = $this->_get($id);
    return $data;
  }

  private function _get($id) {
    if (!is_file($this->path . $this->prefix . $id))
      return null;

    $data = unserialize(fileRead($this->path . $this->prefix . $id));

    if (isset($data['time']) && time() <= $data['time'])
      return isset($data['data']) ? $data['data'] : null;

    @unlink($this->path . $this->prefix . $id);
    return null;
  }

  public function save($id, $data, $ttl = 60) {
    if (!$this->success)
      return false;

    $contents = [
      'time' => time() + $ttl,
      'data' => $data
    ];

    if (!fileWrite($this->path . $this->prefix . $id, serialize($contents)))
      return false;

    chmod($this->path . $this->prefix . $id, 0640);
    return true;
  }

  public function delete($id) {
    return $this->success
      ? is_file($this->path . $this->prefix . $id)
        ? @unlink($this->path . $this->prefix . $id)
        : false
      : false;
  }

  public function clean() {
    return $this->success ? filesDelete($this->path, false, true) : false;
  }

  public function info() {
    return $this->success ? dirFilesInfo($this->path) : null;
  }

  public function metadata($id) {
    if (!$this->success)
      return null;

    if (!is_file($this->path . $this->prefix . $id))
      return null;

    $data = unserialize(file_get_contents($this->path . $this->prefix . $id));

    if (!is_array($data))
      return null;

    $mtime = filemtime($this->path . $this->prefix . $id);

    return !isset($data['ttl'], $data['time']) ? false : [
      'expire' => $data['time'] + $data['ttl'],
      'mtime'  => $mtime
    ];
  }
}
