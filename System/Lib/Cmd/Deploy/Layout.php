<?php

namespace CMD\Deploy;

use \CMD\Layout\Menu  as Menu;
use \CMD\Layout\Check as Check;
use \CMD\Str          as Str;
use \CMD\Tool         as Tool;
use \CMD\Display      as Display;

class Layout {
  private static function checkConfig($stage) {
    Display::line('取得 Deploy Config', \Xterm::gray('檔案位置')->dim() . Display::markSemicolon() . \Xterm::gray('Config' . DIRECTORY_SEPARATOR . ENVIRONMENT . DIRECTORY_SEPARATOR . 'Deploy.php')->dim()->italic());

    $config = null;
    foreach (\config('Deploy') as $deploy)
      if (($deploy['stage'] == $stage) && ($config = $deploy))
        break;

    if (!isset($config))
      Display::line(false, '找不定正確的 Deploy Config！');
    else
      Display::line(true);

    return $config;
  }

  private static function checkDepCmd() {
    Display::line('檢查是否可執行 dep 指令', \Xterm::gray('執行指令')->dim() . Display::markSemicolon() . \Xterm::gray('which dep')->dim()->italic());
    $result = !empty(shell_exec(sprintf('which %s', escapeshellarg('dep'))));
    $result
      || Display::line(false, '找不定正確的 Dep 指令，請先安裝 dep 指令吧！');

    return Display::line(true, '可以');
  }

  private static function writeDeploy($config) {
    Display::line('建立 deploy.php 部署檔案', \Xterm::gray('執行指令')->dim() . Display::markSemicolon() . \Xterm::gray('echo "..." >> deploy.php')->dim()->italic());

    \Load::systemFunc('File')
      || Display::line(false, '載入 System/Func/File 失敗！');

    $path = PATH_FILE_TMP . 'deploy.php';
    $deployStr = Tool::getTemplate('Deploy.template', ['config' => $config]);

    !is_dir($path)
      || Display::line(false, '已存在相同名稱的目錄！');

    fileWrite($path, $deployStr);

    is_readable($path)
      || Display::line(false, '寫入檔案失敗！');
    
    return Display::line(true);
  }

  public static function deploy() {
    $args  = func_get_args();
    $check = array_shift($args);
    $stage = array_shift($args);

    Display::title('本地環境檢查');

    $config = self::checkConfig($stage);

    $result = self::checkDepCmd();
    $result = self::writeDeploy($config);

    @passthru('cd ' . PATH_FILE_TMP . ' && dep deploy ' . $stage);
  }

  public static function get() {
    if (!\Load::system('Env'))
      return null;

    if (!file_exists(PATH_CONFIG . ENVIRONMENT . DIRECTORY_SEPARATOR . 'Deploy.php'))
      return null;

    $items = array_map(function($deploy) {
      return Check::create('部署至' . $deploy['name'], 'Deploy ' . $deploy['stage'])
                  ->appendTip('Config 位置' . Display::markSemicolon() . \Xterm::gray('Config' . DIRECTORY_SEPARATOR . ENVIRONMENT . DIRECTORY_SEPARATOR . 'Deploy.php', true))
                  ->appendTip('此功能是將' . \Xterm::gray('專案部署至指定的伺服器', true) . '。')
                  ->appendTip('將使用 ' . Display::colorBoldWhite('Deployer') . ' SSH 至伺服器上操作更新。')
                  ->appendTip('在伺服器的專案目錄下使用 ' . \Xterm::gray('Git Pull', true) . ' 來達成更新專案。')
                  ->appendTip('部署完成後會一並執行 ' . \Xterm::gray('Migration 更新至最新版本', true) . '，並且' . \Xterm::gray('清除快取', true) . '與' . \Xterm::gray('暫存檔案', true) . '。')
                  ->appendTip(Display::controlC())
                  ->thing('\CMD\Deploy\Layout::deploy', $deploy['stage']);
    }, \config('Deploy'));
    
    return Menu::create('部署專案', 'Deploy Project')
               ->appendItems($items);
  }
}