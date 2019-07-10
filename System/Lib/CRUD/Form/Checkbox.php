<?php

namespace CRUD\Form;

class Checkbox extends \CRUD\Form\Unit\Items {
  private $val = [];

  public function val(array $val) {
    $this->val = $val;
    return $this;
  }

  public static function inArray($var, $arr) {
    foreach ($arr as $val)
      if (($var === 0 ? '0' : $var) == $val)
        return true;
    return false;
  }

  protected function getContent() {
    $value = \CRUD\Form::$flash !== null ? isset(\CRUD\Form::$flash[$this->name]) ? \CRUD\Form::$flash[$this->name] : [] : $this->val;
    is_array($value) || $value = [];

    $return = '';

    $return .= '<div class="checkboxs">';
    $return .= implode('', array_map(function($item) use ($value) {
      $return = '';
      $return .= '<label>';
        $return .= '<input type="checkbox" value="' . $item['value'] . '" name="' . $this->name . '[]"' . (Checkbox::inArray($item['value'], $value) ? ' checked' : '') . '/>';
        $return .= '<span></span>';
        $return .= $item['text'];
      $return .= '</label>';
      return $return;
    }, $this->items));
    $return .= '</div>';

    return $return;
  }
}