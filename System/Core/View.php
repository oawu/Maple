<?php

class View {
  private $vals = [];
  private $parent = null;
  private $path = null;

  public function __construct($path) {
    $this->path($path);
  }

  public function path($path = null) {
    if ($path === null)
      return $this->path;

    $ext  = pathinfo($path, PATHINFO_EXTENSION) == '' ? '.php' : '';
    $path = $path . $ext;

    $path = !realpath($path) || !preg_match("/^\\" . DIRECTORY_SEPARATOR . "/", $path)
        ? PATH_APP_VIEW . ltrim($path, DIRECTORY_SEPARATOR)
        : $path;

    $this->path = View::isValid($path) !== false
      ? $path
      : null;

    return $this;
  }

  public function with($key, $val) {
    $this->vals[$key] = $val;
    return $this;
  }

  public function withReference($key, &$val) {
    $this->vals[$key] = &$val;
    return $this;
  }

  public function appendTo(View $parent, $key) {
    $this->parent = $parent->with($key, $this);
    return $this;
  }

  public function getVals() {
    return array_map(function($t) {
      return $t instanceof View ? '' . $t : $t;
    }, $this->vals);
  }

  public function output() {
    if (!($this->parent instanceof View))
      return '' . $this;
    
    foreach ($this->getVals() as $key => $val)
      $this->parent->with($key, $val);

    return $this->parent->output();
  }

  public function __toString() {
    return View::load($this->path, $this->getVals(), true);
  }

  public static function load($___maple8____path___, $___maple8____params___ = [], $___maple8____isreturn___ = false) {
    ob_start();
    if ($___maple8____path___ === null) {
      echo dump($___maple8____params___);
      $buffer = ob_get_contents();
    } else {
      extract($___maple8____params___);
      include $___maple8____path___;
      $buffer = ob_get_contents();
    }
    @ob_end_clean();

    if ($___maple8____isreturn___)
      return $buffer;
    else
      echo $buffer;
  }

  public static function isValid($path) {
    return is_file($path) && is_readable($path) ? $path : false;
  }

  public static function create(string $path) {
    return new View($path);
  }
}
