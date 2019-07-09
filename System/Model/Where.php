<?php

class Where {
  private $str = '';
  private $vals = [];

  protected function __construct($str, $vals) {

    $i = 0;
    $str = preg_replace_callback('/(?P<key>\(\s*\?\s*\)|\?)/', function($m) use (&$i, &$vals) {
      if ($m['key'] == '?') {
        $i++;
        return '?';
      }

      $val = $vals[$i];
      is_array($val) || gg('Where 條件錯誤，(?) 相對應的參數必須為陣列！');

      $c = count($vals[$i]);
      $vals[$i] = $c ? $vals[$i] : null;
      $i++;
      return '(' . ($c ? implode(',', array_fill(0, $c, '?')) : '?') . ')';
    }, $str);

    $vals = arrayFlatten($vals);
    $count = substr_count($str, '?');
    $count <= count($vals) || gg('Where 條件錯誤，「' . $str . '」 有 ' . count($vals) . ' 個參數，目前只給 ' . $count . ' 個。');

    $this->str = $str;
    $this->vals = array_slice($vals, 0, $count);
  }

  public function getStr() {
    return $this->str;
  }

  public function getVals() {
    return $this->vals;
  }

  public function and() {
    if (!$args = func_get_args())
      return $this;

    if (is_string($args[0]))
      return $this->and(Where::create($args));
    
    if (is_array($args[0]))
      return $this->and(Where::create($args[0]));

    if ($args[0] instanceof Where) {
      $args = array_shift($args);

      if (!$tmps = array_filter([$this->getStr(), $args->getStr()], function($str) { return $str !== null && $str !== ''; }))
        return $this;

      $this->str = count($tmps) == 1 ? implode('', $tmps) : implode(' AND ', array_map(function($tmp) { return '(' . $tmp . ')'; }, $tmps));
      $this->vals = array_merge($this->getVals(), $args->getVals());
      return $this;
    }

    return $this;
  }

  public function or() {
    if (!$args = func_get_args())
      return $this;

    if (is_string($args[0]))
      return $this->or(Where::create($args));
    
    if (is_array($args[0]))
      return $this->or(Where::create($args[0]));

    if ($args[0] instanceof Where) {
      $args = array_shift($args);

      if (!$tmps = array_filter([$this->getStr(), $args->getStr()], function($str) { return $str !== null && $str !== ''; }))
        return $this;

      $this->str = count($tmps) == 1 ? implode('', $tmps) : implode(' OR ', array_map(function($tmp) { return '(' . $tmp . ')'; }, $tmps));
      $this->vals = array_merge($this->getVals(), $args->getVals());
      return $this;
    }

    return $this;
  }

  public static function create($str = '') {
    $args = is_array($str) ? $str : func_get_args();
    $str = array_shift($args);
    
    return new Where($str === null ? '' : $str, $args);
  }
}