<?php

namespace Valid\Rule\Num;

use \Valid\Rule\_Num as Num;

class _Int extends Num {

  use \Valid\IntRangeSetting;

  public function check($data) {
    parent::check($data);

    $data = $this->getData();

    if (!$this->getIsRequired() && $data === null) {
      return $this->getIfNull();
    }

    if (!is_int($data)) {
      throw new \Valid\Exception($this->getReasonTitle() . '必須為整數', $this->getCode());
    }

    $equal = $this->getEqual();
    if ($equal !== null) {
      if ($data !== $equal) {
        throw new \Valid\Exception($this->getReasonTitle() . '需要等於「' . $equal . '」', $this->getCode());
      } else {
        $this->setData($data);
        return $data;
      }
    }

    $min = $this->getMin();
    if ($min !== null && $data < $min) {
      throw new \Valid\Exception($this->getReasonTitle() . '需要大於等於「' . $min . '」', $this->getCode());
    }

    $max = $this->getMax();
    if ($max !== null && $data > $max) {
      throw new \Valid\Exception($this->getReasonTitle() . '需要小於等於「' . $max . '」', $this->getCode());
    }

    return $data;
  }
}
