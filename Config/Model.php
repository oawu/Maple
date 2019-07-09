<?php

// Local
return [
  'uploader' => [
    'saveDir' => 'Storage',
    'tmpDir' => PATH_FILE_TMP,
    'baseUrl' => '/',
    'thumbnailTool' => [
      'class' => 'ThumbnailGd'
      // 'class' => 'ThumbnailImagick'
    ],
    'saveTool' => [
      'class' => 'SaveToolLocal',
      'params' => [PATH_PUBLIC],
    ],
    'deleteLast' => false,
  ]
];

// // S3
// return [
//   'uploader' => [
//     'saveDir' => 'Storage/' . ENVIRONMENT,
//     'tmpDir' => PATH_FILE_TMP,
//     'baseUrl' => 'https://domain/',
//     'thumbnailTool' => [
//       // 'class' => 'ThumbnailGd'
//       'class' => 'ThumbnailImagick'
//     ],
//     'saveTool' => [
//       'class' => 'SaveToolS3',
//       'params' => ['', '', '']
//     ],
//     'deleteLast' => false,
//   ]
// ];