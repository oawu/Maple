<?php

namespace HTML;

abstract class DomElement {
  private static $SingletonTags = ['area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'];

  private $tag;
  protected $text, $attrs = [];
  
  public function __construct($text = '', $attrs = []) {
    $this->tag = strtolower(deNamespace(get_called_class()));
    $this->text($text);
    $this->attrs($attrs);
  }
  
  public function text($text) {
    $this->text = $text;
    return $this;
  }
  public function attrs($attrs, $excludes = []) {
    if (is_string($attrs)) {
      $this->attrs[$attrs] = $excludes;
      return $this;
    }
    $tmps = [];
    foreach ($attrs as $key => $attr)
      if (!($excludes && in_array($key, $excludes)))
        $tmps[$key] = $attr;
    $this->attrs = $tmps;
    return $this;
  }
  
  public function __toString() {
    if ($this->tag === null)
      return '';

    if (in_array($this->tag, DomElement::$SingletonTags))
      return '<' . $this->tag . attr($this->attrs) . '/>';

    return '<' . $this->tag . attr($this->attrs) . '>' . (is_array($this->text) ? implode('', $this->text) : $this->text) . '</' . $this->tag . '>';
  }

  public static function create($text = '', $attrs = []) {
    return new static($text, $attrs);
  }
  public function __call($name, $arguments) {
    $this->attrs[$name] = array_shift($arguments);
    return $this;
  }
}

class A extends DomElement { public function target($target) { $this->attrs['target'] = $target; return $this; } }
class B extends DomElement {}
class Div extends DomElement {}
class Span extends DomElement {}
class Figure extends DomElement {}
class Pre extends DomElement {}
