<?php

spl_autoload_register(function($className) {
  if (!preg_match('/^Session/', $className))
    return false;

  Load::systemLib('Session' . DIRECTORY_SEPARATOR . $className);
  return class_exists($className);
});