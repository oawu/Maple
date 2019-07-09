<?php

class View {
  public static function create($path, $checkPath = true) {
    return new View($path, $checkPath);
  }

  private $path = null;
  private $vals = [];
  private $parent = null;
  private $checkPath = true;

  public function __construct($path, $checkPath = true) {
    $this->checkPath = $checkPath;
    $this->path($path);
  }

  public function path($path) {
    $path = realpath($path) && preg_match("/^\\" . DIRECTORY_SEPARATOR . "/", $path) ? $path : (PATH_APP_VIEW . ltrim($path, DIRECTORY_SEPARATOR));

    if (!(is_file($path) && is_readable($path)))
      !$this->checkPath ? $path = null : gg('View 的路徑錯誤！', '路徑：' . $path);

    $this->path = $path;

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
      return $t instanceof View ? $t->get() : $t;
    }, $this->vals);
  }

  public function get() {
    return View::load($this->path, $this->getVals(), true);
  }

  public function output() {
    if ($this->parent instanceof View) {
      foreach ($this->getVals() as $key => $val)
        $this->parent->with($key, $val);
      return $this->parent->output();
    } else {
      return $this->get();
    }

    return $this->parent === null ? View::load($this->path, $this->getVals()) : $this->parent->output();
  }

  private static function load($_7_maPle_7_pAtH_7_MapLE_7_, $_7_maPle_7_pArAmS_7_MapLE_7_ = [], $_7_maPle_7_return_7_MapLE_7_ = false) {
    if ($_7_maPle_7_pAtH_7_MapLE_7_ === null) {
      
      // 將 include output 存起來
      ob_start();
      echo dump($_7_maPle_7_pArAmS_7_MapLE_7_);
      $buffer = ob_get_contents();
      @ob_end_clean();
    } else {
      extract($_7_maPle_7_pArAmS_7_MapLE_7_);
      
      // 將 include output 存起來
      ob_start();
      include $_7_maPle_7_pAtH_7_MapLE_7_;
      $buffer = ob_get_contents();
      @ob_end_clean();
    }

    if ($_7_maPle_7_return_7_MapLE_7_)
      return $buffer;
    else
      echo $buffer;
  }
}

// if (!function_exists('view')) {
//   function view($path, $params = []) {
//     $view = View::create($path);
//     foreach ($params as $key => $param)
//       $view->with($key, $param);
//     return $view->get();
//   }
// }
