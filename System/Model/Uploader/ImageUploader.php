<?php

namespace _M;

class ImageUploader extends Uploader {
  const ORI = 'ori';
  private $versions = [];
  const AUTO_FORMAT = true;
  const SYMBOL = '_';

  public function __construct($orm, $column, $params) {
    parent::__construct($orm, $column, $params);
    isset($params['versions']) && $this->versions = $params['versions'];
  }

  public function versions() {
    $versions = $this->versions;
    return $versions && is_array($versions) ? array_merge([ImageUploader::ORI => []], $versions) : [ImageUploader::ORI => []];
  }

  protected function moveFile($tmp, $path, $oriName) {
    $tmpDir = self::$config['tmpDir'];
    $versions = $this->versions();
    
    $info = @exif_read_data($tmp);
    $orientation = $info && isset($info['Orientation']) ? $info['Orientation'] : 0;
    $orientation = $orientation == 6 ? 90 : ($orientation == 8 ? -90 : ($orientation == 3 ? 180 : 0));
    $news = [];

    try {
      foreach ($versions as $key => $methods) {
        $image = self::thumbnailTool($tmp);
        $image->rotate($orientation);

        $name = !isset($name) ? getRandomName() . (ImageUploader::AUTO_FORMAT ? '.' . $image->getFormat() : '') : $name;
        $versionName = $key . ImageUploader::SYMBOL . $name;
        $newPath = $tmpDir . $versionName;

        if (!$this->thumbnail($image, $newPath, $key, $methods))
          return !\Log::uploader('圖像處理失敗！', 'utility 發生錯誤！', '儲存路徑：' . $newPath, '版本' . $key);

        array_push($news, [
          'name' => $versionName,
          'path' => $newPath
        ]);
      }
    } catch (\Exception $e) {
      return !\Log::uploader('圖像處理，發生意外錯誤！', '錯誤訊息：' . $e->getMessage());
    }

    if (count($news) != count($versions))
      return !\Log::uploader('縮圖未完成，有些圖片未完成縮圖！', '成功數量：' . count($news), '版本數量：' . count($versions));

    if (!$saveTool = self::saveTool())
      return !\Log::uploader('moveFile 取得 Save Tool 物件失敗！');

    foreach ($news as $new)
      if ($saveTool->put($new['path'], $path . $new['name']))
        @unlink($new['path']) || \Log::uploader('移除暫存資料錯誤！');
      else
        return !\Log::uploader('Save Tool put 發生錯誤！', '檔案路徑：' . $new['path'], '儲存路徑：' . $path . $new['name']);

    @unlink($tmp) || \Log::uploader('移除舊資料錯誤！');

    if (!$this->setColumn(''))
      return !\Log::uploader('清空欄位值失敗！');

    if (!$this->setColumn($name))
      return !\Log::uploader('設定欄位值失敗！');

    return true;
  }

  private function thumbnail($image, $savePath, $key, $methods) {
    if (!$methods)
      return $image->save($savePath, true);

    foreach ($methods as $params) {
      if (!$method = array_shift($params))
        return !\Log::uploader('縮圖函式方法錯誤！', '縮圖函式：' . $method);

      if (!method_exists($image, $method))
        return !\Log::uploader('縮圖函式沒有此方法！', '縮圖函式：' . $method);
      
      call_user_func_array([$image, $method], $params);
    }

    return $image->save($savePath, true);
  }

  public function paths() {
    $paths = [];

    if (!(string)$this->value)
      return $paths;

    $dir = self::$config['saveDir'];

    foreach ($this->versions() as $key => $version)
      array_push($paths, $dir . $this->saveUri() . $key . ImageUploader::SYMBOL . $this->value);

    return $paths;
  }

  public function path($key = null) {
    $key !== null || $key = ImageUploader::ORI;
    $versions = $this->versions();
    $fileName = array_key_exists($key, $versions) && ($value = (string)$this->value) ? $key . ImageUploader::SYMBOL . $value : '';
    return parent::path($fileName);
  }
}
