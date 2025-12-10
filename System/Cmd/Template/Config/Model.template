<?php

return [
  'thumbnail' => 'Gd',

  'uploader' => [
    'driver' => [
      'type' => 'Local',
      'params' => [
        'storage' => PATH_PUBLIC,
      ]

      // 'type' => 'S3',
      // 'params' => [
      //   'bucket' => '',
      //   'access' => '',
      //   'secret' => '',
      //   'region' => 'ap-northeast-1',
      //   'acl' => 'public-read',
      //   'ttl' => 0,
      //   'isUseSSL' => false,
      // ]
    ],

    // 處理圖片時暫存位置
    'tmpDir' => rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR,

    // 儲存基礎目錄
    'baseDirs' => ['Storage'],

    // 基礎網址
    'baseUrl' => 'http://127.0.0.1/',

    // 不存在時預設檔案
    'defaultUrl' => 'http://127.0.0.1/404.png',

    'file' => [
      'namingSort' => ['origin', 'md5', 'random']
    ],
    'image' => [
      'namingSort' => ['md5', 'random', 'origin'],

      'versions' => [
        'w100' => [
          'method' => 'resize',
          'params' => [100, 100, 'width'],
        ],
        'c120x120' => [
          'method' => 'adaptiveResizeQuadrant',
          'params' => [120, 120, 'c']
        ]
      ]
    ]
  ]
];
