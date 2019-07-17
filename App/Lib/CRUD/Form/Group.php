<?php

namespace CRUD\Form;

class Group extends \CRUD {

  private $obj;
  private $title;
  private $str;
  private $closure;
  private $units = [];

  public static function create($title = null, $closure = null) {
    return is_callable($title) ?  new static('', $title) : new static($title, $closure);
  }

  public function __construct(string $title = null, $closure = null) {
    $this->title = $title;
    $this->closure = $closure;

    $traces = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
    
    foreach ($traces as $trace)
      if (isset($trace['object']) && $trace['object'] instanceof \CRUD\Form && method_exists($trace['object'], 'appendUnit') && $this->obj = $trace['object'])
        break;

    $this->obj && $this->obj->appendGroup($this);
  }

  public function title() {
    return $this->title;
  }

  public function hasImage(bool $hasImage = true) {
    $this->obj->hasImage($hasImage);
    return $this;
  }

  public function appendUnit(\CRUD\Form\Unit $unit) {
    array_push($this->units, $unit);
    return $this;
  }

  public function __toString() {
    if ($this->str)
      return $this->str;

    is_callable($closure = $this->closure) && $closure($this->obj->obj());
    $this->str = '';

    if (!$this->units)
      return $this->str;

    $this->str .= '<div class="panel"' . ($this->title ? ' data-title="' . $this->title . '"' : '') . '>';
      $this->str .= implode('', $this->units);
    $this->str .= '</div>';

    return $this->str;
  }
}
