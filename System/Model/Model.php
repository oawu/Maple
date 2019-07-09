<?php

namespace M;

// static $relations = [];
// static $uploaders = [];
// static $afterCreates = [];
// static $afterDeletes = [];

abstract class Model {
  private static $uploaders = [];
  
  public static $validOptions = ['where', 'limit', 'offset', 'order', 'select', 'group', 'having', 'include'];

  public static function closeDB() { return \_M\Connection::close(); }
  public static function table() { return \_M\Table::instance(get_called_class()); }

  public static function one() {   return call_user_func_array(['static', 'find'], array_merge(['one'], func_get_args())); }
  public static function first() { return call_user_func_array(['static', 'find'], array_merge(['first'], func_get_args())); }
  public static function last() {  return call_user_func_array(['static', 'find'], array_merge(['last'], func_get_args())); }
  public static function all() {   return call_user_func_array(['static', 'find'], array_merge(['all'], func_get_args())); }

  public static function find() {
    $className = get_called_class();
    $options   = func_get_args();

    $method = array_shift($options);

    in_array($method, $methods = ['one', 'first', 'last', 'all'])
      || \gg('請給予 ' . $className . ' 查詢類型，目前僅能使用 ' . implode('、', $methods) .' 類型！');

    $options = options($options);
    
    $method == 'last'
      && $options['order'] = isset($options['order']) ? reverseOrder($options['order']) : implode(' DESC, ', static::table()->pks) . ' DESC';

    $options
      && $options = array_intersect_key($options, array_flip(Model::$validOptions));
    
    in_array($method, ['one', 'first'])
      && $options = array_merge($options, ['limit' => 1, 'offset' => 0]);

    $list = static::table()->find($options);

    return $method != 'all' ? ($list[0] ?? null) : $list;
  }

  public static function count() {
    $options = options(func_get_args());
    $options['select'] = 'COUNT(*)';
    unset($options['group']);

    if (!$objs = call_user_func_array(['static', 'find'], ['all', $options]))
      return 0;

    if (!$objs = array_shift($objs))
      return 0;

    if (!$objs = $objs->attrs())
      return 0;
    
    return intval(array_shift($objs));
  }

  public static function counts() {
    $options = options(func_get_args());
    $options['select'] = 'COUNT(*)' . (!empty($options['select']) ? ',' . $options['select'] : '');
    return array_map(function($obj) { return count($obj = $obj->attrs()) > 1 ? $obj : intval(array_shift($obj)); }, call_user_func_array(['static', 'find'], ['all', $options]));
  }

  public static function defaultAttrs($attrs) {
    $className = get_called_class();
    $columns = static::table()->columns;
    
    isset($columns['createAt'])
      && !array_key_exists('createAt', $attrs)
      && $attrs['createAt'] = \date($columns['createAt']['type'] == 'datetime' ? \_M\DateTime::FORMAT_DATETIME : \_M\DateTime::FORMAT_DATE);
    
    isset($columns['updateAt'])
      && !array_key_exists('updateAt', $attrs)
      && $attrs['updateAt'] = \date($columns['updateAt']['type'] == 'datetime' ? \_M\DateTime::FORMAT_DATETIME : \_M\DateTime::FORMAT_DATE);
    
    return array_merge(array_map(function($attr) {
      return $attr['null'] === false
        && $attr['d4'] === null
        && !in_array($attr['type'], ['tinyint', 'smallint', 'mediumint', 'int', 'bigint', 'float', 'double', 'numeric', 'decimal', 'dec', 'datetime', 'timestamp', 'date', 'time'])
          ? ''
          : ($attr['type'] === 'datetime' && $attr['d4'] === 'CURRENT_TIMESTAMP' ? \date(\_M\DateTime::FORMAT_DATETIME) : $attr['d4']);
    }, $columns), array_intersect_key($attrs, $columns));
  }

  public static function create($attrs = []) {
    $className = get_called_class();
    $model = new $className(static::defaultAttrs($attrs), true);
    return $model->setUploadBind()->save() ? $model : null;
  }

  public static function truncate() {
    return static::table()->truncate();
  }

  public static function deleteAll() {
    return static::table()->deleteAll(options(func_get_args()));
  }

  public static function updateAll($set, $options = []) {
    $options = func_get_args();
    $set = array_shift($options);

    if (!isAssoc($set))
      return !\Log::model('更新多筆資料失敗，錯誤原因：updateAll 格式錯誤，set 請使用 key-value 的陣列格式！');

    return static::table()->updateAll(array_intersect_key($set, static::table()->columns), options($options));
  }
  
  public static function relations($name, $relation, $objs) {
    if (!$objs) return ;
    
    $options = $relation['options'];
    
    $className = '\\M\\' . $relation['modelName'];
    $tableName = $className::table()->tableName;

    if ($relation['type'] == 'has') {
      $pk = empty($options['pk']) ? 'id' : $options['pk'];
      $fk = empty($options['fk']) ? lcfirst(static::table()->tableName) . 'Id' : $options['fk'];

      $vals = []; foreach ($objs as $obj) $vals[$obj->$pk] = true;

      $where = \Where::create(quoteName($fk) . ' IN (?)', array_unique(array_keys($vals)));
      $options['where'] = isset($options['where']) ? $options['where']->and($where) : $where;
      isset($options['select']) && $options['select'] .= ',' . $fk;
      $options = array_intersect_key($options, array_flip(Model::$validOptions));

      $relationObjs = $className::all($options);

      $tmps = [];
      foreach ($relationObjs as $relationObj) 
        if (isset($tmps[$relationObj->$fk])) array_push($tmps[$relationObj->$fk], $relationObj);
        else $tmps[$relationObj->$fk] = [$relationObj];

      foreach ($objs as $obj)
        if (isset($tmps[$obj->$pk])) $obj->relations[$name] = $relation['method'] == 'one' ? $tmps[$obj->$pk][0] ? $tmps[$obj->$pk][0] : null : $tmps[$obj->$pk];
        else $obj->relations[$name] = $relation['method'] == 'one' ? null : [];

    } else {
      $pk = empty($options['pk']) ? 'id' : $options['pk'];
      $fk = empty($options['fk']) ? lcfirst($tableName) . 'Id' : $options['fk'];

      $vals = []; foreach ($objs as $obj) $vals[$obj->$fk] = true;
      
      $where = \Where::create(quoteName($pk) . ' IN (?)', array_unique(array_keys($vals)));
      $options['where'] = isset($options['where']) ? $options['where']->and($where) : $where;
      isset($options['select']) && $options['select'] .= ',' . $pk;
      $options = array_intersect_key($options, array_flip(Model::$validOptions));

      $relationObjs = $className::all($options);

      $tmps = [];
      foreach ($relationObjs as $relationObj) 
        if (isset($tmps[$relationObj->$pk])) array_push($tmps[$relationObj->$pk], $relationObj);
        else $tmps[$relationObj->$pk] = [$relationObj];

      foreach ($objs as $obj)
        if (isset($tmps[$obj->$fk])) $obj->relations[$name] = $relation['method'] == 'one' ? $tmps[$obj->$fk] ? $tmps[$obj->$fk][0] : null : $tmps[$obj->$fk];
        else $obj->relations[$name] = $relation['method'] == 'one' ? null : [];
    }

    return $tmps = $pk = $fk = null;
  }

  public static function imageUploader($column) {
    $className = get_called_class();
    self::$uploaders[$className] = self::$uploaders[$className] ?? [];
    return self::$uploaders[$className][$column] = new \_M\TmpUploader('image');
  }

  public static function fileUploader($column) {
    $className = get_called_class();
    self::$uploaders[$className] = self::$uploaders[$className] ?? [];
    return self::$uploaders[$className][$column] = new \_M\TmpUploader('file');
  }
  
  public static function finishUploader() {
    $className = get_called_class();
    self::$uploaders[$className] = self::$uploaders[$className] ?? [];
    $tmps = [];

    foreach (self::$uploaders[$className] as $column => $uploader)
      $tmps[$column] = $uploader->toArray();

    self::$uploaders[$className] = $tmps;
  }

  private $attrs = [];
  private $dirty = [];
  private $isNew = true;
  private $relations = [];

  public function __construct($attrs = [], $isNew = true) {
    $this->setAttrs(!$attrs && $isNew ? static::defaultAttrs($attrs) : $attrs, $isNew)
         ->cleanFlagDirty();

    if ($this->isNew = $isNew)
      foreach (array_keys($this->attrs) as $key)
        $this->flagDirty($key);
  }

  public function attrs($key = null, $d4 = null) {
    return $key !== null ? array_key_exists($key, $this->attrs) ? $this->attrs[$key] : $d4 : $this->attrs;
  }

  private function updateAttr($name, $value, $isNew = false) {
    $this->attrs[$name] = \M\columnCast(static::table()->columns[$name], $value, $isNew);
    $this->flagDirty($name);
    return $value;
  }

  private function cleanFlagDirty() {
    $this->dirty = [];
    return $this;
  }

  private function flagDirty($name = null) {
    $this->dirty[$name] = true;
    return $this;
  }

  private function setAttrs($attrs, $isNew) {
    foreach ($attrs as $name => $value)
      if (isset(static::table()->columns[$name]))
        $this->updateAttr($name, $value, $isNew);
      else
        $this->attrs[$name] = $value;
    return $this;
  }

  private function setIsNew($isNew) {
    if ($this->isNew = $isNew)
      array_map([$this, 'flagDirty'], array_keys($this->attrs));
    return $this;
  }

  private function relation($relation) {
    if (!$relation = relation($relation))
      return \gg('關聯設定錯誤！');

    $method = $relation['method'];
    $options = $relation['options'];
    
    $className = '\\M\\' . $relation['modelName'];
    $tableName = $className::table()->tableName;

    if ($relation['type'] == 'has') {
      $pk = empty($options['pk']) ? 'id' : $options['pk'];
      $where = \Where::create(quoteName(empty($options['fk']) ? lcfirst(static::table()->tableName) . 'Id' : $options['fk']) . '= ?', $this->$pk);
    } else {
      $fk = empty($options['fk']) ? lcfirst($tableName) . 'Id' : $options['fk'];
      $where = \Where::create(quoteName(empty($options['pk']) ? 'id' : $options['pk']) . '= ?', $this->$fk);
    }

    $options['where'] = isset($options['where']) ? $options['where']->and($where) : $where;
    $options = array_intersect_key($options, array_flip(Model::$validOptions));

    return $className::$method($options);
  }

  public function __isset($name) {
    return array_key_exists($name, $this->attrs);
  }

  public function &__get($name) {
    if (array_key_exists($name, $this->attrs))
      return $this->attrs[$name];
    
    $className = get_called_class();

    if (array_key_exists($name, $this->relations))
      return $this->relations[$name];

    if (isset($className::$relations[$name]))
      $this->relations[$name] = $this->relation($className::$relations[$name]);

    if (array_key_exists($name, $this->relations))
      return $this->relations[$name];

    return \gg('找不到名稱為「' . $name . '」此物件變數！');
  }

  public function __set($name, $value) {
    if (array_key_exists($name, $this->attrs))
      if (isset(static::table()->columns[$name]))
        return $this->updateAttr($name, $value);
      else
        return $this->attrs[$name] = $value;

    return \gg('找不到名稱為「' . $name . '」此物件變數！');
  }
  
  public function save() {
    return $this->isNew
      ? $this->insert()
      : $this->update();
  }

  private function update() {
    isset(static::table()->columns['updateAt'])
      && array_key_exists('updateAt', $this->attrs)
      && !array_key_exists('updateAt', $this->dirty)
      && $this->updateAttr('updateAt', \date(static::table()->columns['updateAt']['type'] == 'datetime'
                                         ? \_M\DateTime::FORMAT_DATETIME
                                         : \_M\DateTime::FORMAT_DATE));

    if (!$dirty = array_intersect_key($this->attrs, $this->dirty))
      return true;

    if (!$pksWithValues = $this->pksWithValues())
      return !\Log::model('更新資料失敗，錯誤原因：找不到 Primary Key！');

    return static::table()->update($dirty, $pksWithValues);
  }

  private function pksWithValues() {
    $tmp = [];
    
    foreach (static::table()->pks as $pk)
      if (array_key_exists($pk, $this->attrs))
        $tmp[$pk] = $this->$pk;

    return $tmp;
  }

  public function insert() {
    $this->attrs = array_intersect_key($this->attrs, static::table()->columns);

    if (!static::table()->insert($this->attrs))
      return false;

    foreach (static::table()->pks as $pk)
      if (isset(static::table()->columns[$pk]) && static::table()->columns[$pk]['ai'])
        $this->attrs[$pk] = (int)\_M\Connection::instance()->lastInsertId();

    $this->setIsNew(false)
         ->cleanFlagDirty();

    $afterCreates = empty(static::$afterCreates) ? [] : static::$afterCreates;
    is_array($afterCreates) || $afterCreates = [$afterCreates];

    foreach ($afterCreates as $afterCreate) {
      if (!method_exists($this, $afterCreate))
        \gg('Model「' . get_called_class() . '」內沒有名為「' . $afterCreate . '」的 method！');

      if (!$this->$afterCreate())
        return false;
    }

    return true;
  }

  public function delete() {
    if (!$pksWithValues = $this->pksWithValues())
      return !\Log::model('刪除資料失敗，錯誤原因：找不到 Primary Key！');

    if (!static::table()->delete($pksWithValues))
      return false;

    $afterDeletes = empty(static::$afterDeletes) ? [] : static::$afterDeletes;
    is_array($afterDeletes) || $afterDeletes = [$afterDeletes];

    foreach ($afterDeletes as $afterDelete) {
      if (!method_exists($this, $afterDelete))
        \gg('Model「' . get_called_class() . '」內沒有名為「' . $afterDelete . '」的 method！');

      if (!$this->$afterDelete())
        return false;
    }

    return true;
  }

  public function setColumns($attrs = []) {
    if ($attrs = array_intersect_key($attrs, $this->attrs()))
      foreach ($attrs as $column => $value)
        $this->$column = $value;
    return true;
  }
  
  public function setUploadBind() {
    $className = get_called_class();
    $uploaders = self::$uploaders[$className] ?? [];

    foreach ($uploaders as $column => $uploader)
      if (in_array($column, array_keys($this->attrs())))
        $uploader['class']::bind($this, $column, $uploader);

    return $this;
  }
  
  public function toArray() {
    return toArray($this);
  }

  public function putFiles($files) {
    foreach ($files as $key => $file)
      if ($file && isset($this->$key) && $this->$key instanceof \_M\Uploader && !$this->$key->put($file))
        return false;
    return true;
  }
}