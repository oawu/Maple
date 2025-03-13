<?php

namespace Valid\Rule;

use \Valid\Rule;

abstract class _Num extends Rule {
  public function check($data) {
    parent::check($data);

    $data = $this->getData();

    if (!$this->getIsRequired() && $data === null) {
      return $this->getIfNull();
    }

    if (!$this->getIsStrict() && is_string($data)) {
      $data = 1 * $data;
      $this->setData($data);
    }

    if (!is_numeric($data)) {
      throw new \Valid\Exception($this->getReasonTitle() . '必須是「數字」格式', $this->getCode());
    }

    return $data;
  }
}
