<?php

namespace Valid\Rule\String;

use \Valid\Rule\_String as _String;

final class _Email extends _String {

  public function check($data) {
    parent::check($data);

    $data = $this->getData();

    if (!$this->getIsRequired() && $data === null) {
      return $this->getIfNull();
    }

    if (!filter_var($data, FILTER_VALIDATE_EMAIL)) {
      throw new \Valid\Exception($this->getReasonTitle() . '必須是「Email」格式', $this->getCode());
    }

    return $data;
  }
}
