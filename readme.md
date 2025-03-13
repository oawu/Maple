# 歡迎來到 Maple 9

🍁 飛起來 🍁 飛過來

## 說明
* 這是一套 [OA Wu](https://www.ioa.tw/) 所製作的個人 [PHP](https://www.php.net/) 框架！
* 此框架僅支援 (PHP 7.4.33+)[https://www.php.net/releases/7_4_33.php] （包含）以上。
* 主要大功能如下：
  * 指令控制框架
  * 針對資料庫採用 Migration 管理
  * Model 採用 [Maple-ORM](https://github.com/oawu/Maple-ORM)([9.0.1+](https://github.com/oawu/Maple-ORM/tree/9.0.1)) 的 [Active Record](https://zh.wikipedia.org/zh-tw/Active_Record) 設計

* 製作參考如下：
  * [CodeIgniter](https://www.codeigniter.com/)
  * [OACI](https://github.com/oawu/oaci)
  * [Maple4](https://github.com/oawu/Maple/tree/4.0.13)
  * [Maple7](https://github.com/oawu/Maple/tree/7.1.5)
  * [Maple8](https://github.com/oawu/Maple/tree/8.0.1)
  * [php-activerecord](https://github.com/jpfuentes2/php-activerecord)

## 初始化專案
專案最初開始通常需要一些結構目錄的建置，例如 Cache、Log 或環境檔案設定等，所以需要執行初始動作。
初始方法於專案目錄下執行指令 `php Maple.php init` 後，依據所需即可建立初始所需的目錄結構。

* 本地端 `php Maple.php init Local`
* 開發站 `php Maple.php init Development`
* 測試站 `php Maple.php init Beta`
* 準備站 `php Maple.php init Staging`
* 正式站 `php Maple.php init Production`


## 新增 Migration
在專案目錄下打開終端機，執行指令 `php Maple.php create -I` 即可。

## 新增 Model
在專案目錄下打開終端機，執行指令 `php Maple.php create -M` 即可。

## 執行 Migration
在專案目錄下打開終端機，執行指令 `php Maple.php migration` 即可。

> 更新至最新版可以下指令 `php Maple.php migration new` 或 `php Maple.php migration`。
> 重置 Migration 可以下指令 `php Maple.php migration -R`。
