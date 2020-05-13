<?php

return [
  'matchIp'           => false,                 // 比對 IP
  'expiration'        => 60 * 60 * 24 * 30 * 3, // 存活週期 // 單位秒 // 三個月
  'time2Update'       => 60 * 60 * 24,          // 更新 session ID 週期 // 每天更新
  'regenerateDestroy' => true,                  // 重置 key 是否刪除舊的

  'lastRegenerateKey' => '__maple8__last__regenerate',
  'varsKey'           => '__maple8__vars',

  'cookieName' => 'MapleSession',

  'driver' => [
    'type' => 'File',
    'params' => [
      'path' => PATH_FILE_SESSION,
    ],

    // 'type' => 'Database',
    // 'params' => [
    //   'modelName' => '_SessionData',
    // ],
    
    // 'type' => 'Redis',
    // 'params' => [
    //   'prefix'   => '_:Session:',
    //   'host'     => 'localhost',
    //   'port'     => 6379,
    //   'timeout'  => null,
    //   'password' => null,
    //   'database' => null,
    // ]
  ],
];