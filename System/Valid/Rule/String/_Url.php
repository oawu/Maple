<?php

namespace Valid\Rule\String;

use \Valid\Rule\_String as _String;

final class _Url extends _String {

  public function check($data) {
    parent::check($data);

    $data = $this->getData();

    if (!filter_var($data, FILTER_VALIDATE_URL)) {
      throw new \Valid\Exception($this->getReasonTitle() . '必須是「網址」格式', $this->getCode());
    }

    return $data;
  }
}
