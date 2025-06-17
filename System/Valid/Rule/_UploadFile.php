<?php

namespace Valid\Rule;

use \Valid\Rule;

final class _UploadFile extends Rule {

  use \Valid\UploadFileSetting;

  public function check($data) {
    parent::check($data);

    $data = $this->getData();

    if (!$this->getIsRequired() && $data === null) {
      return $this->getIfNull();
    }

    if (!is_array($data)) {
      throw new \Valid\Exception($this->getReasonTitle() . '必須是「上傳檔案」格式', $this->getCode());
    }

    if (!(array_key_exists('name', $data) && is_string($data['name']))) {
      throw new \Valid\Exception($this->getReasonTitle() . '上傳檔案格式必須包含「name」', $this->getCode());
    }

    if (!(array_key_exists('type', $data) && is_string($data['type']))) {
      throw new \Valid\Exception($this->getReasonTitle() . '上傳檔案格式必須包含「type」', $this->getCode());
    }

    if (!(array_key_exists('tmp_name', $data) && is_string($data['tmp_name']))) {
      throw new \Valid\Exception($this->getReasonTitle() . '上傳檔案格式必須包含「tmp_name」', $this->getCode());
    }

    if (!(array_key_exists('error', $data) && is_int($data['error']))) {
      throw new \Valid\Exception($this->getReasonTitle() . '上傳檔案格式必須包含「error」', $this->getCode());
    }

    if (!(array_key_exists('size', $data) && is_int($data['size']))) {
      throw new \Valid\Exception($this->getReasonTitle() . '上傳檔案格式必須包含「size」', $this->getCode());
    }

    if ($data['error'] !== UPLOAD_ERR_OK) {
      throw new \Valid\Exception($this->getReasonTitle() . '上傳檔案錯誤', $this->getCode());
    }

    $size = $data['size'];
    $equal = $this->getEqualSize();

    if ($equal !== null) {
      if ($size != $equal) {
        throw new \Valid\Exception($this->getReasonTitle() . '檔案大小需等於「' . $equal . '」Bytes', $this->getCode());
      } else {
        return $data;
      }
    }

    $min = $this->getMinSize();
    if ($min !== null && $size < $min) {
      throw new \Valid\Exception($this->getReasonTitle() . '檔案大小需要大於等於「' . $min . '」Bytes', $this->getCode());
    }

    $max = $this->getMaxSize();
    if ($max !== null && $size > $max) {
      throw new \Valid\Exception($this->getReasonTitle() . '檔案大小需要小於等於「' . $max . '」Bytes', $this->getCode());
    }

    return $data;
  }
}
