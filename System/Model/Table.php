<?php

namespace _M;

class Table {
  private static $instances = [];
  private $className;
  public $tableName;
  public $columns = [];
  public $pks;

  public static function instance($className) {
    return self::$instances[$className] ?? self::$instances[$className] = new Table($className);
  }

  protected function __construct($className) {
    $this->className = $className;
    $this->tableName = $className::$tableName ?? \deNamespace($className);
    $this->getMetaData();
    $this->setPks();
  }

  private function getMetaData() {
    $sth = null;
    
    $closure = function() {
      $error = Connection::instance()->query("SHOW COLUMNS FROM " . \M\quoteName($this->tableName), [], $sth);
      $error && \gg('取得「' . $this->tableName . '」Table 的 Meta Data 失敗！', $error);
      $columns = [];

      foreach ($sth->fetchAll() as $row)
        if ($column = \M\toColumn($row))
          $columns[$column['name']] = $column;

      return $columns;
    };

    $this->columns = ENVIRONMENT === 'Production'
      ? \Cache::getData('file', '_:DB:MetaData:' . $this->tableName, 86400, $closure)
      : $closure();

    return $this;
  }

  private function setPks() {
    $className = $this->className;

    if (isset($className::$pks))
      $this->pks = \is_array($className::$pks) ? $className::$pks : [$className::$pks];
    else
      $this->pks = \array_values(\array_column(\array_filter($this->columns, function($column) { return $column['pk']; }), 'name'));

    return $this;
  }

  public function find($options) {
    $sqlBuilder = SqlBuilder::select(\M\quoteName($this->tableName), $options);

    return $this->findBySql(
      $sqlBuilder->getStr(),
      $sqlBuilder->getVals(),
      isset($options['include'])
        ? is_string($options['include'])
          ? [$options['include']]
          : $options['include']
        : []);
  }

  public function findBySql($sql, $values = [], $includes = []) {
    $sth = null;
    
    if ($error = Connection::instance()->query($sql, $values, $sth)) {
      \Log::model('查詢資料庫錯誤，錯誤原因：' . $error);
      $objs = [];
    } else {
      $objs = array_map(function($row) {
        $obj = new $this->className($row, false); return $obj->setUploadBind();
      }, $sth->fetchAll());
    }
    
    foreach ($includes as $include) {
      $name = $include;
      $include = [];

      $i = strpos($name, '.');
      if ($i !== false) {
        $tmp = substr($name, $i + 1);
        $name = substr($name, 0, $i);
        $include = ['include' => $tmp];
      }

      if (isset($this->className::$relations[$name])) {
        if (!$relation = \M\relation($this->className::$relations[$name]))
          return \gg('關聯設定錯誤！');

        $include && $relation['options'] = array_merge($relation['options'], $include);
        $this->className::relations($name, $relation, $objs);
      }
    }

    return $objs;
  }
 
  public function processDataToStr($datas) {
    foreach ($datas as $name => &$data)
      if ($data instanceof DateTime)
        $data = $data->format(null, null);
      else
        $data = $data;

    return $datas;
  }

  private function mergeWherePks($pksWithValues) {
    $where = \Where::create();
    
    foreach ($pksWithValues as $pk => $pv)
      $where->and(\M\quoteName($pk) . ' = ?', $pv);

    return $where;
  }

  public function update($datas, $pksWithValues) {
    $datas = $this->processDataToStr($datas);
    $where = $this->mergeWherePks($pksWithValues);

    if (!$sqlBuilder = SqlBuilder::update(\M\quoteName($this->tableName), $datas, ['where' => $where]))
      return !\Log::model('更新資料失敗，錯誤原因：SQL Builder 錯誤！');

    if ($error = Connection::instance()->query($sqlBuilder->getStr(), $sqlBuilder->getVals()))
      return !\Log::model('更新資料失敗，錯誤原因：' . $error);
    
    return true;
  }

  public function insert($datas) {
    $datas = $this->processDataToStr($datas);
    
    if (!$sqlBuilder = SqlBuilder::insert(\M\quoteName($this->tableName), $datas))
      return !\Log::model('新增資料失敗，錯誤原因：SQL Builder 錯誤！');;

    if ($error = Connection::instance()->query($sqlBuilder->getStr(), $sqlBuilder->getVals()))
      return !\Log::model('新增資料失敗，錯誤原因：' . $error);  ;

    return true;
  }

  public function delete($pksWithValues) {
    $where = $this->mergeWherePks($pksWithValues);

    if (!$sqlBuilder = SqlBuilder::delete(\M\quoteName($this->tableName), array_intersect_key(['where' => $where], ['where' => '', 'order' => '', 'limit' => '', 'offset' => ''])))
      return !\Log::model('刪除資料失敗，錯誤原因：SQL Builder 錯誤！');
    
    if ($error = Connection::instance()->query($sqlBuilder->getStr(), $sqlBuilder->getVals()))
      return !\Log::model('刪除資料失敗，錯誤原因：' . $error);

    return true;
  }

  public function deleteAll($options) {
    if (!$sqlBuilder = SqlBuilder::delete(\M\quoteName($this->tableName), array_intersect_key($options, ['where' => '', 'order' => '', 'limit' => '', 'offset' => ''])))
      return !\Log::model('刪除多筆資料失敗，錯誤原因：SQL Builder 錯誤！');
    
    if ($error = Connection::instance()->query($sqlBuilder->getStr(), $sqlBuilder->getVals()))
      return !\Log::model('刪除多筆資料失敗，錯誤原因：' . $error);

    return true;
  }

  
  public function updateAll($datas, $options) {
    if (!$sqlBuilder = SqlBuilder::update(\M\quoteName($this->tableName), $this->processDataToStr($datas), array_intersect_key($options, ['where' => '', 'order' => '', 'limit' => '', 'offset' => ''])))
      return !\Log::model('更新多筆資料失敗，錯誤原因：SQL Builder 錯誤！');

    if ($error = Connection::instance()->query($sqlBuilder->getStr(), $sqlBuilder->getVals()))
      return !\Log::model('更新多筆資料失敗，錯誤原因：' . $error);

    return true;
  }

  public function truncate() {
    $sqlBuilder = SqlBuilder::truncate(\M\quoteName($this->tableName));
    $error = Connection::instance()->query($sqlBuilder->getStr(), $sqlBuilder->getVals());
    $error && \gg('清除「' . \M\quoteName($this->tableName) . '」的資料失敗！', $error);
    return true;
  }
}