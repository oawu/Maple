<?php

// 載入模型
require_once PATH_SYSTEM . 'Core/Model.php';

// 載入路由
Router::init(PATH . 'Router' . DIRECTORY_SEPARATOR);

// 執行路由
try {
  Response::output(Router::execute());
} catch (Exception $error) {
  throw $error;
}
