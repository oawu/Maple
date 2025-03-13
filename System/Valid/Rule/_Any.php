<?php

namespace Valid\Rule;

use \Valid\Rule;

final class _Any extends Rule {
  public function check($data) {
    parent::check($data);

    $data = $this->getData();

    if (!$this->getIsRequired() && $data === null) {
      return $this->getIfNull();
    }

    return $data;
  }
}
