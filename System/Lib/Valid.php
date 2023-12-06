<?php

namespace {
  use \Valid\Rule;
  use \Valid\Model;
  use \Valid\Exception;

  final class Valid {
    private static $__error = null;

    public static function __callStatic($name, $params) {
      if ($name === 'check') return call_user_func_array([new static(self::$__error), 'check'], $params);

      $chars = str_split($name);
      array_pop($chars) === '_'
        ? array_unshift($params, true)
        : array_unshift($params, false);

      switch ($name) {
        case 'int':       case 'int_':       return call_user_func_array('\Valid\Rule\Num\_Int::create', $params);
        case 'int8':      case 'int8_':      return call_user_func_array('\Valid\Rule\Num\_Int::create', $params)->min(-127)->max(127);
        case 'int16':     case 'int16_':     return call_user_func_array('\Valid\Rule\Num\_Int::create', $params)->min(-32767)->max(32767);
        case 'int32':     case 'int32_':     return call_user_func_array('\Valid\Rule\Num\_Int::create', $params)->min(-2147483647)->max(2147483647);
        case 'int64':     case 'int64_':     return call_user_func_array('\Valid\Rule\Num\_Int::create', $params)->min(-9223372036854775807)->max(9223372036854775807);
        case 'uInt':      case 'uInt_':      return call_user_func_array('\Valid\Rule\Num\_Int::create', $params)->min(0);
        case 'uInt8':     case 'uInt8_':     return call_user_func_array('\Valid\Rule\Num\_Int::create', $params)->min(0)->max(255);
        case 'uInt16':    case 'uInt16_':    return call_user_func_array('\Valid\Rule\Num\_Int::create', $params)->min(0)->max(65535);
        case 'uInt32':    case 'uInt32_':    return call_user_func_array('\Valid\Rule\Num\_Int::create', $params)->min(0)->max(4294967295);
        case 'uInt64':    case 'uInt64_':    return call_user_func_array('\Valid\Rule\Num\_Int::create', $params)->min(0)->max(18446744073709551615);
        case 'id':        case 'id_':        return call_user_func_array('\Valid\Rule\Num\_Int::create', $params)->min(1)->max(18446744073709551615);
        case 'timestamp': case 'timestamp_': return call_user_func_array('\Valid\Rule\Num\_Int::create', $params)->min(0);
        case 'float':     case 'float_':     return call_user_func_array('\Valid\Rule\Num\_Float::create', $params);
        case 'uFloat':    case 'uFloat_':    return call_user_func_array('\Valid\Rule\Num\_Float::create', $params)->min(0);
        case 'string':    case 'string_':    return call_user_func_array('\Valid\Rule\_String::create', $params);
        case 'url':       case 'url_':       return call_user_func_array('\Valid\Rule\String\_Url::create', $params);
        case 'email':     case 'email_':     return call_user_func_array('\Valid\Rule\String\_Email::create', $params);
        case 'date':      case 'date_':      return call_user_func_array('\Valid\Rule\String\_Date::create', $params);
        case 'time':      case 'time_':      return call_user_func_array('\Valid\Rule\String\_Time::create', $params);
        case 'datetime':  case 'datetime_':  return call_user_func_array('\Valid\Rule\String\_Datetime::create', $params);
        case 'bool':      case 'bool_':      return call_user_func_array('\Valid\Rule\_Bool::create', $params);
        case 'enum':      case 'enum_':      return call_user_func_array('\Valid\Rule\_Enum::create', $params);
        case 'upload':    case 'upload_':    return call_user_func_array('\Valid\Rule\_Upload::create', $params);
        case 'array':     case 'array_':     return call_user_func_array('\Valid\Rule\_Array::create', $params);
        case 'object':    case 'object_':    return call_user_func_array('\Valid\Rule\_Object::create', $params);
        case 'any':       case 'any_':       return call_user_func_array('\Valid\Rule\_Any::create', $params);
        default: return null;
      }
    }
    public static function error($error = null) {
      self::$__error = $error;
    }
    public static function create($error = null) {
      return new static($error);
    }

    private $_error = null;
    private $_format = null;

    public function __construct($error) {
      $this->_error = is_callable($error) ? $error : null;
    }
    public function format($format) {
      $this->_format = is_callable($format) ? $format : null;
      return $this;
    }
    public function __call($name, $args) {
      if ($name != 'check')
        return null;

      $params = array_shift($args);
      $format = array_shift($args);
      $this->format(array_shift($args));

      if ($format instanceof Model) return $this->_checkModel($params, $format);
      if ($format instanceof Rule) return $this->_checkRule($params, $format);
      if (is_array($format)) return $this->_checkModel($params, Model::create($format));
      $this->_error('Valid 參數 2 格式錯誤！', 500);
    }
    private function _checkModel($params, $model) {
      is_array($params) || $this->_error('Valid 參數 1 須為 Array 格式！', 500);
      
      $returns = [];
      $exception = null;

      try {
        foreach (array_filter($model->rules(), function($rule) { return $rule->getKey() !== null; }) as $rule) {
          $optional = $rule->getOptional();
          $default  = $rule->getDefault();
          $null     = $rule->getNull();
          $key      = $rule->getKey();
          $exist    = array_key_exists($key, $params);
          $val      = $rule->setVal($exist ? $params[$key] : null)->getVal();
          
          if ($exist) {
            $returns[$key] = $val === null && $null['isSet'] ? $null['value'] : $val;
          } else if ($default['isSet']) {
            $returns[$key] = $default['value'];
          } else {
          }
        }
      } catch (Exception $e) {
        $exception = $e;
      }

      return !$exception
        ? $this->_format($returns)
        : $this->_error($exception->name() . $exception->reason(), $exception->code());
    }
    private function _checkRule($val, $rule) {
      $exception = null;
      try {
        $val = $rule->setVal($val)->getVal();
      } catch (Exception $e) {
        $exception = $e;
      }

      return !$exception
        ? $this->_format($val)
        : $this->_error($exception->name() . $exception->reason(), $exception->code());
    }
    private function _format($val) {
      return is_callable($format = $this->_format)
        ? $format($val, function($message, $code = 400) { return $this->_error($message, $code); })
        : $val;
    }
    private function _error($message, $code = 400) {
      return is_callable($error = $this->_error)
        ? $error($message, $code)
        : null;
    }
  }
}
namespace Valid {
  final class Exception extends \Exception {
    private $_code = 400;
    private $_name = '';
    private $_reason = '';

    public function __construct($name, $reason, $code) {
      parent::__construct($name . $reason);
      $this->_code = $code;
      $this->_name = $name;
      $this->_reason = $reason;
    }
    public function code() {
      return $this->_code;
    }
    public function name() {
      return $this->_name;
    }
    public function reason() {
      return $this->_reason;
    }
  }

  abstract class Rule {
    abstract public function getVal();

    private $_optional;
    private $_default = ['value' => null, 'isSet' => false];
    private $_null = ['value' => null, 'isSet' => false];
    private $_key;
    private $_name;
    private $_code = 400;
    protected $_val = null;

    public function __construct($optional) {
      $args = func_get_args();
      $optional = array_shift($args);
      $this->_optional = $optional;

      $name = null;
      $code = 400;

      for (;$tmp = array_shift($args);) { 
        is_string($tmp) && $name = $tmp;
        is_int($tmp) && $code = $tmp;
      }
      $this->_name     = $name;
      $this->_code     = $code;
    }
    public static function create() {
      return (new \ReflectionClass(static::class))->newInstanceArgs(func_get_args());
    }
    protected function error($reason, $code = null) {
      throw new Exception($this->_name ? '「' . $this->_name . '」' : '', $reason, $code ?? $this->_code);
    }
    protected function isOptionalAndValIsNull() {
      return $this->_optional && $this->_val === null;
    }
    public function setKey($key) {
      $this->_key = $key;
      $this->_name = $this->_name ?? $key;
      return $this;
    }
    public function getKey() {
      return $this->_key;
    }
    public function defaultAndNull($val) {
      return $this->default($val)->null($val);
    }
    public function null($null) {
      $this->_null['isSet'] = true;
      $this->_null['value'] = $null;
      return $this;
    }
    public function default($default) {
      $this->_default['isSet'] = true;
      $this->_default['value'] = $default;
      return $this;
    }
    public function getNull() {
      return $this->_null;
    }
    public function getDefault() {
      return $this->_default;
    }
    public function setVal($val = null) {
      $this->_val = $val;
      return $this;
    }
    public function getOptional() {
      return $this->_optional;
    }
  }
  final class Model {
    private $_rules = [];

    public function __construct($rules = []) {
      foreach ($rules as $key => $rule)
        $rule && $rule instanceof Rule && $rule->setKey($key) && array_push($this->_rules, $rule);
    }
    public static function create($rules = []) {
      return new static($rules);
    }
    public function rules() {
      return $this->_rules;
    }
  }
}

namespace Valid\Rule {
  use \Valid;
  use \Valid\Rule;
  use \Valid\Model;

  trait minmax {
    private $_min = null;
    private $_max = null;
    private function _str2num($val) {
      is_object($val) && method_exists($val, '__toString') && $val .= '';
      is_string($val) && $val = trim($val);
      is_numeric($val) && $val += 0;
      return $val;
    }
    public function min($val) {
      $val === null && $this->_min = $val;
      $val = $this->_str2num($val);
      is_int($val) && $this->_min = $val;
      return $this;
    }
    public function max($val) {
      $val === null && $this->_max = $val;
      $val = $this->_str2num($val);
      is_int($val) && $this->_max = $val;
      return $this;
    }
    public function range($min = null, $max = null) {
      return $this->min($min)->max($max);
    }
    public function len($min = null, $max = null) {
      return $this->min($min)->max($max);
    }
    public function size($min = null, $max = null) {
      return $this->min($min)->max($max);
    }
  }
  class _Num extends Rule {
    use minmax;

    private $_isZeroIsNull = false;
    private $_isEmptyStringIsNull = false;

    public function zeroIsNull($val = true) {
      $this->_isZeroIsNull = $val;
      return $this;
    }
    public function emptyStringIsNull($val = true) {
      $this->_isEmptyStringIsNull = $val;
      return $this;
    }
    public function getVal() {
      $this->getOptional()
        && $this->_val === ''
        && $this->_isEmptyStringIsNull === true && $this->_val = null;

      if ($this->isOptionalAndValIsNull())
        return $this->_val = null;
      else
        $this->_val !== null || $this->error('為必要');

      is_numeric($this->_val) || $this->error('必須是「數字」格式');
      $this->_val += 0;

      $this->getOptional()
        && $this->_val === 0
        && $this->_isZeroIsNull === true && $this->_val = null;

      if ($this->isOptionalAndValIsNull())
        return $this->_val = null;

      $this->_min === null || $this->_val >= $this->_min || $this->error('需要大於等於「' . $this->_min . '」');
      $this->_max === null || $this->_val <= $this->_max || $this->error('需要小於等於「' . $this->_max . '」');

      return $this->_val;
    }
  }
  class _String extends Rule {
    use minmax;

    private $_removeHTML = false;
    private $_isEmptyStringIsNull = false;
    
    public function emptyStringIsNull($val = true) {
      $this->_isEmptyStringIsNull = $val;
      return $this;
    }
    public function removeHTML($val = true) {
      $this->_removeHTML = $val;
      return $this;
    }

    public function getVal() {
      $this->getOptional()
        && $this->_val === ''
        && $this->_isEmptyStringIsNull === true
        && $this->_val = null;

      if ($this->isOptionalAndValIsNull())
        return $this->_val = null;
      else
        $this->_val !== null || $this->error('為必要');

      is_numeric($this->_val) || is_string($this->_val) || (is_object($this->_val) && method_exists($this->_val, '__toString')) || $this->error('必須是「字串」格式');
      $this->_val = $this->_removeHTML
        ? strip_tags(trim('' . $this->_val, " \t\n\r\0\x0B"))
        : trim('' . $this->_val, " \t\n\r\0\x0B");

      $this->getOptional()
        && $this->_val === ''
        && $this->_isEmptyStringIsNull === true
        && $this->_val = null;
        
      if ($this->isOptionalAndValIsNull())
        return $this->_val = null;

      $this->_min === null || mb_strlen($this->_val) >= $this->_min || $this->error('長度最短需要「' . $this->_min . '」個字');
      $this->_max === null || mb_strlen($this->_val) <= $this->_max || $this->error('長度最長只能「' . $this->_max . '」個字');

      return $this->_val;
    }
  }

  final class _Bool extends Rule {
    private $_isEmptyStringIsNull = false;
    
    public function emptyStringIsNull($val = true) {
      $this->_isEmptyStringIsNull = $val;
      return $this;
    }

    public function getVal() {
      $this->getOptional()
        && $this->_val === ''
        && $this->_isEmptyStringIsNull === true
        && $this->_val = null;

      if ($this->isOptionalAndValIsNull())
        return $this->_val = null;
      else
        $this->_val !== null || $this->error('為必要');

      is_object($this->_val) && method_exists($this->_val, '__toString') && $this->_val .= '';
      is_string($this->_val) && (strtolower($this->_val) == 'true' || strtolower($this->_val) == 'false') && $this->_val = strtolower($this->_val) == 'true';
      is_bool($this->_val) || $this->error('必須是「布林」格式');

      return $this->_val;
    }
  }
  final class _Upload extends Rule {
    private $_types = null;
    use minmax;

    public function types() {
      $this->_types = array_unique(array_reduce(func_get_args(), function($a, $b) { return array_merge($a, is_array($b) ? $b : [$b]); }, []));
      return $this;
    }
    public function getVal() {
      if ($this->isOptionalAndValIsNull())
        return $this->_val = null;
      else
        $this->_val !== null || $this->error('為必要');

      is_array($this->_val) && array_key_exists('name', $this->_val) && array_key_exists('type', $this->_val) && array_key_exists('tmp_name', $this->_val) && array_key_exists('error', $this->_val) && array_key_exists('size', $this->_val) || $this->error('必須是「上傳檔案」的格式');

      if ($this->_val['error'] !== 0) {
        $this->getOptional() || $this->error('上傳失敗');
        return $this->_val = null; 
      }

      $this->_min === null || $this->_val['size'] >= $this->_min || $this->error('需要大於等於「' . $this->_min . '」Bytes');
      $this->_max === null || $this->_val['size'] <= $this->_max || $this->error('需要小於等於「' . $this->_max . '」Bytes');
      $this->_types === null || in_array($this->_val['type'], $this->_types) || $this->error('類型錯誤');

      return $this->_val;
    }
  }
  final class _Enum extends Rule {
    private $_items = [];
    private $_isEmptyStringIsNull = false;

    public function emptyStringIsNull($val = true) {
      $this->_isEmptyStringIsNull = $val;
      return $this;
    }
    
    public function __construct($optional) {
      $args = func_get_args();
      $optional = array_shift($args);

      $name = null;
      $code = 400;

      for (;$tmp = array_shift($args);) { 
        is_array($tmp) && $this->items($tmp);
        is_string($tmp) && $name = $tmp;
        is_int($tmp) && $code = $tmp;
      }
      
      parent::__construct($optional, $name, $code);
    }

    public function items() {
      $this->_items = array_reduce(func_get_args(), function($a, $b) { return array_merge($a, is_array($b) ? $b : [$b]); }, []);
      return $this;
    }

    private function _in() {
      foreach ($this->_items as $item) {
        if ($item === $this->_val)
          return true;

        if (is_numeric($item) && is_numeric($tmp = trim($this->_val)) && $item == ($tmp + 0)) {
          $this->_val = $tmp + 0;
          return true;
        }
      }

      return false;
    }
    public function getVal() {
      $this->getOptional()
        && $this->_val === ''
        && $this->_isEmptyStringIsNull === true && $this->_val = null;

      if ($this->isOptionalAndValIsNull())
        return $this->_val = null;
      else
        $this->_val !== null || $this->error('為必要');

      $this->_in() || $this->error('不存在允許的選項內');

      return $this->_val;
    }
  }
  final class _Array extends Rule {
    private $_type = null;
    private $_isEmptyStringIsNull = false;
    use minmax;

    public function __construct($optional) {
      $args = func_get_args();
      $optional = array_shift($args);

      $name = null;
      $code = 400;

      for (;$tmp = array_shift($args);) { 
        $this->type($tmp);
        is_string($tmp) && $name = $tmp;
        is_int($tmp) && $code = $tmp;
      }
      
      parent::__construct($optional, $name, $code);
    }
    public function type($tmp) {
      $tmp instanceof Rule && $this->_type = $tmp;
      $tmp instanceof Model && $this->_type = $tmp;
      is_array($tmp) && $this->_type = Model::create($tmp);
      return $this;
    }
    public function emptyStringIsNull($val = true) {
      $this->_isEmptyStringIsNull = $val;
      return $this;
    }

    public function getVal() {
      $this->getOptional()
        && $this->_val === ''
        && $this->_isEmptyStringIsNull === true
        && $this->_val = null;

      if ($this->isOptionalAndValIsNull())
        return $this->_val = null;
      else
        $this->_val !== null || $this->error('為必要');

      is_array($this->_val) || $this->error('必須是「陣列」格式');

      $this->_type instanceof Rule || $this->_type instanceof Model || $this->error('參考格式錯誤');

      $vals = [];

      $error = null;
      foreach ($this->_val as $i => $val)
        $error || array_push($vals, Valid::create(function($message, $code) use (&$error, $i) {
          $error = ['陣列中「索引 ' . $i . '」的元素' . ($this->_type instanceof Model ? '的' : '') . $message, $code];
        })->check($val, $this->_type));

      $error === null || call_user_func_array([$this, 'error'], $error);
      
      $this->_min === null || count($vals) >= $this->_min || $this->error('最少需要「' . $this->_min . '」個');
      $this->_max === null || count($vals) <= $this->_max || $this->error('最多只能「' . $this->_max . '」個');

      return $this->_val = $vals;
    }
  }
  final class _Any extends Rule {
    private $_isEmptyStringIsNull = false;
    
    public function emptyStringIsNull($val = true) {
      $this->_isEmptyStringIsNull = $val;
      return $this;
    }
    public function getVal() {
      $this->getOptional()
        && $this->_val === ''
        && $this->_isEmptyStringIsNull === true
        && $this->_val = null;

      if ($this->isOptionalAndValIsNull())
        return $this->_val = null;
      else
        $this->_val !== null || $this->error('為必要');

      return $this->_val;
    }
  }
  final class _Object extends Rule {
    private $_model = null;
    private $_isEmptyStringIsNull = false;

    public function __construct($optional) {
      $args = func_get_args();
      $optional = array_shift($args);

      $name = null;
      $code = 400;

      for (;$tmp = array_shift($args);) { 
        $this->model($tmp);
        is_string($tmp) && $name = $tmp;
        is_int($tmp) && $code = $tmp;
      }
      
      parent::__construct($optional, $name, $code);
    }

    public function model($tmp) {
      $tmp instanceof Model && $this->_model = $tmp;
      is_array($tmp) && $this->_model = Model::create($tmp);
      return $this;
    }
    
    public function emptyStringIsNull($val = true) {
      $this->_isEmptyStringIsNull = $val;
      return $this;
    }

    public function getVal() {
      $this->getOptional()
        && $this->_val === ''
        && $this->_isEmptyStringIsNull === true
        && $this->_val = null;

      if ($this->isOptionalAndValIsNull())
        return $this->_val = null;
      else
        $this->_val !== null || $this->error('為必要');

      is_array($this->_val) || $this->error('必須是「陣列」格式');

      $this->_model instanceof Model || $this->error('參考格式錯誤');

      $val = null;
      $error = null;

      $val = Valid::create(function($message, $code) use (&$error) {
        $error = ['的' . $message, $code];
      })->check($this->_val, $this->_model);

      $error === null || call_user_func_array([$this, 'error'], $error);

      return $this->_val = $val;
    }
  }
}

namespace Valid\Rule\Num {
  use \Valid\Rule\_Num;

  final class _Int extends _Num {
    public function getVal() {
      parent::getVal();
      $this->_val === null || is_int($this->_val) || $this->error('必須是「整數」格式');
      return $this->_val;
    }
  }
  final class _Float extends _Num {}
}

namespace Valid\Rule\String {
  use \Valid\Rule\_String;

  final class _Url extends _String {
    public function getVal() {
      parent::getVal();
      $this->_val === null || preg_match('/^.*:\/\/.*/', $this->_val) || $this->error('必須是「URL」格式');
      return $this->_val;
    }
  }
  final class _Email extends _String {
    public function getVal() {
      parent::getVal();
      $this->_val === null || filter_var($this->_val, FILTER_VALIDATE_EMAIL) || $this->error('必須是「E-mail」格式');
      return $this->_val;
    }
  }
  final class _Date extends _String {
    public function getVal() {
      $this->min(10)->max(10);
      parent::getVal();
      $this->_val === null || \DateTime::createFromFormat('Y-m-d', $this->_val) !== false || $this->error('必須是「yyyy-MM-dd」的「日期」格式');
      return $this->_val;
    }
  }
  final class _Time extends _String {
    public function getVal() {
      $this->min(8)->max(8);
      parent::getVal();
      $this->_val === null || \DateTime::createFromFormat('H:i:s', $this->_val) !== false || $this->error('必須是「HH:mm:ss」的「時間」格式');
      return $this->_val;
    }
  }
  final class _Datetime extends _String {
    public function getVal() {
      $this->min(19)->max(19);
      parent::getVal();
      $this->_val === null || \DateTime::createFromFormat('Y-m-d H:i:s', $this->_val) !== false || $this->error('必須是「yyyy-MM-dd HH:mm:ss」的「Datetime」格式');
      return $this->_val;
    }
  }
}
