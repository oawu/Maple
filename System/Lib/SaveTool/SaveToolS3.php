<?php

Load::systemLib('S3.php');

class SaveToolS3 extends SaveTool {
  private $s3 = null;

  public function __construct($bucket, $accessKey, $secretKey) {
    parent::__construct($bucket);
    $this->s3 = S3::instance($accessKey, $secretKey);
  }

  public static function create($bucket, $accessKey, $secretKey) {
    return new static($bucket, $accessKey, $secretKey);
  }

  public function put($filePath, $localPath) {
    return $this->s3->putObject($filePath, $this->bucket, $localPath) ? true : !\Log::saveTool('上傳 S3 失敗！', '檔案路徑：' . $filePath, '儲存路徑：' . $localPath, 'Bucket：' . $this->bucket);
  }

  public function delete($path) {
    return $this->s3->deleteObject($this->bucket, $path) ? true : !\Log::saveTool('刪除 S3 檔案失敗', '檔案路徑：' . $path, 'Bucket：' . $this->bucket);
  }
}