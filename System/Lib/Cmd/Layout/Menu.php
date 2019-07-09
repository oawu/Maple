<?php

namespace CMD\Layout;

use CMD\Str      as Str;
use CMD\Display  as Display;
use CMD\Keyboard as Keyboard;

class Menu extends Item {
  private $items = [];

  public function __construct($title, $subtitle) {
    parent::__construct($title, $subtitle);
  }

  public function items() {
    return $this->items;
  }

  public function appendItem(Item $item = null) {
    if (!$item) return $this;
    $item->setParent($this);
    array_push($this->items, $item);
    return $this;
  }

  public function appendItems(array $items) {
    $items = array_filter($items, function($item) {
      return $item instanceof Item;
    });

    foreach ($items as $item)
      $this->appendItem($item);

    return $this;
  }

  public function itemsTitleMaxWidth() {
    return max(array_map(function($item) {
      return $item->titleWidth();
    }, $this->items));
  }

  public function families() {
    if (!$this->parent) return [$this];
    else return array_merge($this->parent->families(), [$this]);
  }

  public function itemIndex(Item $item) {
    foreach ($this->items as $i => $tmp)
      if ($tmp === $item)
        return $i;
    return 0;
  }

  public function choice(int $cho = 1) {
    Item::show($cho, $this);

    \CMD\Keyboard::listener(function($codes, $keyboard) use (&$cho) {
      if (!in_array($codes = implode(',', $codes), ['27,91,65', '27,91,66', '27,91,67', '27,91,68']))
        return;

      switch ($codes) {
        default:
        case '27,91,68':
          $cho = 0;
        case '27,91,67':
          return $keyboard->stop();
          break;
        
        case '27,91,65': --$cho; break;
        case '27,91,66': ++$cho; break;
      }

      Item::show($cho, $this);
    })->run();

    if (!isset($this->items[$cho - 1]))
      return $this->back();

    return $this->items[$cho - 1]->choice();
  }
}
