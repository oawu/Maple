<?php

return [
  'up' => "CREATE TABLE `User` (
    `id`        int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',

    `name`      varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '名稱',
    `owner`     varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '所屬',
    `tip`       varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '提示',
    `address`   varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '地址',

    `updateAt`  datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    `createAt`  datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '新增時間',
    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='User 註解';",

  'down' => "DROP TABLE IF EXISTS `User`;",

  'at' => "2023-12-16 14:41:11"
];

# 欄位格式
  # 主鍵
    // `id`        int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',

  # 外鍵
    // `userId`    int(10) unsigned NOT NULL COMMENT 'User ID',
    // `userId`    int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'User ID',

  # 整數
    // `sort`      int(10) unsigned NOT NULL DEFAULT 0 COMMENT '排序 DESC',

  # 字串
    // `cover`     varchar(50)  COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '封面',
    // `title`     varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '標題',
    // `content`   text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '內容',

  # 列舉
    // `enable`    enum('yes', 'no') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '啟用',
    // `enable`    enum('yes', 'no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no' COMMENT '啟用',

  # 小數
    // `price`     decimal(10,2) NOT NULL DEFAULT '0.00',

# 資料表
  # 新增
    // CREATE TABLE `{資料表名稱}` (
    //   `id`        int(10) unsigned NOT NULL AUTO_INCREMENT,
    //   [{欄位格式}]
    //   `updateAt`  datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    //   `createAt`  datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '新增時間',
    //   PRIMARY KEY (`id`),
    //   KEY `userId_index` (`userId`)
    // ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='資料表註解';
  
  
  # 刪除
    // DROP TABLE IF EXISTS `{資料表名稱}`;

  # 清空
    // TRUNCATE TABLE `{資料表名稱}`;

# 欄位
  # 新增
    // ALTER TABLE `{資料表名稱}` ADD {新增的欄位格式} AFTER `{哪個欄位之後}`;",

  # 刪除
    // ALTER TABLE `{資料表名稱}` DROP COLUMN `{欄位名稱}`;

  # 變更
    // ALTER TABLE `{資料表名稱}` CHANGE `{原欄位名稱}` {新欄位格式}

#          Name || Bytes ||                  Min | Max                 || Min | Max
# --------------------------------------------------------------------------------------------
#    tinyint(3) ||    1  ||                 -128 | 127                 ||   0 | 255
#   smallint(5) ||    2  ||               -32768 | 32767               ||   0 | 65535
#  mediumint(8) ||    3  ||             -8388608 | 8388607             ||   0 | 16777215
#       int(10) ||    4  ||          -2147483648 | 2147483647          ||   0 | 4294967295
#    bigint(20) ||    8  || -9223372036854775808 | 9223372036854775807 ||   0 | 18446744073709551615
