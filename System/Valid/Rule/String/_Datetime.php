<?php

namespace Valid\Rule\String;

use \Valid\Rule\_String as _String;

final class _Datetime extends _String {

  public function check($data) {
    parent::check($data);

    $data = $this->getData();

    if (\DateTime::createFromFormat('Y-m-d H:i:s', $data) === false) {
      throw new \Valid\Exception($this->getReasonTitle() . '必須是「yyyy-MM-dd HH:mm:ss」的「Datetime」格式', $this->getCode());
    }

    return $data;
  }
}
