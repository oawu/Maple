<?php

namespace Valid\Rule\String;

use \Valid\Rule\_String as _String;

final class _Time extends _String {

  public function check($data) {
    parent::check($data);

    $data = $this->getData();

    if (\DateTime::createFromFormat('H:i:s', $data) === false) {
      throw new \Valid\Exception($this->getReasonTitle() . '必須是「HH:mm:ss」的「Time」格式', $this->getCode());
    }

    return $data;
  }
}
