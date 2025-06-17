<?php

namespace Valid\Rule;

use \Valid\Rule;

class _String extends Rule {

  use \Valid\StringLengthSetting;

  public function check($data) {
    parent::check($data);

    $data = $this->getData();

    if (!$this->getIsRequired() && $data === null) {
      return $this->getIfNull();
    }

    if (!$this->getIsStrict() && is_numeric($data)) {
      $data = '' . $data;
      $this->setData($data);
    }

    if (!$this->getIsStrict() && is_object($data) && method_exists($data, '__toString')) {
      $data = $data->__toString();
      $this->setData($data);
    }


    if (!is_string($data)) {
      throw new \Valid\Exception($this->getReasonTitle() . '必須是「字串」格式', $this->getCode());
    }

    $equal = $this->getEqual();
    if ($equal !== null) {
      if ($data !== $equal) {
        throw new \Valid\Exception($this->getReasonTitle() . '需要等於「' . $equal . '」', $this->getCode());
      } else {
        return $data;
      }
    }

    $length = mb_strlen($data);

    $min = $this->getMinLength();
    if ($min !== null && $length < $min) {
      throw new \Valid\Exception($this->getReasonTitle() . '長度需要大於等於「' . $min . '」', $this->getCode());
    }

    $max = $this->getMaxLength();
    if ($max !== null && $length > $max) {
      throw new \Valid\Exception($this->getReasonTitle() . '長度需要小於等於「' . $max . '」', $this->getCode());
    }

    return $data;
  }
}
