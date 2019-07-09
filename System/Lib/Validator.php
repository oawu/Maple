<?php

Load::systemFunc('Format.php');

class ValidatorContainer {
  private $params = null, $closure = null, $validators = [];

  public function __construct(&$params, $closure) {
    $this->params =& $params;
    $this->closure = $closure;
  }

  public function &params() {
    return $this->params;
  }

  public function run() {
    if (is_callable($closure = $this->closure))
      $closure();

    foreach ($this->validators as $validator)
      $validator->run($this->params);

    return $this;
  }

  public function appendValidator(Validator $validator) {
    array_push($this->validators, $validator);
    return $this;
  }

  public static function create($params, $closure) {
    $static = new static($params, $closure);
    $static->run();
    return $static->params();
  }
}

class Validator {
  public static function maybe(string $name = null, string $title = null, int $code = null) { return new static(false, $name, $title); }
  public static function need(string $name = null, string $title = null, int $code = null) { return new static(true, $name, $title); }
  public static function get($closure) { return ValidatorContainer::create(Input::get(), $closure); }
  public static function post($closure) { return ValidatorContainer::create(Input::post(), $closure); }
  public static function file($closure) { return ValidatorContainer::create(Input::file(), $closure); }
  public static function params($params, $closure) { return ValidatorContainer::create($params, $closure); }
  
  public function isNum() { array_push($this->methods, ['isNum']); return $this; }
  public function isInt() { array_push($this->methods, ['isInt']); return $this; }
  public function isStr() { array_push($this->methods, ['isStr']); return $this; }
  public function isArr() { array_push($this->methods, ['isArr']); return $this; }
  public function isNumber(int $min = null, int $max = null) { array_push($this->methods, ['isNumber', $min, $max]); return $this; }
  public function integer(int $min = null, int $max = null) { array_push($this->methods, ['integer', $min, $max]); return $this; }
  public function isString(int $minLength = 0, int $maxLength = null) { array_push($this->methods, ['isString', $minLength, $maxLength]); return $this; }
  public function isArray(int $minLength = 0, int $maxLength = null) { array_push($this->methods, ['isArray', $minLength, $maxLength]); return $this; }
  public function isDate() { array_push($this->methods, ['isDate']); return $this; }
  public function isDatetime() { array_push($this->methods, ['isDatetime']); return $this; }
  public function isUrl(int $maxLength = null) { array_push($this->methods, ['isUrl', $maxLength]); return $this; }
  public function isEmail(int $maxLength = null) { array_push($this->methods, ['isEmail', $maxLength]); return $this; }
  public function allowableTags($allowableTags = null) { $this->allowableTags = $allowableTags; return $this; }
  public function isId() { array_push($this->methods, ['integer', 0]); return $this; }
  public function isLat() { array_push($this->methods, ['isNumber', -90, 90]); return $this; }
  public function isLng() { array_push($this->methods, ['isNumber', -180, 180]); return $this; }
  public function inEnum(array $enums = []) { array_push($this->methods, ['inEnum', $enums]); return $this; }
  public function map($closure = null) { array_push($this->methods, ['map', $closure]); return $this; }
  public function filter($closure = null) { array_push($this->methods, ['filter', $closure]); return $this; }
  public function isUpload(int $sizeMin = null, int $sizeMax = null) { array_push($this->methods, ['isUpload', $sizeMin, $sizeMax]); return $this; }
  public function formatFilter(array $formats = []) { array_push($this->methods, ['formatFilter', $formats]); return $this; }

  private $need = true, $name = null, $title = null, $default = null, $code = 400, $methods = [], $allowableTags = null;

  public function __construct(bool $need, string $name = null, string $title = null, int $code = null) {
    $traces = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
    
    foreach ($traces as $trace)
      if (isset($trace['object']) && $trace['object'] instanceof ValidatorContainer && method_exists($trace['object'], 'appendValidator') && $trace['object']->appendValidator($this))
        break;

    $this->isNeed($need);
    $name && $this->name($name);
    $title && $this->title($title);
    $code && $this->code($code);
  }

  public function isNeed(bool $need) {   $this->need  = $need;      return $this; }
  public function name(string $name) {   $this->name  = $name;      return $this; }
  public function title(string $title) { $this->title = $title;     return $this; }
  public function default($default) {    $this->default = $default; return $this; }
  public function code(int $code) { $this->code  = $code;  return $this; }
  // public function allowableTags() { return $this->allowableTags; }

  public function run(&$params) {
    if (!is_string($this->name)) return;
    is_string($this->title) || $this->title = $this->name;
    if ($this->need) if (!array_key_exists($this->name, $params)) error($this->code, '「' . $this->title . '」需必填！');
    if (!$this->need) if (!array_key_exists($this->name, $params)) if ($this->default !== null) $params[$this->name] = $this->default;
    if ($this->need || (array_key_exists($this->name, $params) && $params[$this->name] !== $this->default)) foreach ($this->methods as $method) if ($error = call_user_func_array(['static', '_' . array_shift($method)], array_merge([&$params[$this->name], $this], $method))) error($this->code, '「' . $this->title . '」' . $error);
  }


  private static function _isStr(&$param, Validator $validator) { return is_string($param) ? null : '不是字串！'; }
  private static function _isNum(&$param, Validator $validator) { if (!is_numeric($param)) return '不是數字！'; $param = $param + 0; return null; }
  private static function _isInt(&$param, Validator $validator) { if ($error = self::_isNum($param, $validator)) return $error; if (!is_int($param)) return '不是整數！'; return null; }
  private static function _isArr(&$param, Validator $validator) { return is_array($param) ? null : '不是陣列！'; }
  private static function _isNumber(&$param, Validator $validator, int $min = null, int $max = null) { if ($error = self::_isNum($param, $validator)) return $error; if ($min !== null) if ($error = self::_greaterEqual($param, $validator, $min)) return $error; if ($max !== null) if ($error = self::_lessEqual($param, $validator, $max)) return $error; return null; }
  private static function _integer(&$param, Validator $validator, int $min = null, int $max = null) { if ($error = self::_isInt($param, $validator)) return $error; if ($min !== null) if ($error = self::_greaterEqual($param, $validator, $min)) return $error; if ($max !== null) if ($error = self::_lessEqual($param, $validator, $max)) return $error; return null; }
  private static function _isString(&$param, Validator $validator, int $minLength = 0, int $maxLength = null) { if ($error = self::_isStr($param, $validator)) return $error; self::_strTrim($param, $validator); self::_strStripTags($param, $validator); if ($minLength >= 0) if ($error = self::_strMinLength($param, $validator, $minLength)) return $error; if ($maxLength !== null && $maxLength >= 0) if ($error = self::_strMaxLength($param, $validator, $maxLength)) return $error; return null; }
  private static function _isArray(&$param, Validator $validator, int $minLength = 0, int $maxLength = null) { if ($error = self::_isArr($param, $validator)) return $error; if ($minLength >= 0) if ($error = self::_arrMinLength($param, $validator, $minLength)) return $error; if ($maxLength !== null && $maxLength >= 0) if ($error = self::_arrMaxLength($param, $validator, $maxLength)) return $error; return null; }

  private static function _lessEqual(&$param, Validator $validator, $num) { return $param <= $num ? null : '需要小於等於 ' . $num . '！'; }
  private static function _less(&$param, Validator $validator, $num) { return $param < $num ? null : '需要小於 ' . $num . '！'; }
  private static function _greaterEqual(&$param, Validator $validator, $num) { return $param >= $num ? null : '需要大於等於 ' . $num . '！'; }
  private static function _greater(&$param, Validator $validator, $num) { return $param > $num ? null : '需要大於 ' . $num . '！'; }
  
  private static function _strTrim(&$param, Validator $validator, $mask = " \t\n\r\0\x0B") { $param = trim($param, $mask); return null; }
  private static function _strStripTags(&$param, Validator $validator, $allowableTags = null) { if ($validator->allowableTags !== false) $param = $allowableTags !== null ? strip_tags($param, $allowableTags) : strip_tags($param); return null; }
  private static function _strMinLength(&$param, Validator $validator, $lenght) { return mb_strlen($param) >= $lenght ? null : '長度最短需要 ' . $lenght . ' 個字！'; }
  private static function _strMaxLength(&$param, Validator $validator, $lenght) { return mb_strlen($param) <= $lenght ? null : '長度最長只能 ' . $lenght . ' 個字！'; }
  
  private static function _arrMinLength(&$param, Validator $validator, $lenght) { return count($param) >= $lenght ? null : '最少需要 ' . $lenght . ' 個！'; }
  private static function _arrMaxLength(&$param, Validator $validator, $lenght) { return count($param) <= $lenght ? null : '最多只能 ' . $lenght . ' 個！'; }
  
  private static function _isUrl(&$param, Validator $validator, int $maxLength = null) { if ($error = self::_isString($param, $validator, 0, $maxLength)) return $error; return isUrl($param) ? null : '須為網址(http、https)格式！'; }
  private static function _isEmail(&$param, Validator $validator, int $maxLength = null) { if ($error = self::_isString($param, $validator, 0, $maxLength)) return $error; return isEmail($param) ? null : '須為電子郵件(E-Mail)格式！'; }
  private static function _isDate(&$param, Validator $validator) { if ($error = self::_isString($param, $validator, 0, $maxLength)) return $error; return isDate($param) ? null : '不符合 Date 格式！'; }
  private static function _isDatetime(&$param, Validator $validator) { if ($error = self::_isString($param, $validator, 0, $maxLength)) return $error; return isDatetime($param) ? null : '不符合 Date 格式！'; }

  private static function _inEnum(&$param, Validator $validator, array $enums = []) { if ($error = self::_isString($param, $validator)) return $error; foreach ($enums as $enum) if ($enum === $param) return null; return '不存在選項中！'; }
  private static function _map(&$param, Validator $validator, $closure = null) { if (!is_array($param)) return null; is_callable($closure) && $param = array_map($closure, $param); }
  private static function _filter(&$param, Validator $validator, $closure = null) { if (!is_array($param)) return null; $closure === null && $param = array_filter($param); is_callable($closure) && $param = array_filter($param, $closure); is_array($closure) && $param = array_filter($param, function($e) use ($closure) { foreach ($closure as $enum) if ($enum === $e) return true; return false; }); }

  private static function _formatFilter(&$param, Validator $validator, array $formats = []) { if ($error = self::_isUpload($param, $validator)) return $error; return uploadFileInFormats($param, $formats) ? null : '格式不符！'; }
  private static function _isUpload(&$param, Validator $validator, int $min = null, int $max = null) { if ($error = self::_isArray($param, $validator, 5, 5)) return $error; if (!isUploadFile($param)) return '檔案格式錯誤！'; if ($min !== null) if ($error = self::_sizeMin($param, $validator, $min)) return $error; if ($max !== null) if ($error = self::_sizeMax($param, $validator, $max)) return $error; return null; }

  private static function _sizeMin(&$param, Validator $validator, $size) { return $param['size'] >= $size ? null : '檔案最少需要 ' . implode(' ', memoryUnit($size)) . '！'; }
  private static function _sizeMax(&$param, Validator $validator, $size) { return $param['size'] <= $size ? null : '檔案最大只能 ' . implode(' ', memoryUnit($size)) . '！'; }
}
