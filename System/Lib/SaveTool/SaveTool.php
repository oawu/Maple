<?php

abstract class SaveTool {
  protected $bucket = null;

  protected function __construct($bucket) {
    $this->bucket = $bucket;
  }

  abstract public function put($filePath, $localPath);
  abstract public function delete($path);
}