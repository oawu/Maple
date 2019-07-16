<?php

if (!function_exists('umaskChmod')) {
  function umaskChmod($path, $mode = 0777) {
    $oldmask = umask(0);
    @chmod($path, $mode);
    umask($oldmask);
  }
}

if (!function_exists('umaskMkdir')) {
  function umaskMkdir($path, $mode = 0777, $recursive = false) {
    $oldmask = umask(0);
    $return = @mkdir($path, $mode, $recursive);
    umask($oldmask);
    return $return;
  }
}

if (!function_exists('dirNotExistCreate')) {
  function dirNotExistCreate($path, $mode = 0777, $recursive = false) {
    return is_dir($path) ? true : @umaskMkdir($path, $mode, $recursive);
  }
}

if (!function_exists('isAssoc')) {
  // 判斷陣列是否為非正常正列
  // ['a', 'b'] => false,
  // ['a' => 1, 'b' => 'c'] => true
  function isAssoc(array $array) {
    $keys = array_keys($array);
    return array_keys($keys) !== $keys;
  }
}

if (!function_exists('dump')) {
  // 專門印變數用的(不錯用，可以印出 Model 之類的)

  function dump($val, $ln = "\n", $spaceStr = ' ', $level = 0) {
    $ln === '' && $level = 0;

    $space = str_repeat($spaceStr, $level * 2);

    if ($val === null)
      return $space . 'null';

    if (is_bool($val))
      return $space . '' . ($val === true ? 'true' : 'false') . '';

    if (is_string($val))
      return $space . '"' . $val . '"';

    if (is_numeric($val))
      return $space . $val;

    if (is_array($val)) {
      if (isAssoc($val))
        return $space . '[' . (($tmp = implode(', ' . $ln, array_map(function ($k, $v) use ($level, $ln, $spaceStr) {
          return dump($k, $ln, $spaceStr, $level + 1) . ': ' . ltrim(dump($v, $ln, $spaceStr, $level + 1), $spaceStr);
        }, array_keys($val), $val))) ? $ln . $tmp . $ln . $space : '') . ']';
      else
        return $space . '[' . (($tmp = implode(',' . $ln, array_map(function($v) use ($level, $ln, $spaceStr) {
          return dump($v, $ln, $spaceStr, $level + 1);
        }, $val))) ?  $ln . $tmp . $ln . $space : '' ) . ']';
    }

    if ($val instanceof \M\Model)
      return $space . 'Model(' . deNamespace(get_class($val)) . ') {' . $ln
            . implode(',' . $ln, array_map(function ($k, $v) use ($spaceStr, $level, $ln) { return str_repeat($spaceStr, ($level + 1) * 2) . dump($k, $ln, $spaceStr, 0) . ': ' . ltrim(dump($v, $ln, $spaceStr, $level + 1), $spaceStr);}, array_keys($val->attrs()), $val->attrs())) . ($ln ? $ln . $space : '') . '}';

    if ($val instanceof \_M\DateTime)
      return $space . 'DateTime(' . '"' . $val . '"' . ')';

    if ($val instanceof \_M\Uploader) {
      $space2 = str_repeat($spaceStr, ($level + 1) * 2);

      if ($val instanceof \_M\FileUploader)
        return $space . 'Uploader(File) {' . $ln
              . $space2 . '"DB value": ' . dump((string)$val) . $ln
              . $space2 . '"defaultUrl": ' . dump($val->defaultUrl()) . $ln
              . $space . '}';

      if ($val instanceof \_M\ImageUploader)
        return $space . 'Uploader(Image) {' . $ln
              . $space2 . '"DB value": ' . dump((string)$val) . $ln
              . $space2 . '"defaultUrl": ' . dump($val->defaultUrl()) . $ln
              . $space2 . '"versions": ' . '[' . $ln . implode(', ' . $ln, array_map(function($t) use ($level, $spaceStr, $ln) { return dump($t, $ln, $spaceStr, $level + 2); }, array_keys($val->versions()))) . $ln . $space2 . ']' . $ln
              . $space . '}';
    }

    if ($val instanceof Exception)
      return $space . $val->getMessage();

    if (is_object($val) && method_exists($val, '__toString'))
      return $space . '"' . $val . '"';
    
    if (is_object($val) && !method_exists($val, '__toString'))
      return $space . 'Obj(' . get_class($val) . ')';

    ob_start();
    var_dump($val);
    return ob_get_clean();
  }
}

if (!function_exists('isCli')) {
  function isCli() {
    return PHP_SAPI === 'cli' || defined('STDIN');
  }
}

if (!function_exists('isPhpVersion')) {
  function isPhpVersion($version) {
    static $versions;
    return !isset($versions[$version = (string)$version]) ? $versions[$version] = version_compare(PHP_VERSION, $version, '>=') : $versions[$version];
  }
}

if (!function_exists('implodeRecursive')) {
  function implodeRecursive($glue, $pieces) {
    $ret = '';

    foreach ($pieces as $piec)
      $ret .= isset($piec) ? !is_object($piec) ? !is_bool($piec) ? is_array($piec) ? '[' . implodeRecursive($glue, $piec) . ']' . $glue : $piec . $glue : ($piec ? 'true' : 'false') . $glue : get_class($piec) . $glue : 'null' . $glue;

    $ret = substr($ret, 0, 0 - strlen($glue));

    return $ret;
  }
}

if (!function_exists('config')) {
  function config() {
    static $files, $keys;

    if (!$args = func_get_args())
      return null;

    $filename = array_shift($args);
    $argsStr  = implode('', $args);

    if (isset($keys[$filename . $argsStr]))
      return $keys[$filename . $argsStr];
    
    if (!isset($files[$filename])) {
      if (!file_exists($path = PATH_CONFIG . ENVIRONMENT . DIRECTORY_SEPARATOR . $filename . '.php') && !file_exists($path = PATH_CONFIG . $filename . '.php'))
        return null;

      $files[$filename] = include_once($path);
    }

    $tmp = $files[$filename];

    foreach ($args as $arg)
      if (($tmp = $tmp[$arg] ?? null) === null)
        break;

    return $keys[$filename . $argsStr] = $tmp;
  }
}

if (!function_exists('arrayFlatten')) {
  function arrayFlatten($arr) {
    $new = [];

    foreach ($arr as $val)
      if (is_array($val))
        $new = array_merge($new, arrayFlatten($val));
      else
        array_push($new, $val);

    return $new;
  }
}

if (!function_exists('attr')) {
  function attr($attrs, $excludes = [], $symbol = '"') {
    $attrs = array_filter($attrs, function($attr) { return $attr !== null; });

    is_string($excludes) && $excludes = [$excludes];
    if ($excludes)
      foreach ($attrs as $key => $value)
        if (in_array($key, $excludes))
          unset($attrs[$key]);

    $attrs = array_map(function($k, $v) use ($symbol) { return is_bool($v) ? $v === true ? $k : '' : ($k . '=' . $symbol . $v . $symbol); }, array_keys($attrs), array_values($attrs));
    return $attrs ? ' ' . implode(' ', $attrs) : '';
  }
}

if (!function_exists('tag')) {
  function tag($name, $text, $arr = []) {
    if (is_array($text))
      return '<' . $name . attr($text) . ' />';
    else
      return '<' . $name . attr($arr) . '>' . $text . '</' . $name . '>';
  }
}

if (!function_exists('getNamespaces')) {
  function getNamespaces($className) {
    return array_slice(explode('\\', $className), 0, -1);
  }
}

if (!function_exists('deNamespace')) {
  function deNamespace($className) {
    $className = array_slice(explode('\\', $className), -1);
    return array_shift($className);
  }
}

if (!function_exists('isJson')) {
  function isJson(&$string, $array = true) {
   $string = json_decode($string, $array);
   return json_last_error() === JSON_ERROR_NONE;
  }
}

if (!function_exists('memoryUnit')) {
  function memoryUnit($s) {
    if (!$s)
      return [];
    $u = ['B','KB','MB','GB','TB','PB'];
    return [@round($s / pow(1024, ($i = floor(log($s, 1024)))), 2), $u[$i]];
  }
}
if (!function_exists('timeago')) {
  function timeago($now) {
    $day = time() - $now;
    $units = [['b' => 10, 'f' => '現在'],
             ['b' => 6, 'f' => '不到 1 分鐘'],
             ['b' => 60, 'f' => ' 分鐘前'],
             ['b' => 24, 'f' => ' 小時前'],
             ['b' => 30, 'f' => ' 天前'],
             ['b' => 12, 'f' => ' 個月前']];
    $unit = 1;
    for ($i = 0, $c = count($units); $i < $c; $i++, $unit = $tmp) {
      $tmp = $units[$i]['b'] * $unit;
      if ($day < $tmp)
        return [($i > 1 ? round($day / $unit) : ''), $units[$i]['f']];
    }
    return [round($day / $unit), ' 年前'];
  }
}

if (!function_exists('transaction')) {
  function transaction($closure, $code = 400) {
    $errors = call_user_func_array('\M\transaction', [$closure]);
    return $errors ? error($code, array_merge(['資料庫處理錯誤！'], $errors)) : null;
  }
}

if (!function_exists('items')) {
  function items($values, $texts, $k1 = 'value', $k2 = 'text') {
    return count($values) == count($texts) ? array_map(function($value, $text) use ($k1, $k2) { return [$k1 => '' . $value, $k2 => '' . $text]; }, $values, $texts) : [];
  }
}

if (!function_exists('minText')) {
  function minText($text, $length = 200) {
    return $length ? mb_strimwidth(strip_tags($text), 0, $length, '…','UTF-8') : strip_tags($text);
  }
}