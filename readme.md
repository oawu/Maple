# 歡迎來到 Maple 8

🍁 飛起來 🍁 飛過來

## 說明
* 這是一套 [OA Wu](https://www.ioa.tw/) 所製作的個人 [PHP](http://php.net/) 框架！
* 此框架僅支援 PHP 7.0（包含）以上。  
* 主要大功能如下：
  * 指令控制框架
  * 針對資料庫採用 Migration 管理
  * Model 採用 [Maple-ORM](https://github.com/comdan66/Maple-ORM)([2.0+](https://github.com/comdan66/Maple-ORM/releases/tag/2.0.0)) 的 [Active Record](https://zh.wikipedia.org/zh-tw/Active_Record) 設計
  * 採用 [Deployer](https://deploye4.r.org/) 快速部署至伺服器

* 製作參考如下：
  * [CodeIgniter](https://www.codeigniter.com/)
  * [OACI](https://github.com/comdan66/oaci)
  * [Maple4](https://github.com/comdan66/Maple/tree/4.0.13)
  * [Maple7](https://github.com/comdan66/Maple/tree/7.1.5)
  * [php-activerecord](https://github.com/jpfuentes2/php-activerecord)

## 初始化專案
專案最初開始通常需要一些結構目錄的建置，例如 Cache、Log 或環境檔案設定等，所以需要執行初始動作。
初始方法於專案目錄下執行指令 `php Maple.php init` 後，依據所需即可建立初始所需的目錄結構。

* 開發站 `php Maple.php init Development`
* 測試站 `php Maple.php init Testing`
* 準備站 `php Maple.php init Staging`
* 正式站 `php Maple.php init Production`


## 部署專案
專案部署更新至伺服器前請先確認以下幾項步驟：

1. 請至伺服器將專案建置起來。

2. 確認伺服器上的專案可以正常使用 `git pull`。 

3. 將伺服器上的專案初始化，方式就是在伺服器上的專案執行 `php Maple.php init` 選擇初始專案。

4. 因為部署過程會自動更新 **Migration**，故請先確認伺服器上的專案是否可正常連至資料庫。

5. 因為部署過程中會使用 [SSH](https://zh.wikipedia.org/wiki/Secure_Shell) 方式連線，所以請確認本地端是否可以使用 `公鑰` 的方式連線至伺服器。

6. 請先在本地安裝部署工具 [Deployer](https://deployer.org/)，安裝方式則執行以下指令：
  * `curl -LO https://deployer.org/deployer.phar`
  * `mv deployer.phar /usr/local/bin/dep`
  * `chmod +x /usr/local/bin/dep`

7. 請在本地專案下的 `Config/{ENVIRONMENT}/Deploy.php` 設定部署資訊。


確認以上步驟後，即可使用 Maple7 指令部署，使用方式只要在專案目錄下打開終端機，執行指令 `php Maple.php deploy` 後依據引導步驟後即可開始部署。

## 新增 Migration
在專案目錄下打開終端機，執行指令 `php Maple.php create -I` 即可。

## 新增 Model
在專案目錄下打開終端機，執行指令 `php Maple.php create -M` 即可。

## 執行 Migration
在專案目錄下打開終端機，執行指令 `php Maple.php migration` 即可。

> 更新至最新版可以下指令 `php Maple.php migration new`。  
> 重置 Migration 可以下指令 `php Maple.php migration -R`。
