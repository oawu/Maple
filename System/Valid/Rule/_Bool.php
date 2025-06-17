<?php

namespace Valid\Rule;

use \Valid\Rule;

final class _Bool extends Rule {

  use \Valid\BoolSetting;

  public function check($data) {
    parent::check($data);

    $data = $this->getData();

    if (!$this->getIsRequired() && $data === null) {
      return $this->getIfNull();
    }

    if (!$this->getIsStrict() && is_object($data) && method_exists($data, '__toString')) {
      $data = $data->__toString();
      $this->setData($data);
    }

    if (!$this->getIsStrict() && is_string($data) && (strtolower($data) === 'true' || strtolower($data) === 'false' || $data === '1' || $data === '0')) {
      $data = $data === 'true' || $data === '1';
      $this->setData($data);
    }

    if (!is_bool($data)) {
      throw new \Valid\Exception($this->getReasonTitle() . '必須是「布林值」格式', $this->getCode());
    }

    $equal = $this->getEqual();
    if ($equal !== null && $data !== $equal) {
      throw new \Valid\Exception($this->getReasonTitle() . '需要等於「' . ($equal ? 'true' : 'false') . '」', $this->getCode());
    }

    return $data;
  }
}
