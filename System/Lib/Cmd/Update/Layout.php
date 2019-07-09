<?php

namespace CMD\Update;

use \CMD\Layout\Menu    as Menu;
use \CMD\Layout\Check   as Check;

use \CMD\Str            as Str;
use \CMD\Tool           as Tool;
use \CMD\Display        as Display;

class Layout {
  public static function update() {
    \Load::systemFunc('File') ?: Display::error('載入 System/Func/File 失敗！');

    Display::title('開始更新');
    Display::line('下載最新的指令', \Xterm::gray('執行指令')->dim() . Display::markSemicolon() . \Xterm::gray('wget https://comdan66.github.io/Maple/maple7')->dim()->italic());
    $content = @file_get_contents('https://comdan66.github.io/Maple/maple7?=' . time());
    $content || Display::line(false, '取得最新 Maple7 指令失敗！');
    Display::line(true);

    Display::line('寫入暫存', \Xterm::gray('執行指令')->dim() . Display::markSemicolon() . \Xterm::gray('echo "..." > tmp/mapel7')->dim()->italic());
    $path = PATH_FILE_TMP . 'mapel7_' . md5(uniqid(mt_rand(), true));
    fileWrite($path, $content, 'w+b');
    file_exists($path) || Display::line(false, '寫入檔案失敗！');
    Display::line(true);
    
    Display::line('搬移指令', \Xterm::gray('執行指令')->dim() . Display::markSemicolon() . \Xterm::gray('mv tmp/mapel7 /usr/local/bin/maple7')->dim()->italic());
    @rename($path, '/usr/local/bin/maple7');
    file_exists('/usr/local/bin/maple7') || Display::line(false, '搬移檔案失敗！');
    Display::line(true);

    Display::line('變更 Maple 權限', \Xterm::gray('執行指令')->dim() . Display::markSemicolon() . \Xterm::gray('chmod +x /usr/local/bin/maple7')->dim()->italic());
    @passthru('chmod +x /usr/local/bin/maple7');
    Display::line(true);

    Display::line('檢查 Maple 權限', \Xterm::gray('執行指令')->dim() . Display::markSemicolon() . \Xterm::gray('fileperms(/usr/local/bin/maple7) == 33261')->dim()->italic());
    @fileperms('/usr/local/bin/maple7') == '33261' || Display::line(false, '權限錯誤！');
    Display::line(true);

    Display::title('完成');
    Display::markListLine('已經更新成功囉，目前已經是最新版囉！');
    Display::markListLine('重新開啟終端機後就可以使用最新版的 maple7 囉！');
    echo Display::LN;
  }

  public static function get() {
    if (!\Load::system('Env'))
      return null;

    $json = @file_get_contents('https://comdan66.github.io/Maple/version7.json?=' . time());

    if (!(isJson($json) && isset($json[VERSION]) && $json[VERSION]['to'] !== VERSION))
        return null;

    return Check::create('更新 Maple7 指令', 'Update maple7 command line')
                  ->appendTip('Maple7 目前版本為 v' . VERSION)
                  ->appendTip('線上最新版本為 v' . $json[VERSION]['to'])
                  ->appendTip('過程中若要離開，請直接按下鍵盤上的 control + c')
                  ->thing('\CMD\Update\Layout::update');
  }
}
