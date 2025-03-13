<?php

namespace Valid\Rule\String;

use \Valid\Rule\_String as _String;

final class _Date extends _String {

  public function check($data) {
    parent::check($data);

    $data = $this->getData();

    if (\DateTime::createFromFormat('Y-m-d', $data) === false) {
      throw new \Valid\Exception($this->getReasonTitle() . '必須是「yyyy-MM-dd」的「Date」格式', $this->getCode());
    }

    return $data;
  }
}
