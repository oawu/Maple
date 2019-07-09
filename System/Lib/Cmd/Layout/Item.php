<?php

namespace CMD\Layout;

use CMD\Str      as Str;
use CMD\Display  as Display;

abstract class Item {
  protected $title,
            $subtitle,
            $subitems = [],
            $parent,
            $isHover = false,
            $isChoed = false;

  public function __construct($title, $subtitle) {
    $this->setTitle($title)
         ->setSubtitle($subtitle);
  }
  
  public function title() {
    return $this->title;
  }

  public function subtitle() {
    return $this->subtitle;
  }

  public function titleWidth() {
    return array_sum(array_map(function($t) {
      return strlen($t) == 3 ? 2 : 1;
    }, Str::split($this->title)));
  }

  public function subtitleWidth() {
    return array_sum(array_map(function($t) {
      return strlen($t) == 3 ? 2 : 1;
    }, Str::split($this->subtitle)));
  }

  public function titleLen() {
    return array_sum(array_map('strlen', Str::split($this->title)));
  }

  public function subtitleLen() {
    return array_sum(array_map('strlen', Str::split($this->subtitle)));
  }

  public function setTitle(string $title) {
    $this->title = $title;
    return $this;
  }

  public function setSubtitle(string $subtitle) {
    $this->subtitle = $subtitle;
    return $this;
  }

  public function setParent(Menu $parent) {
    $this->parent = $parent;
    return $this;
  }

  public function isChoed(bool $isChoed) {
    $this->isChoed = $isChoed;
    return $this;
  }

  public function isHover(bool $isHover) {
    $this->isHover = $isHover;
    return $this;
  }

  public function back() {
    return $this->parent ? $this->parent->choice($this->index() + 1) : null;
  }

  public function index() {
    if (!$this->parent)
      return 0;
    return $this->parent->itemIndex($this);
  }

  public function subitems() {
    return is_callable($this->subitems) ? call_user_func_array($this->subitems, []) : $this->subitems;
  }

  public function setSubitems($subitems) {
    $this->subitems = $subitems;
    return $this;
  }

  public function __toString() {
    if (!$this->parent)
      return '';
    $itemsTitleMaxWidth = $this->parent->itemsTitleMaxWidth();
    $spaceCount  = $itemsTitleMaxWidth + ($this->titleLen() - $this->titleWidth());
    $repeatSpace = Str::repeat(Display::MAX_LEN - 7 - $itemsTitleMaxWidth - 4 - $this->subtitleLen() - 3);

    $arrow    = $this->isHover ? Str::repeat() . Display::markArrow()->dim($this->isChoed) . Str::repeat(2) : Str::repeat(4);
    $num      = \Xterm::gray(($this->index() + 1) . '.', $this->isHover);
    $title    = \Xterm::gray(sprintf('%-' . $spaceCount . 's', $this->title), $this->isHover);
    $split    = \Xterm::create(' ─ ')->color($this->isHover ? null : \Xterm::L_BLACK)->dim();
    $subtitle = \Xterm::create($this->subtitle)->dim(!$this->isHover);

    return Str::repeat() . Display::markBorder6() . $arrow . $num . Str::repeat() . $title . $split . $subtitle . $repeatSpace . Display::markBorder6();
  }

  public static function create($title, $subtitle) {
    return new static($title, $subtitle);
  }

  public static function show(&$cho, Menu $menu, bool $isChoed = false) {
    system('clear');

    Display::logo();

    $cho <= count($menu->items()) || $cho = 1;
    $cho >= 1 || $cho = count($menu->items());

    $families = $menu->families();
    $repeatSpace = Str::repeat(Display::MAX_LEN - ((count($families) - 1) * 2 + array_sum(array_map(function($family) { return $family->titleWidth(); }, $families))) - 7);

    echo Str::repeat() . Display::markBorder6() . Str::repeat(3) . implode(\Xterm::black('﹥', true), array_map(function($family) use ($menu) { return \Xterm::create($family->title())->color($family == $menu ? \Xterm::YELLOW : null); }, $families)) . $repeatSpace . Display::markBorder6() . Display::LN;
    echo Str::repeat() . Display::markBorder7() . Str::repeat(Display::MAX_LEN - 4, Display::markBorder5()) . Display::markBorder8() . Display::LN;

    if ($subitems = $menu->subitems()) {
      echo implode('', array_map(function($subitem) { return $subitem . Display::LN; }, $menu->subitems()));
      echo Str::repeat() . Display::markBorder7() . Str::repeat(Display::MAX_LEN - 4, Display::markBorder5()) . Display::markBorder8() . Display::LN;
    }

    foreach ($menu->items() as $i => $item) {
      echo $item->isHover(++$i == $cho)->isChoed($isChoed) . Display::LN;
    }

    $footer = '[←]離開/上一頁   [→]進入/選擇   [↑]上移   [↓]下移';
    
    $len = Str::width($footer);
    $rs = Str::repeat(Display::MAX_LEN - $len - 7);

    echo Str::repeat() . Display::markBorder7() . Str::repeat(Display::MAX_LEN - 4, Display::markBorder5()) . Display::markBorder8() . Display::LN;
    echo Str::repeat() . Display::markBorder6() . Str::repeat(3) . $footer . $rs . Display::markBorder6() . Display::LN;
    echo Str::repeat() . Display::markBorder2() . Str::repeat(Display::MAX_LEN - 4, Display::markBorder5()) . Display::markBorder4() . Display::LN;
  }
}