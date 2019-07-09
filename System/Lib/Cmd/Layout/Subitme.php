<?php

namespace CMD\Layout;

use CMD\Str      as Str;
use CMD\Display  as Display;

class Subitme {
  protected $index,
            $select,
            $title,
            $subtitle;

  public function __construct(bool $select, int $index, string $title, string $subtitle) {
    $this->setSelect($select)
         ->setIndex($index)
         ->setTitle($title)
         ->setSubtitle($subtitle);
  }

  public function setSelect(bool $select) {
    $this->select = $select;
    return $this;
  }

  public function setIndex(int $index) {
    $this->index = $index;
    return $this;
  }

  public function setTitle(string $title) {
    $this->title = $title;
    return $this;
  }

  public function setSubtitle(string $subtitle) {
    $this->subtitle = $subtitle;
    return $this;
  }

  public static function create(bool $select, int $index, string $title, string $subtitle) {
    return new static($select, $index, $title, $subtitle);
  }

  public function __toString() {
    $titleLen = Display::MAX_LEN - 19 - 12 - 4;
    $mark     = \Xterm::create($this->select ? '▣' : '□')->dim(!$this->select);
    $title    = \Xterm::gray(sprintf('%4d. ', $this->index) . sprintf('%-' . $titleLen . 's', Str::docx3($this->title, $titleLen)), $this->select)->dim(!$this->select);
    $subtitle = \Xterm::create($this->subtitle)->color($this->select ? \Xterm::L_GRAY : \Xterm::L_BLACK)->dim();
    return Str::repeat() . Display::markBorder6() . Str::repeat() . $mark . $title . Str::repeat(3) . $subtitle . Str::repeat() . Display::markBorder6();
  }
}
