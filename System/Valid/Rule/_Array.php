<?php

namespace Valid\Rule;

use \Valid\Rule;
use \Valid\Rule\_Any;

final class _Array extends Rule {

  use \Valid\ArraySetting;

  private static function _isIndexedArray(array $arr): bool {
    if ($arr === []) {
      return true;
    }
    return array_keys($arr) === range(0, count($arr) - 1);
  }

  private ?Rule $_rule = null;

  public function check($data) {
    parent::check($data);

    $data = $this->getData();

    if (!$this->getIsRequired() && $data === null) {
      return $this->getIfNull();
    }

    if (!is_array($data)) {
      throw new \Valid\Exception($this->getReasonTitle() . '必須是「陣列」', $this->getCode());
    }

    if (!_Array::_isIndexedArray($data)) {
      throw new \Valid\Exception($this->getReasonTitle() . '必須是「索引陣列」', $this->getCode());
    }

    $count = count($data);

    $equal = $this->getEqualCount();
    if ($equal !== null && $equal !== $count) {
      throw new \Valid\Exception($this->getReasonTitle() . '元素數量需要等於「' . $equal . '」', $this->getCode());
    }

    $min = $this->getMinCount();
    if ($min !== null && $count < $min) {
      throw new \Valid\Exception($this->getReasonTitle() . '元素數量需要大於等於「' . $min . '」', $this->getCode());
    }

    $max = $this->getMaxCount();
    if ($max !== null && $count > $max) {
      throw new \Valid\Exception($this->getReasonTitle() . '元素數量需要小於等於「' . $max . '」', $this->getCode());
    }

    $rule = $this->getRule();
    foreach ($data as $i => $val) {
      $data[$i] = $rule->setDescription($this->getReasonTitle() . '中的')->setTitle('第 ' . ($i + 1) . ' 個元素')->check($val);
    }

    $this->setData($data);
    return $data;
  }
  public function setRule(Rule $rule): self {
    $this->_rule = $rule;
    return $this;
  }
  public function getRule(): Rule {
    return $this->_rule ?? _Any::create();
  }
}
