<?php

namespace M;

if (!function_exists('\M\relation')) {
  function relation($relation) {
    $type = $model = null;

    if (is_string($relation)) {
      if (preg_match_all('/^\s*(?P<model>[A-Z][A-Za-z0-9]*)\s*$/', $relation, $result) && !empty($result['model'][0]))
        $relation = '<= ' . $result['model'][0];

      if (!preg_match_all('/^(?P<key1>([A-Za-z][A-Za-z0-9]*)?)\s*(?P<type>([=-]>|<[=-])+)\s*(?P<model>[A-Za-z][A-Za-z0-9]*)\.?(?P<key2>([A-Za-z][A-Za-z0-9_\-]*)?)$/', $relation, $result))
        return false;

      if (empty($result['type'][0]) || empty($result['model'][0]))
        return false;

      $relation = [$result['type'][0] => $result['model'][0]];

      if (!empty($result['key1'][0]))
        $relation[in_array($result['type'][0], ['<=', '<-']) ? 'pk' : 'fk'] = $result['key1'][0];

      if (!empty($result['key2'][0]))
        $relation[in_array($result['type'][0], ['<=', '<-']) ? 'fk' : 'pk'] = $result['key2'][0];
    }
    
    if (!is_array($relation))
      return false;

    $keys = [
      '<=' => 'hasMany',
      '<-' => 'hasOne',
      '=>' => 'belongToMany',
      '->' => 'belongToOne',
      'hasMany'      => 'hasMany',
      'hasOne'       => 'hasOne',
      'belongToMany' => 'belongToMany',
      'belongToOne'  => 'belongToOne'];
    
    foreach ($keys as $key => $method)
      if (isset($relation[$key])) {
        $type = $method;
        $model = $relation[$key];
        unset($relation[$key]);
      }
    
    if (!isset($type, $model))
      return false;

    $relation = options([$relation]);

    return [
      'type'      => in_array($type, ['hasMany', 'hasOne']) ? 'has' : 'belong',
      'method'    => in_array($type, ['hasMany', 'belongToMany']) ? 'all' : 'one',
      'modelName' => $model,
      'options'   => $relation,
    ];
  }
}

if (!function_exists('\M\options')) {
  function options($options) {
    // Model::find('one', null);
    is_array($options) && count($options)
      && $options[0] === null
      && $options[0] = ['where' => \Where::create('id = ?', null)];

    // Model::find('one', 1);
    isset($options[0])
      && is_numeric($options[0])
      && $options[0] = ['where' => \Where::create('id = ?', $options[0])];

    // Model::count(Where::create('id = ?', 2));
    isset($options[0])
      && $options[0] instanceof \Where
      && $options[0] = ['where' => $options[0]];

    // Model::count('id = ?', 2);
    isset($options[0])
      && is_string($options[0])
      && $options[0] = ['where' => \Where::create($options)];

    // 以下為正規格式 ['select' => 'id', 'where' => ...]
    $options = $options ? array_shift($options) : [];

    // Model::count(['where' => 'id = 2']);
    isset($options['where'])
      && is_string($options['where'])
      && $options['where'] = \Where::create($options['where']);
    
    // Model::count(['where' => ['id = ?', 2]]);
    isset($options['where'])
      && is_array($options['where'])
      && $options['where'] = \Where::create($options['where']);

    return $options;
  }
}

if (!function_exists('\M\quoteName')) {
  function quoteName($string) {
    return $string[0] === '`' || $string[strlen($string) - 1] === '`' ? $string : '`' . $string . '`';
  }
}

if (!function_exists('\M\reverseOrder')) {
  function reverseOrder($order) {
    return trim($order) ? implode(', ', array_map(function($part) {
      $v = trim(strtolower($part));
      return strpos($v,' asc') === false ? strpos($v,' desc') === false ? $v . ' DESC' : preg_replace('/desc/i', 'ASC', $v) : preg_replace('/asc/i', 'DESC', $v);
    }, explode(',', $order))) : 'order';
  }
}

if (!function_exists('\M\toColumn')) {
  function toColumn($row) {
    $row = array_change_key_case($row, CASE_LOWER);

    if ($row['type'] == 'timestamp' || $row['type'] == 'datetime') {
      $type = 'datetime';
    } elseif ($row['type'] == 'date') {
      $type = 'date';
    } elseif ($row['type'] == 'time') {
      $type = 'time';
    } else {
      preg_match('/^([A-Za-z0-9_]+)(\(([0-9]+(,[0-9]+)?)\))?/', $row['type'], $matches);
      $type = (count($matches) > 0 ? $matches[1] : $row['type']);
    }

    $type == 'integer' && $this->type = 'int';

    return [
      'name' => $row['field'],
      'null' => $row['null'] === 'YES', // 是否可為 null
      'pk' => $row['key'] === 'PRI', // 是否為主鍵
      'ai' => $row['extra'] === 'auto_increment', // 是否自動增加
      'type' => $type,
      'd4' => $row['default'],
    ];
  }
}

if (!function_exists('\M\transaction')) {
  function transaction($closure, &...$args) {
    if (!is_callable($closure))
      return ['transaction 使用方法錯誤！'];

    $instance = \_M\Connection::instance();

    try {
      if (!$instance->beginTransaction())
        return ['Database Transaction 失敗！'];
      
      if (call_user_func_array($closure, $args))
        return $instance->commit() ? [] : ['Database Commit 失敗！'];

      throw new \Exception('transaction 回傳 false，故 rollback！');
    } catch (\Exception $e) {
      return $instance->rollback() ? [$e->getMessage()] : ['Database Rollback 失敗！', $e->getMessage()];
    }

    return ['不明原因錯誤！'];
  }
}

if (!function_exists('\M\columnCast')) {
  function columnCast($info, $val, $isNew) {
    !$isNew && !$info['null'] && $val === null && \gg('「' . $info['name'] . '」欄位不可以為 NULL');

    if ($val === null)
      return null;
    
    switch ($info['type']) {
      case 'tinyint': case 'smallint': case 'mediumint': case 'int': case 'bigint':
        if (is_int($val)) return $val;
        elseif (is_numeric($val) && floor($val) != $val) return (int)$val;
        elseif (is_string($val) && is_float($val + 0)) return (string) $val;
        elseif (is_float($val) && $val >= PHP_INT_MAX) return number_format($val, 0, '', '');
        else return (int)$val;
      
      case 'float': case 'double': case 'numeric': case 'decimal': case 'dec':
        return (double)$val;

      case 'datetime': case 'timestamp': case 'date': case 'time':
        $tmp = \_M\DateTime::createByString($val, $info['type']);
        $tmp->isFormat() || \gg('「' . $info['name'] . '」欄位格式為「' . $info['type'] . '」，您給予的值為：' . $val);
        return $tmp;

      default:
        if ($val instanceof \_M\Uploader)
          return $val;

        return (string)$val;
    }
    return $val;
  }
}

if (!function_exists('\M\toArray')) {
  function toArray($obj) {
    $attrs = [];

    foreach ($obj->attrs() as $key => $attr) {
      if ($attr instanceof \_M\ImageUploader)
        $attrs[$key] = array_combine($keys = array_keys($attr->versions()), array_map(function($key) use ($attr) { return $attr->url($key); }, $keys));
      else if ($attr instanceof \_M\FileUploader)
        $attrs[$key] = $attr->url();
      else if ($attr instanceof \_M\DateTime)
        $attrs[$key] = (string)$attr;
      else if (isset($obj->table()->columns[$key]['type']) && in_array($obj->table()->columns[$key]['type'], ['tinyint', 'smallint', 'mediumint', 'int', 'bigint']))
        $attrs[$key] = is_int($attr) ? $attr : (is_numeric($attr) && floor($attr) != $attr ? (int)$attr : (is_string($attr) && is_float($attr + 0) ? (string)$attr : (is_float($attr) && $attr >= PHP_INT_MAX ? number_format($attr, 0, '', '') : (int)$attr)));
      else if (isset($obj->table()->columns[$key]['type']) && in_array($obj->table()->columns[$key]['type'], ['float', 'double', 'numeric', 'decimal', 'dec']))
        $attrs[$key] = (double)$attr;
      else 
        $attrs[$key] = (string)$attr;
    }

    return $attrs;
  }
}
