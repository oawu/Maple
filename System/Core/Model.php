<?php

Load::systemModel('Func') ?: gg('載入 System/Model/Func 失敗！');

spl_autoload_register(function($className) {
  static $init;

  if (!isset($init)) {
    Load::systemLib('Cache');
    $init = true;
  }

  if ($className == 'Where') {
    Load::systemModel('Where') ?: gg('載入 System/Model/Where 失敗！');
    return class_exists($className);
  }

  // 取得 namespace
  $namespaces = \getNamespaces($className);

  // 此 class 沒有使用 namespace
  if (!$namespaces)
    return false;
  
  // 取出第一個 namespace
  $namespace = array_shift($namespaces);

  // 檢查 namespace 是不是屬於 Model 系列
  if (!in_array($namespace, ['M', '_M']))
    return false;

  // 移除 namespace
  $modelName = \deNamespace($className);

  // 載入 sys model 的主要核心
  if ($namespace == 'M' && $modelName == 'Model') {
    Load::systemModel('Model') ?: gg('載入 System/Model/Model 失敗！');
    return class_exists($className);
  }

  // 載入 sys model 的其他物件
  if ($namespace == '_M') {

    if (preg_match('/Uploader$/', $modelName))
      include_once PATH_SYSTEM_MODEL_UPLOADER . $modelName . '.php';
    else
      include_once PATH_SYSTEM_MODEL . $modelName . '.php';
    
    return class_exists($className);
  }

  // 找尋 App Model 內的物件
  foreach (array_merge([PATH_APP_MODEL], array_filter(array_map(function($t) { return !in_array($t, ['.', '..']) ? PATH_APP_MODEL . $t . DIRECTORY_SEPARATOR : null; }, scandir(PATH_APP_MODEL)), 'is_dir')) as $tmp)
    if (is_file($tmp = $tmp . $modelName . '.php') && is_readable($tmp) && $path = $tmp)
      break;

  if (!isset($path))
    return false;

  include_once $path;

  if (!class_exists($className))
    return false;
  
  $className::finishUploader();

  return true;
}, false, true);

Status::addFuncs('Model Connection closeAll', function() {
  return \M\Model::closeDB();
});