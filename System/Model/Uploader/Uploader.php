<?php

namespace _M;

if (!function_exists('\_M\getRandomName')) {
  function getRandomName() {
    return md5(uniqid(mt_rand(), true));
  }
}

if (!function_exists('\_M\webFileExists')) {
  function webFileExists($url, $cainfo = null) {
    $options = [CURLOPT_URL => $url, CURLOPT_NOBODY => 1, CURLOPT_FAILONERROR => 1, CURLOPT_RETURNTRANSFER => 1];

    is_readable($cainfo) && $options[CURLOPT_CAINFO] = $cainfo;

    $ch = curl_init($url);
    curl_setopt_array($ch, $options);
    return curl_exec($ch) !== false;
  }
}

if (!function_exists('\_M\downloadWebFile')) {
  function downloadWebFile($url, $filename = null, $isUseReffer = false, $cainfo = null) {
    if (!webFileExists($url, $cainfo))
      return null;

    is_readable($cainfo) && $url = str_replace(' ', '%20', $url);

    $options = [CURLOPT_URL => $url, CURLOPT_TIMEOUT => 120, CURLOPT_HEADER => false, CURLOPT_MAXREDIRS => 10, CURLOPT_AUTOREFERER => true, CURLOPT_CONNECTTIMEOUT => 30, CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.76 Safari/537.36"];

    is_readable ($cainfo) && $options[CURLOPT_CAINFO] = $cainfo;

    $isUseReffer && $options[CURLOPT_REFERER] = $url;

    $ch = curl_init($url);
    curl_setopt_array($ch, $options);
    $data = curl_exec($ch);
    curl_close($ch);

    if (!$filename)
      return $data;

    $write = fopen($filename, 'w');
    fwrite($write, $data);
    fclose($write);

    @umaskChmod($filename, 0777);

    return filesize($filename) ? $filename : null;
  }
}

abstract class Uploader {
  protected static $config = null;
  private static $saveTool = null;

  protected $obj = null;
  protected $column = null;
  protected $value = null;
  protected $defaultUrl;

  public static function bind($obj, $column, $params) {
    if (!self::$config) {
      self::$config = [
        'saveDir' => 'dir',
        'tmpDir' => sys_get_temp_dir(),
        'baseUrl' => '/',
        'thumbnailTool' => [
          'class' => 'ThumbnailImagick'
          // 'class' => 'ThumbnailGd'
        ],
        'saveTool' => [
          'class' => 'SaveToolLocal',
          'params' => [PATH]
        ],
        'deleteLast' => false,
      ];

      $config = config('Model', 'uploader');
      self::$config = array_merge(self::$config, $config);

      self::$config['saveDir']                = rtrim(self::$config['saveDir'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
      self::$config['tmpDir']                 = rtrim(self::$config['tmpDir'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
      self::$config['baseUrl']                = rtrim(self::$config['baseUrl'], '/') . '/';
      self::$config['thumbnailTool']['class'] = '\\' . ltrim(self::$config['thumbnailTool']['class'], '\\');
      self::$config['saveTool']['class']      = '\\' . ltrim(self::$config['saveTool']['class'], '\\');
    }

    return new static($obj, $column, $params);
  }

  public function __construct($obj, $column, $params) {
    $attrs = $obj->attrs();
    $this->obj = $obj;
    $this->column = $column;
    $this->value  = $obj->$column;
    $obj->$column = $this;

    isset($params['defaultUrl']) && $this->defaultUrl = $params['defaultUrl'];
  }

  public function __toString() {
    return (string)$this->value;
  }

  public function obj() {
    return $this->obj;
  }

  public function putUrl($url) {
    $format = strtolower(pathinfo($url, PATHINFO_EXTENSION));
    if (!$tmp = downloadWebFile($url, self::$config['tmpDir'] . getRandomName() . ($format ? '.' . $format : '')))
      return false;

    if (!$this->put($tmp)) {
      @unlink($tmp) || \Log::uploader('putUrl 的暫存檔案沒有被刪除！');
      return false;
    }

    if (file_exists($tmp))
      @unlink($tmp) || \Log::uploader('putUrl 的暫存檔案沒有被刪除！');

    return true;
  }

  public function put($fileInfo) {

    if (!($fileInfo && (is_array($fileInfo) || (is_string($fileInfo) && file_exists($fileInfo)))))
      return !\Log::uploader('檔案格式有誤(1)！', '檔案：', $fileInfo);

    if (is_array($fileInfo)) {
      foreach (['name', 'tmp_name', 'type', 'error', 'size'] as $key)
        if (!array_key_exists($key, $fileInfo))
          return !\Log::uploader('檔案格式有誤(2)！', '缺少 key：' . $key, '檔案：', $fileInfo);

      $name = $fileInfo['name'];
    } else {
      $name = basename($fileInfo);
      $fileInfo = ['name' => 'file', 'tmp_name' => $fileInfo, 'type' => '', 'error' => '', 'size' => '1'];
    }

    $pathinfo = pathinfo($name);
    
    $name = preg_replace("/[^a-zA-Z0-9\\._-]/", "", $name);
    $format = !empty($pathinfo['extension']) ? '.' . $pathinfo['extension'] : '';
    $name = ($pathinfo['filename'] ? $pathinfo['filename'] : getRandomName()) . $format;

    if (!$tmp = $this->moveOriFile($fileInfo))
      return false;

    if (!$path = $this->saveUri())
      return false;

    $path = self::$config['saveDir'] . $path;

    if (!$this->moveFile($tmp, $path, $name))
      return false;

    return true;
  }

  protected function setColumn($value, $isSave = true) {
    self::$config['deleteLast'] && $this->deleteLast();
    return $isSave ? $this->uploadColumn($value) : true;
  }

  protected function uploadColumn($value) {
    $column = $this->column;
    $this->obj->$column = $value;

    if (!$this->obj->save())
      return !\Log::uploader('Model 儲存失敗！');

    $this->value = $value;
    $this->obj->$column = $this;
    return true;
  }

  protected function deleteLast() {
    if (!$saveTool = self::saveTool())
      return !\Log::uploader('deleteLast 取得 Save Tool 物件失敗！');

    foreach ($this->paths() as $path)
      if (!$saveTool->delete($path))
        \Log::uploader('清除檔案發生錯誤！', '檔案路徑：' . $path);

    return true;
  }

  public function paths() {
    return !((string)$this->value) ? [] : [self::$config['saveDir'] . $this->saveUri() . $this->value];
  }

  public function saveUri() {
    if (!array_key_exists($this->uniqueColumn(), $this->obj->attrs()))
      return !\Log::uploader('物件 「' . get_class($this->obj) . '」 沒有 「' . $this->uniqueColumn() . '」 欄位！');

    $id = $this->obj->attrs($this->uniqueColumn(), 0);
    $tmp = get_class($this->obj)::table()->tableName . DIRECTORY_SEPARATOR . $this->column . DIRECTORY_SEPARATOR;

    if (is_numeric($id))
      return $tmp . implode(DIRECTORY_SEPARATOR, str_split(sprintf('%08s', base_convert($id, 10, 36)), 4)) . DIRECTORY_SEPARATOR;

    return '';
  }
  
  protected function uniqueColumn() {
    return 'id';
  }

  private function moveOriFile($fileInfo) {
    $tmp = self::$config['tmpDir'] . 'uploader_' . getRandomName();

    if (is_uploaded_file($fileInfo['tmp_name']))
      @move_uploaded_file($fileInfo['tmp_name'], $tmp);
    else
      @rename($fileInfo['tmp_name'], $tmp);

    @\umaskChmod($tmp, 0777);
    
    return file_exists($tmp)
      ? $tmp
      : \Log::uploader('搬移至暫存資料夾時發生錯誤！', 'moveOriFile 失敗！', '暫存目錄：' . $tmp);
  }

  protected function moveFile($tmp, $path, $name) {
    if (!$saveTool = self::saveTool())
      return !\Log::uploader('moveFile 取得 Save Tool 物件失敗！');

    if (!$saveTool->put($tmp, $path . $name))
      return !\Log::uploader('使用 Save Tool 放置檔案時發生錯誤！', '檔案路徑：' . $tmp, '儲存路徑：' . $path . $name);

    @unlink($tmp) || \Log::uploader('移除舊資料錯誤！');

    if (!$this->setColumn(''))
      return !\Log::uploader('清空欄位值失敗！');

    if (!$this->setColumn($name))
      return !\Log::uploader('設定欄位值失敗！');

    return true;
  }

  protected static function saveTool() {
    if (self::$saveTool)
      return self::$saveTool;

    $config = self::$config['saveTool'];

    if (!\Load::systemLib('SaveTool'))
      return false;

    return self::$saveTool = call_user_func_array([$config['class'], 'create'], $config['params']);
  }

  protected static function thumbnailTool($path) {
    $config = self::$config['thumbnailTool'];
    
    if (!class_exists($config['class']) && !\Load::systemLib('Thumbnail'))
      return false;

    return $config['class']::create($path);
  }

  public function cleanAllFiles($isSave = true) {
    $this->deleteLast();
    return $this->setColumn('', $isSave);
  }

  public function path($filename = '') {
    return $filename ? self::$config['saveDir'] . $this->saveUri() . $filename : '';
  }

  public function defaultUrl() {
    return is_string($this->defaultUrl) ? $this->defaultUrl : '';
  }

  public function url($key = null) {
    return ($path = $this->path($key)) ? self::$config['baseUrl'] . $path : $this->defaultUrl();
  }
}