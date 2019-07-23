<?php

namespace HTML;

abstract class DomElement {
  private static $SingletonTags = ['area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'];

  private $tag;
  protected $text, $attrs = [], $datas = [];

  public function __construct($text = '') {
    $this->tag = strtolower(deNamespace(get_called_class()));
    $this->text($text);
  }

  public function text($text) {
    $this->text = $text;
    return $this;
  }

  public function data($key, $val) {
    if ($val === null) return $this;
    is_array($val) && $val = json_encode($val);
    $this->datas[$key] = $val;
    return $this;
  }

  public function __toString() {
    if ($this->tag === null)
      return '';

    foreach ($this->datas as $key => $value)
      $this->attrs['data-' . $key] = $value;

    if (in_array($this->tag, DomElement::$SingletonTags))
      return '<' . $this->tag . attr($this->attrs) . '/>';

    return '<' . $this->tag . attr($this->attrs) . '>' . (is_array($this->text) ? implode('', $this->text) : $this->text) . '</' . $this->tag . '>';
  }

  public static function create($text = '') {
    return new static($text);
  }

  public function __call($name, $arguments) {
    $this->attrs[$name] = array_shift($arguments);
    return $this;
  }
}

class A extends DomElement {}
class B extends DomElement {}
class I extends DomElement {}
class H1 extends DomElement {}
class Div extends DomElement {}
class Span extends DomElement {}
class Figure extends DomElement {}
class Pre extends DomElement {}
class Del extends DomElement {}
class Option extends DomElement {}
class Select extends DomElement {}
class Label extends DomElement {}