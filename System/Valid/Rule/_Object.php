<?php

namespace Valid\Rule;

use \Valid\Rule;
use \Valid\Rule\_Any;

final class _Object extends Rule {

  private array $_rules = [];

  public function check($data) {
    parent::check($data);

    $data = $this->getData();

    if (!$this->getIsRequired() && $data === null) {
      return $this->getIfNull();
    }

    if (!is_array($data)) {
      throw new \Valid\Exception($this->getReasonTitle() . '必須是「陣列」', $this->getCode());
    }

    $rules = $this->getRules();
    $_data = [];
    foreach ($rules as $key => $rule) {
      $rule = $rule->setDescription($this->getReasonTitle() . '中的');
      $title = $rule->getTitle($key);
      if ($title === '') {
        $title = $key;
        $rule->setTitle($title);
      }

      if (array_key_exists($key, $data)) {
        $_data[$key] = $rule->check($data[$key]);
        continue;
      }

      $required = $rule->getIsRequired();

      if ($required) {
        throw new \Valid\Exception($this->getReasonTitle() . '缺少「' . $title . '」', $this->getCode());
      }

      $ifNoKey = $rule->getIfNoKey();

      if ($ifNoKey !== null) {
        $_data[$key] = $ifNoKey['val'];
      }
    }

    $this->setData($_data);
    return $_data;
  }
  public function setRules(array $rules): self {
    $this->_rules = $rules;
    return $this;
  }
  public function getRules(): array {
    return $this->_rules;
  }
}
