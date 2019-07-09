<?php

spl_autoload_register(function($className) {
  if (!preg_match('/^Cache/', $className))
    return false;

  Load::systemLib('Cache' . DIRECTORY_SEPARATOR . $className);
  return class_exists($className);
});