<?php

return [
  'uploader' => [
    'driver' => [
      'type' => 'Local',
      'params' => [
        'dir' => PATH_PUBLIC,
      ]

      // 'type' => 'S3',
      // 'params' => [
      //   'bucket' => '',
      //   'access' => '',
      //   'secret' => '',
      // ]
    ],

    // 處理圖片時暫存位置
    'tmpDir' => rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR,
    
    // 儲存基礎目錄
    'baseDirs' => ['Storage'],

    // 基礎網址
    'baseURL' => 'http://127.0.0.1/',

    // 不存在時預設檔案
    'default' => 'http://127.0.0.1/404.png',
  ],

  'imageVersions' => [
    'w100' => ['resize', [100, 100, 'width']],
    'c120x120' => ['adaptiveResizeQuadrant', [120, 120, 'c']]
  ]
];