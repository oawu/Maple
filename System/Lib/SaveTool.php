<?php

spl_autoload_register(function($className) {
  if (!preg_match('/^SaveTool/', $className))
    return false;

  Load::systemLib('SaveTool' . DIRECTORY_SEPARATOR . $className);
  return class_exists($className);
});