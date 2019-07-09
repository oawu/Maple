<?php

spl_autoload_register(function($className) {
  if (!$namespaces = getNamespaces($className))
    return false;

  if ($namespaces[0] !== 'CMD')
    return false;

  $namespaces[0] = 'Cmd';

  Load::systemLib(implode(DIRECTORY_SEPARATOR, $namespaces) . DIRECTORY_SEPARATOR . deNamespace($className));
  return class_exists($className);
});