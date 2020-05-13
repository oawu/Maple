<?php

Load::systemFunc('Format');

final class Validator {
  public static function must(&$params, $key, $title = null, $code = 400, $errorMsg = null) {
    return new static(true, $params, $key, $title, $code, $errorMsg);
  }

  public static function optional(&$params, $key, $title = null, $code = 400, $errorMsg = null) {
    return new Validator(false, $params, $key, $title, $code, $errorMsg);
  }

  private $code = 400, $errorMsg = null, $mustCheck = null, $key = null, $params = null, $title = null;

  private function __construct($mustCheck, &$params, $key, $title = null, $code = 400, $errorMsg = null) {
    $this->code($code)->errorMsg($errorMsg)->setMustCheck($mustCheck)->setParams($params)->setKey($key)->setTitle($title ?? $key);

    if ($this->mustCheck)
      array_key_exists($this->key, $params) || $this->error('需必填！');
    else
      array_key_exists($this->key, $params) && $this->setMustCheck(true);
  }

  private function error($message) { return error($this->code, $this->errorMsg ?? '「' . $this->title . '」' . $message . '！'); }

  public function code($code) { $this->code = $code; return $this; }
  public function errorMsg($errorMsg) { $this->errorMsg = $errorMsg; return $this; }
  public function setMustCheck($mustCheck) { $this->mustCheck = $mustCheck; return $this; }
  public function setParams(&$params) { $this->params =& $params; return $this; }
  public function setKey($key) { $this->key = $key; return $this; }
  public function setTitle($title) { $this->title = $title; return $this; }
  
  public function default($default) { if ($this->mustCheck || array_key_exists($this->key, $this->params)) return $this; $this->params[$this->key] = $default; return $this->setMustCheck(false); }
  public function isStr() { $this->mustCheck && !is_string($this->params[$this->key]) && $this->error('必須是「字串」格式'); return $this; }
  public function strTrim($mask = " \t\n\r\0\x0B") { $this->mustCheck && $this->params[$this->key] = trim($this->params[$this->key], $mask); return $this; }
  public function allowableTags($allowableTags = null) { $this->mustCheck && $this->isStr() && $this->strStripTags($allowableTags); return $this; }
  public function strStripTags($allowableTags = null) { if ($this->mustCheck && $allowableTags !== false) $this->params[$this->key] = $allowableTags !== null ? strip_tags($this->params[$this->key], $allowableTags) : strip_tags($this->params[$this->key]); return $this; }
  public function strMinLength($lenght = null) { $this->mustCheck && isset($lenght) && $lenght >= 0 && mb_strlen($this->params[$this->key]) < $lenght && $this->error('長度最短需要 ' . $lenght . ' 個字'); return $this; }
  public function strMaxLength($lenght = null) { $this->mustCheck && isset($lenght) && $lenght >= 0 && mb_strlen($this->params[$this->key]) > $lenght && $this->error('長度最長只能 ' . $lenght . ' 個字'); return $this; }
  public function isNum() { $this->mustCheck && !is_numeric($this->params[$this->key]) && $this->error('必須是「數字」格式'); $this->params[$this->key] += 0; return $this; }
  public function isInt() { $this->mustCheck && $this->isNum() && !is_int($this->params[$this->key]) && $this->error('必須是「整數」格式'); return $this; }
  public function lessEqual($num = null) { $this->mustCheck && isset($num) && $this->params[$this->key] > $num && $this->error('需要小於等於「' . $num . '」'); return $this; }
  public function less($num = null) { $this->mustCheck && isset($num) && $this->params[$this->key] >= $num && $this->error('需要小於「' . $num . '」'); return $this; }
  public function greaterEqual($num = null) { $this->mustCheck && isset($num) && $this->params[$this->key] < $num && $this->error('需要大於等於「' . $num . '」'); return $this; }
  public function greater($num = null) { $this->mustCheck && isset($num) && $this->params[$this->key] <= $num && $this->error('需要大於「' . $num . '」'); return $this; }
  public function isArr() { $this->mustCheck && !is_array($this->params[$this->key]) && $this->error('必須是「陣列」格式'); return $this; }
  public function arrMinLength($lenght = null) { $this->mustCheck && isset($lenght) && count($this->params[$this->key]) < $lenght && $this->error('最少需要「' . $lenght . '」個'); return $this; }
  public function arrMaxLength($lenght = null) { $this->mustCheck && isset($lenght) && count($this->params[$this->key]) > $lenght && $this->error('最多只能「' . $lenght . '」個'); return $this; }
  public function map($closure = null) { $this->mustCheck && is_callable($closure) && $this->params[$this->key] = array_map($closure, $this->params[$this->key]); return $this; }
  public function filter($closure = null) {
    if (!$this->mustCheck)
      return $this;

    isset($closure) || $this->params[$this->key] = array_filter($this->params[$this->key]);
    is_callable($closure) && $this->params[$this->key] = array_filter($this->params[$this->key], function($e) use ($closure) { try { return $closure($e); } catch (MapleException $e) { return null; } });
    is_array($closure) && $this->params[$this->key] = array_filter($this->params[$this->key], function($e) use ($closure) { foreach ($closure as $enum) if ($enum === $e)  return true; return false; });
    
    return $this;
  }

  public function sizeMin($size = null) { $this->mustCheck && isset($size) && $this->params[$this->key]['size'] < $size && $this->error('檔案最少需要「' . implode(' ', memoryUnit($size)) . '」'); return $this; }
  public function sizeMax($size = null) { $this->mustCheck && isset($size) && $this->params[$this->key]['size'] > $size && $this->error('檔案最大只能「' . implode(' ', memoryUnit($size)) . '」'); return $this; }
  public function formatFilter($formats = []) { $this->mustCheck && $formats && !uploadFileInFormats($this->params[$this->key], $formats) && $this->error('檔案格式不符合'); return $this; }
  public function isString($minLength = 0, $maxLength = null) { return $this->mustCheck ? $this->isStr()->strTrim()->strStripTags()->strMinLength($minLength)->strMaxLength($maxLength) : $this; }
  public function isNumber($min = null, $max = null) { return $this->mustCheck ? $this->isNum()->greaterEqual($min)->lessEqual($max) : $this; }
  public function isInteger($min = null, $max = null) { return $this->mustCheck ? $this->isInt()->greaterEqual($min)->lessEqual($max) : $this; }
  public function isArray($minLength = 0, $maxLength = null) { return $this->mustCheck ? $this->isArr()->arrMinLength($minLength)->arrMaxLength($maxLength) : $this; }
  public function isObject() { $this->mustCheck && !($this->isArray() && isAssoc($this->params[$this->key])) && $this->error('必須是「物件」格式'); return $this; }
  public function isDate() { $this->mustCheck && $this->isString(10, 10) && !isDate($this->params[$this->key]) && $this->error('必須是「Date」格式'); return $this; }
  public function isDatetime() { $this->mustCheck && $this->isString(19, 19) && !isDatetime($this->params[$this->key]) && $this->error('必須是「Datetime」格式'); return $this; }
  public function isURL() { $this->mustCheck && $this->isString() && !isURL($this->params[$this->key]) && $this->error('必須是「網址(http、https)」格式'); return $this; }
  public function isEmail() { $this->mustCheck && $this->isString() && !isEmail($this->params[$this->key]) && $this->error('必須是「E-mail」格式'); return $this; }
  public function isId() { return $this->mustCheck ? $this->isInteger(0) : $this; }
  public function isVarchar($minLength = 0, $maxLength = null) { return $this->mustCheck ? $this->isString($minLength, $maxLength) : $this; }
  public function isLat() { return $this->mustCheck ? $this->isNumber(-90, 90) : $this; }
  public function isLng() { return $this->mustCheck ? $this->isNumber(-180, 180) : $this; }

  public function inEnum(array $enums = []) {
    if (!($this->mustCheck && $enums))
      return $this;

    $this->isString();

    foreach ($enums as $enum)
      if ($enum === $this->params[$this->key])
        return $this;

    $this->error('不存在允許的選項內！');
  }

  public function isUpload($sizeMin = 0, $sizeMax = null) {
    return $this->mustCheck
      ? $this->isArray(5, 5) && isUploadFile($this->params[$this->key])
        ? $this->sizeMin($sizeMin)->sizeMax($sizeMax)
        : $this->error('必須是「上傳檔案」的格式')
      : $this;
  }
}
