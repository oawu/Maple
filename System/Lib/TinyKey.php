<?php

final class TinyKey {
  private static $instance = null;
  private static $instances = [];
  private static $digitals = null;
  
  public static function instance($key = null, $digitals = null) {
    if ($key === null)
      return self::$instance ?? $instance = new TinyKey(null, $digitals === null ? self::$digitals : $digitals);

    $key = preg_match('/^[0-9a-f]+$/', $key) ? $key : md5(serialize($key));
    return self::$instances[$key] ?? self::$instances[$key] = new TinyKey($key, $digitals === null ? self::$digitals : $digitals);
  }

  public static function digitals($digitals) {
    self::$digitals = $digitals;
  }
  
  private $_key = null;
  private $_map = null;
  private $_len = null;
  private $_digitals = null;

  private function __construct($key = null, $digitals) {
    $this->_key = $key;
    $this->_digitals = $digitals === null ? self::$digitals : $digitals;
    $this->_len = strlen($this->_digitals);
  }

  public function hide($number, $zero = 0) {
    $map = $this->_map();
    
    $strs = [];
    for ($i = 0; !$i || $number || count($strs) < $zero; $i++) {
      $i = $i % $this->_len;
      $j = $number % $this->_len;
      $number = floor($number / $this->_len);

      array_push($strs, $map[$i][$j]);
      $map = $this->_shift($map, $i, $j);
    }

    return implode('', $strs);
  }

  public function show($str) {
    $map = $this->_map();

    $limit = strlen($str);
    $ints = [];
    for ($i = 0; $i < $limit; $i++) {
      $j = strpos($map[$i % $this->_len], $str[$i]);
      array_push($ints, $j * pow($this->_len, $i));
      $map = $this->_shift($map, $i, $j);
    }

    return (int)array_sum($ints);
  }

  public function encode($number, $zero = 0) {
    $map = $this->_map();

    $res = '';
    $i = 0;

    do {
      $r = $number % $this->_len;
      $number = floor($number / $this->_len);
      $res = $map[$i++ % $this->_len][$r] . $res;
    } while ($number);

    // Pad
    $len = strlen($res);
    $diff = $len - $zero;

    while ($diff++ < 0 && $len++)
      $res = $map[($len - 1) % $this->_len][0] . $res;

    return $res;
  }

  public function decode($str) {
    $map = $this->_map();

    $limit = strlen($str);
    $res = 0;
    $i = $limit;

    while ($i--) {
      $res = $this->_len * $res + strpos($map[$i % $this->_len], $str[$limit - $i - 1]);
    }

    return (int)$res;
  }

  private function _shift($map, $i, $j) {
    $k = ($this->_len + $j - $i) % $this->_len;
    $map = array_map(function($row) use (&$k) { return substr($row, $k % $this->_len) . substr($row, 0, $k++ % $this->_len); }, $map);
    return $map;
  }

  private function _map() {
    if ($this->_map !== null)
      return $this->_map;

    $this->_digitals !== null || $this->_digitals = implode('', array_merge(range(0, 9), range('a', 'z'), range('A', 'Z'), ['-', '_']));

    $tmp = $this->_key !== null && ($len = strlen($this->_key))
      ? array_map(function($i) use ($len) { return $this->_key[$i % $len]; }, array_keys(array_fill(0, strlen($this->_digitals), null)))
      : array_fill(0, strlen($this->_digitals), 0);

    return $this->_map = array_map(function($i) {
      return $this->_rule($i);
    }, $tmp);
  }

  private function _rule($salt = 0) {
    $digitals = $this->_digitals;

    if (!$salt)
      return $digitals;

    $digitals = array_chunk(str_split($digitals), $salt = hexdec($salt));
    $len  = count($digitals);
    $half = floor(count($digitals) / 2);

    for ($i = 0; $i < $half && ($t = $len - $i - 1); $i += 2)
      list($digitals[$i], $digitals[$t]) = [$digitals[$t], $digitals[$i]];

    $digitals = str_split(implode('', array_map(function($dig) { return implode('', $dig); }, $digitals)));

    while ($salt--)
      array_push($digitals, array_shift($digitals));

    return implode('', $digitals);
  }
}

TinyKey::digitals(implode('', array_merge(
  range(0, 9),
  range('a', 'z'),    // 35(1), 1295(2), 46655(3), 1679615(4), 60466175(5), 2176782335(6), 78364164095(7), 2821109907455(8), 101559956668415(9), 3656158440062975(10), 
  range('A', 'Z'), // 61(1), 3843(2), 238327(3), 14776335(4), 916132831(5), 56800235583(6), 3521614606207(7), 218340105584895(8)
  ['>', '<', '{', '}', '(', ')', '.', '?', '-', '_', '$', '*', '!', '+', '@', '&', '#', '~', ':', '^', '=']
)));
