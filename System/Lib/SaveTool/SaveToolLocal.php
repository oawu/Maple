<?php

class SaveToolLocal extends SaveTool {
  public static function create($bucket) {
    return new static(rtrim($bucket, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
  }

  public function put($filePath, $localPath) {
    if (!(is_file($filePath) && is_readable($filePath)))
      return !\Log::saveTool('無法開啟檔案！', '檔案路徑：' . $filePath, '儲存路徑：' . $localPath, 'Bucket：' . $this->bucket);

    $path = pathinfo($tmp = $this->bucket . $localPath, PATHINFO_DIRNAME);

    if (!file_exists($path))
      \umaskMkdir($path, 0777, true);

    if (!is_dir($path))
      return !\Log::saveTool('資料夾不能存在！', '資料夾路徑：' . $path);

    if (!is_writable($path))
      return !\Log::saveTool('資料夾不能讀寫！', '資料夾路徑：' . $path);

    
    if (!@copy($filePath, $tmp))
      return !\Log::saveTool('複製檔案失敗！', '檔案路徑：' . $tmp);

    @\umaskChmod($tmp, 0777);

    return is_readable($tmp) ? true : !\Log::saveTool('檔案不可讀取！', '檔案路徑：' . $tmp);
  }

  public function delete($path) {
    return file_exists($this->bucket . $path) ? @unlink($this->bucket . $path) ? true : !\Log::saveTool('清除檔案發生錯誤！', '檔案路徑：' . $path) : true;
  }
}