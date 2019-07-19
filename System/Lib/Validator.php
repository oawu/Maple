<?php

Load::systemFunc('Format.php');

class Validator {
  public static function must(Array &$params, String $key, String $title = null, int $code = 400) {
    return new Validator(true, $params, $key, $title, $code);
  }

  public static function optional(Array &$params, String $key, String $title = null, int $code = 400) {
    return new Validator(false, $params, $key, $title, $code);
  }

  private $code = 400,
          $mustCheck = null,
          $key = null,
          $params = null,
          $title = null;

  public function __construct(bool $mustCheck, Array &$params, String $key, String $title = null, int $code = 400) {

    $this->code($code)
         ->setMustCheck($mustCheck)
         ->setParams($params)
         ->setKey($key)
         ->setTitle($title ?? $key);

    if ($this->mustCheck)
      array_key_exists($this->key, $params)
        || $this->error('需必填！');
    else
      array_key_exists($this->key, $params)
        && $this->setMustCheck(true);
  }

  public function code(int $code) {
    $this->code  = $code; 
    return $this;
  }

  public function setMustCheck(bool $mustCheck) {
    $this->mustCheck = $mustCheck;
    return $this;
  }
  
  public function setParams(Array &$params) {
    $this->params =& $params;
    return $this;
  }
  
  public function setKey(String $key) {
    $this->key = $key;
    return $this;
  }
  
  public function setTitle(String $title) {
    $this->title = $title;
    return $this;
  }
  
  public function default($default) {
    if ($this->mustCheck)
      return $this;

    if (array_key_exists($this->key, $this->params))
      return $this;

    $this->params[$this->key] = $default;
    return $this->setMustCheck(false);
  }

  private function error($message) {
    return error($this->code, '「' . $this->title . '」' . $message . '！');
  }

  public function isStr() {
    return $this->mustCheck && !is_string($this->params[$this->key])
      ? $this->error('必須是「字串」格式')
      : $this;
  }

  public function strTrim($mask = " \t\n\r\0\x0B") {
    if ($this->mustCheck)
      $this->params[$this->key] = trim($this->params[$this->key], $mask);
    return $this;
  }

  public function allowableTags($allowableTags = null) {
    $this->mustCheck
      ? $this->isStr() && $this->strStripTags($allowableTags)
      : $this;
    return $this;
  }

  public function strStripTags($allowableTags = null) {
    if ($this->mustCheck && $allowableTags !== false)
      $this->params[$this->key] = $allowableTags !== null
        ? strip_tags($this->params[$this->key], $allowableTags)
        : strip_tags($this->params[$this->key]);
    return $this;
  }

  public function strMinLength(int $lenght = null) {
    return $this->mustCheck && isset($lenght) && $lenght >= 0 && mb_strlen($this->params[$this->key]) < $lenght
      ? $this->error('長度最短需要 ' . $lenght . ' 個字')
      : $this;
  }

  public function strMaxLength(int $lenght = null) {
    return $this->mustCheck && isset($lenght) && $lenght >= 0 && mb_strlen($this->params[$this->key]) > $lenght
    ? $this->error('長度最長只能 ' . $lenght . ' 個字')
    : $this;
  }
  
  public function isNum() {
    if ($this->mustCheck && !is_numeric($this->params[$this->key]))
      return $this->error('必須是「數字」格式');
    $this->params[$this->key] += 0;
    return $this;
  }

  public function isInt() {
    return $this->mustCheck && $this->isNum() && !is_int($this->params[$this->key])
      ? $this->error('必須是「整數」格式')
      : $this;
  }

  public function lessEqual(int $num = null) {
    return $this->mustCheck && isset($num) && $this->params[$this->key] > $num
      ? $this->error('需要小於等於「' . $num . '」')
      : $this;
  }

  public function less(int $num = null) {
    return $this->mustCheck && isset($num) && $this->params[$this->key] >= $num
      ? $this->error('需要小於「' . $num . '」')
      : $this;
  }

  public function greaterEqual(int $num = null) {
    return $this->mustCheck && isset($num) && $this->params[$this->key] < $num
      ? $this->error('需要大於等於「' . $num . '」')
      : $this;
  }

  public function greater(int $num = null) {
    return $this->mustCheck && isset($num) && $this->params[$this->key] <= $num
      ? $this->error('需要大於「' . $num . '」')
      : $this;
  }
  
  public function isArr() {
    return $this->mustCheck && !is_array($this->params[$this->key])
      ? $this->error('必須是「陣列」格式')
      : $this;
  }

  public function arrMinLength(int $lenght = null) {
    return $this->mustCheck && isset($lenght) && count($this->params[$this->key]) < $lenght
      ? $this->error('最少需要「' . $lenght . '」個')
      : $this;
  }

  public function arrMaxLength(int $lenght = null) {
    return $this->mustCheck && isset($lenght) && count($this->params[$this->key]) > $lenght
      ? $this->error('最多只能「' . $lenght . '」個')
      : $this;
  }

  public function map($closure = null) {
    $this->mustCheck && is_callable($closure) && $this->params[$this->key] = array_map($closure, $this->params[$this->key]);
    return $this;
  }

  public function filter($closure = null) {
    if (!$this->mustCheck)
      return $this;
    else if (!isset($closure))
      $this->params[$this->key] = array_filter($this->params[$this->key]);
    else if (is_callable($closure))
      $this->params[$this->key] = array_filter($this->params[$this->key], $closure);
    else if (is_array($closure))
      $this->params[$this->key] = array_filter($this->params[$this->key], function($e) use ($closure) { foreach ($closure as $enum) if ($enum === $e)  return true; return false; });
    
    return $this;
  }

  public function sizeMin(int $size = null) {
    return $this->mustCheck && isset($size) && $this->params[$this->key]['size'] < $size
      ? $this->error('檔案最少需要「' . implode(' ', memoryUnit($size)) . '」')
      : $this;
  }

  public function sizeMax(int $size = null) {
    return $this->mustCheck && isset($size) && $this->params[$this->key]['size'] > $size
      ? $this->error('檔案最大只能「' . implode(' ', memoryUnit($size)) . '」')
      : $this;
  }

  public function formatFilter(array $formats = []) {
    return $this->mustCheck && $formats && !uploadFileInFormats($this->params[$this->key], $formats)
      ? $this->error('檔案格式不符合')
      : $this;
  }

  public function isString(int $minLength = 0, int $maxLength = null) {
    return $this->mustCheck
      ? $this->isStr()->strTrim()->strStripTags()->strMinLength($minLength)->strMaxLength($maxLength)
      : $this;
  }

  public function isNumber(int $min = null, int $max = null) {
    return $this->mustCheck
      ? $this->isNum()->greaterEqual($min)->lessEqual($max)
      : $this;
  }

  public function isInteger(int $min = null, int $max = null) {
    return $this->mustCheck
      ? $this->isInt()->greaterEqual($min)->lessEqual($max)
      : $this;
  }

  public function isArray(int $minLength = 0, int $maxLength = null) {
    return $this->mustCheck
      ? $this->isArr()->arrMinLength($minLength)->arrMaxLength($maxLength)
      : $this;
  }
  
  public function isDate() {
    return $this->mustCheck && $this->isString(10, 10) && !isDate($this->params[$this->key])
      ? $this->error('必須是「Date」格式')
      : $this;
  }

  public function isDatetime() {
    return $this->mustCheck && $this->isString(19, 19) && !isDatetime($this->params[$this->key])
      ? $this->error('必須是「Datetime」格式')
      : $this;
  }

  public function isUrl() {
    return $this->mustCheck && $this->isString() && !isUrl($this->params[$this->key])
      ? $this->error('必須是「網址(http、https)」格式')
      : $this;
  }

  public function isEmail() {
    return $this->mustCheck && $this->isString() && !isEmail($this->params[$this->key])
      ? $this->error('必須是「E-mail」格式')
      : $this;
  }

  public function isId() {
    return $this->mustCheck
    ? $this->isInteger(0)
    : $this;
  }

  public function isLat() {
    return $this->mustCheck
    ? $this->isNumber(-90, 90)
    : $this;
  }

  public function isLng() {
    return $this->mustCheck
    ? $this->isNumber(-180, 180)
    : $this;
  }

  public function inEnum(array $enums = []) {
    if (!($this->mustCheck && $enums))
      return $this;

    $this->isString();

    foreach ($enums as $enum)
      if ($enum === $this->params[$this->key])
        return $this;

    return $this->error('不存在允許的選項內！');
  }

  public function isUpload(int $sizeMin = 0, int $sizeMax = null) {
    return $this->mustCheck
      ? $this->isArray(5, 5) && isUploadFile($this->params[$this->key])
        ? $this->sizeMin($sizeMin)->sizeMax($sizeMax)
        : $this->error('必須是「上傳檔案」的格式')
      : $this;
  }
}