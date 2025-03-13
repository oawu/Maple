<?php

namespace Valid\Rule;

use \Valid\Rule;

final class _Enum extends Rule {

  private array $_items = [];

  public function check($data) {
    parent::check($data);

    $data = $this->getData();

    if (!$this->getIsRequired() && $data === null) {
      return $this->getIfNull();
    }

    $items = $this->getItems();
    $isSame = false;
    if ($this->getIsStrict()) {
      foreach ($items as $item) {
        if ($item === $data) {
          $isSame = true;
          break;
        }
      }
    } else {
      foreach ($items as $item) {
        if ($item == $data) {
          $isSame = true;
          $data = $item;
          $this->setData($data);
          break;
        }
      }
    }

    if (!$isSame) {
      throw new \Valid\Exception($this->getReasonTitle() . '必須是「' . implode(', ', $items) . '」其中一個', $this->getCode());
    }

    return $data;
  }
  public function setItems(array $items): self {
    $this->_items = $items;
    return $this;
  }
  public function items(array $items): self {
    return $this->setItems($items);
  }
  public function in(array $items): self {
    return $this->setItems($items);
  }
  public function getItems(): array {
    return $this->_items;
  }
}
