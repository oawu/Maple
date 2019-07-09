<?php

namespace CMD\ApiDoc;

use \CMD\Layout\Menu  as Menu;
use \CMD\Layout\Check as Check;
use \CMD\Display      as Display;

class Layout {

  public static function upload() {
    $config = \config('ApiDoc');
    $cmd = ['node upload', '--bucket ' . $config['bucket'], '--access ' . $config['access'], '--secret ' . $config['secret']];
    isset($config['folder']) && $config['folder'] !== '' && array_push($cmd, '--folder ' . $config['folder']);
    isset($config['domain']) && $config['domain'] !== '' && array_push($cmd, '--domain ' . $config['domain']);
    @passthru('cd ' . PATH_FILE_APIDOC . ' && ' . implode(' ', $cmd));
  }

  public static function run() {
    @passthru('cd ' . PATH_FILE_APIDOC . ' && node apidoc');
  }

  public static function get() {
    if (!\Load::system('Env'))
      return null;
    
    if (file_exists(PATH_FILE_APIDOC) && is_file(file_exists(PATH_FILE_APIDOC)))
      return null;

    if (!file_exists(PATH_FILE_APIDOC))
      return Check::create('初始 ApiDoc 目錄', 'Init ApiDoc Dir')
                   ->appendTip('建立 ' . \Xterm::gray('apiDoc 文件', true) . '使用的目錄。')
                   ->appendTip(Display::controlC())
                   ->thing('\CMD\Init\Layout::initApiDoc');
    
    $item1 = Check::create('編譯 API 文件', 'Compile API into apiDoc format')
                  ->appendTip('編譯 ' . \Xterm::gray('API', true) . '，並且產生文件。')
                  ->appendTip(Display::controlC())
                  ->thing('\CMD\ApiDoc\Layout::run');
    
    $menu = Menu::create('API 文件', 'API Document')
                ->appendItem($item1);

    if (!file_exists(PATH_CONFIG . ENVIRONMENT . DIRECTORY_SEPARATOR . 'ApiDoc.php'))
      return $menu;

    $config = \config('ApiDoc');

    if (!(isset($config['bucket']) && $config['bucket'] !== '' && isset($config['access']) && $config['access'] !== '' && isset($config['secret']) && $config['secret'] !== ''))
      return $menu;

    $bucket = \Xterm::gray($config['bucket'], true);
    $folder = isset($config['folder']) && $config['folder'] !== '' ? $config['folder'] : '';
    $folder = ($folder === '' ? \Xterm::black('無', true)->dim() : \Xterm::gray($folder, true));

    $item2 = Check::create('上傳 API 文件', 'Upload API to S3')
                  ->appendTip('Config 位置' . Display::markSemicolon() . \Xterm::gray('Config' . DIRECTORY_SEPARATOR . ENVIRONMENT . DIRECTORY_SEPARATOR . 'ApiDoc.php', true))
                  ->appendTip('將 ' . \Xterm::gray('API', true) . ' 文件上傳到指定的 S3 空間。')
                  ->appendTip('Bucket Name' . Display::markSemicolon() . $bucket)
                  ->appendTip('Bucket Folder' . Display::markSemicolon() . $folder)
                  ->appendTip(Display::controlC())
                  ->thing('\CMD\ApiDoc\Layout::upload');

    return Menu::create('API 文件', 'API Document')
               ->appendItem($item1)
               ->appendItem($item2);
  }
}