<?php

spl_autoload_register(function($className) {
  if (!preg_match('/^Thumbnail/', $className))
    return false;

  Load::systemLib('Thumbnail' . DIRECTORY_SEPARATOR . $className);
  return class_exists($className);
});