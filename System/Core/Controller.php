<?php

abstract class Controller {}

spl_autoload_register(function($className) {
  return preg_match('/Controller$/', $className)
    && Load::controller('_' . DIRECTORY_SEPARATOR . $className)
    && class_exists($className);
});
