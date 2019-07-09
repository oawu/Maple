<?php

return [
  'CacheFile' => [
    'path' => PATH_FILE_CACHE
  ],
  'CacheRadis' => [
    'host' => 'localhost',
    'port' => 6379,
    'timeout' => null,
    'password' => null,
    'database' => null,
    'serializeKey' => '_maple_cache_serialized',
  ]
];