<?php

namespace _M;

class FileUploader extends Uploader {
  public function url($key = null) {
    return parent::url('');
  }

  public function path($filename = '') {
    return parent::path((string)$this->value);
  }
}