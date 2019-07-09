<?php

namespace _M;

class SqlBuilder {
  private $str = '';
  private $vals = [];

  protected function __construct($str, $vals) {
    $count = substr_count($str, '?');
    $count <= count($vals) || \gg('SqlBuilder 參數錯誤。「' . $str . '」 有 ' . count($vals) . ' 個參數，目前只給 ' . $count . ' 個。');

    $this->str = $str;
    $this->vals = array_slice($vals, 0, $count);
  }

  public function getStr() {
    return $this->str;
  }

  public function getVals() {
    return $this->vals;
  }

  public static function select($quoteTableName, $options) {
    $vals = [];
    $strs = ['SELECT'];

    array_push($strs, empty($options['select']) ? '*' : $options['select']);
    array_push($strs, 'FROM');
    array_push($strs, $quoteTableName);
    
    if (!empty($options['where']) && $options['where']->getStr() != '') {
      $vals = $options['where']->getVals();
      array_push($strs, 'WHERE', $options['where']->getStr());
    }

    empty($options['group'])  || array_push($strs, 'GROUP BY', $options['group']);
    empty($options['having']) || array_push($strs, 'HAVING', $options['having']);
    empty($options['order'])  || array_push($strs, 'ORDER BY', $options['order']);

    $limit = empty($options['limit']) ? 0 : intval($options['limit']);
    $offset = empty($options['offset']) ? 0 : intval($options['offset']);

    if ($limit || $offset)
      array_push($strs, 'LIMIT', intval($offset) . ', ' . intval($limit));

    return new SqlBuilder(implode(' ', array_filter($strs)), $vals);
  }

  public static function update($quoteTableName, $datas, $options) {
    if (!$datas)
      return null;

    $sets = $vals = [];
    foreach ($datas as $key => $val) {
      array_push($sets, \M\quoteName($key) . ' = ?');
      array_push($vals, $val);
    }

    if (!$sets)
      return null;

    $strs = ['UPDATE'];
    array_push($strs, $quoteTableName);
    array_push($strs, 'SET');
    array_push($strs, implode(', ', $sets));

    if (!empty($options['where']) && $options['where']->getStr() != '') {
      $vals = array_merge($vals, $options['where']->getVals());
      array_push($strs, 'WHERE', $options['where']->getStr());
    }

    empty($options['order']) || array_push($strs, 'ORDER BY', $options['order']);

    $limit = empty($options['limit']) ? 0 : intval($options['limit']);
    $offset = empty($options['offset']) ? 0 : intval($options['offset']);

    if ($limit || $offset)
      array_push($strs, 'LIMIT', intval($offset) . ', ' . intval($limit));

    return new SqlBuilder(implode(' ', array_filter($strs)), $vals);
  }

  public static function delete($quoteTableName, $options) {

    $strs = ['DELETE'];
    array_push($strs, 'FROM');
    array_push($strs, $quoteTableName);

    $vals = [];
    if (!empty($options['where']) && $options['where']->getStr() != '') {
      $vals = array_merge($vals, $options['where']->getVals());
      array_push($strs, 'WHERE', $options['where']->getStr());
    }
    
    empty($options['order']) || array_push($strs, 'ORDER BY', $options['order']);

    $limit = empty($options['limit']) ? 0 : intval($options['limit']);
    $offset = empty($options['offset']) ? 0 : intval($options['offset']);

    if ($limit || $offset)
      array_push($strs, 'LIMIT', intval($offset) . ', ' . intval($limit));

    return new SqlBuilder(implode(' ', array_filter($strs)), $vals);
  }

  public static function insert($quoteTableName, $datas) {

    $keys = $vals = [];
    foreach ($datas as $key => $val) {
      array_push($keys, \M\quoteName($key));
      array_push($vals, $val);
    }

    $strs = ['INSERT'];
    array_push($strs, 'INTO');
    array_push($strs, $quoteTableName);
    array_push($strs, '(' . implode(', ', $keys) . ')');
    array_push($strs, 'VALUES');
    array_push($strs, '(' . implode(', ', array_fill(0, count($keys), '?')) . ')');

    return new SqlBuilder(implode(' ', array_filter($strs)), $vals);
  }

  public static function truncate($quoteTableName) {
    $strs = ['TRUNCATE'];
    array_push($strs, 'TABLE');
    array_push($strs, $quoteTableName);

    return new SqlBuilder(implode(' ', array_filter($strs)), []);
  }
}
