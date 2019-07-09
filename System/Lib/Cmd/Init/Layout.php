<?php

namespace CMD\Init;

use \CMD\Layout\Menu  as Menu;
use \CMD\Layout\Check as Check;
use \CMD\Str          as Str;
use \CMD\Tool         as Tool;
use \CMD\Display      as Display;

class Layout {
  private static function dirsMaker($dirs, $path, &$success, &$errors) {
    foreach ($dirs as $dir => $subs) {
      $data = array_map('trim', explode('|', $dir));
      
      $dir = array_shift($data);
      $data || $data = [];

      $isD = is_array($subs);
      $isX = in_array('x', $data);

      if ($isD) {
        if (is_file($path . $dir)) {
          array_push($errors, ['路徑' => Tool::depath($path . $dir), '類型' => '目錄', '原因' => '已存相同名稱的檔案']);
          self::dirsMaker($subs, $path . $dir . DIRECTORY_SEPARATOR, $success, $errors);
          continue;
        }

        $newPath = $path . $dir . DIRECTORY_SEPARATOR;
        $messages = ['路徑' => Tool::depath($newPath), '類型' => '目錄'];

        if (!is_dir($path . $dir . DIRECTORY_SEPARATOR)) {
          $messages['狀態'] = '已新增';

          if (!is_dir($path)) {
            array_push($errors, ['路徑' => Tool::depath($path . $dir), '類型' => '目錄', '原因' => '父層目錄 ' . Tool::depath($path) . ' 不存在']);
            self::dirsMaker($subs, $newPath, $success, $errors);
            continue;
          }

          if (!is_writable($path)) {
            array_push($errors, ['路徑' => Tool::depath($path . $dir), '類型' => '目錄', '原因' => '父層目錄 ' . Tool::depath($path) . ' 不可讀寫']);
            self::dirsMaker($subs, $newPath, $success, $errors);
            continue;
          }

          umaskMkdir($newPath, 0777, true);
        } else {
          $messages['狀態'] = '已存在';
        }

        if (!is_dir($newPath)) {
          array_push($errors, ['路徑' => Tool::depath($newPath), '類型' => '目錄', '原因' => '產生失敗']);
          self::dirsMaker($subs, $newPath, $success, $errors);
          continue;
        }

        array_push($success, $messages);
        self::dirsMaker($subs, $newPath, $success, $errors);
      } else {
        if (is_dir($path . $dir)) { array_push($errors, ['路徑' => Tool::depath($path . $dir), '類型' => '檔案', '原因' => '已存相同名稱的目錄']); continue; }
        if (!is_dir($path)) { array_push($errors, ['路徑' => Tool::depath($path . $dir), '類型' => '檔案', '原因' => '父層目錄 ' . Tool::depath($path) . ' 不存在']); continue; }
        if (!is_writable($path)) { array_push($errors, ['路徑' => Tool::depath($path . $dir), '類型' => '檔案', '原因' => '父層目錄 ' . Tool::depath($path) . ' 不可讀寫']); continue; }

        $newPath = $path . $dir;
        $messages = ['路徑' => Tool::depath($newPath), '類型' => '檔案'];

        if (is_file($newPath)) {
          if ($isX) { $messages['狀態'] = '已存在並已覆寫'; \fileWrite($newPath, $subs); }
          else { $messages['狀態'] = '已存在並未覆寫'; } }
        else { $messages['狀態'] = '不存在但已新增'; \fileWrite($newPath, $subs); }

        if (!is_file($newPath) || $isX) \fileWrite($newPath, $subs);
        if (!is_file($newPath)) { array_push($errors, ['路徑' => Tool::depath($newPath), '類型' => '目錄', '原因' => '寫入失敗']); continue; }

        array_push($success, $messages);
      }
    }
  }

  private static function display($result) {
    if ($result['success']) {
      $max = max(array_map('CMD\Str::width', array_column($result['success'], '路徑')));
      Display::title('完成');
      Display::markListLines(array_map(function($success) use ($max) {
        return $success['類型'] . Display::markSemicolon() . \Xterm::gray(sprintf('%-' . $max . 's', $success['路徑']), true)->dim() . Str::repeat() . \Xterm::create('─')->dim() . Str::repeat() . $success['狀態'];
      }, $result['success']));
    }

    if ($result['errors']) {
      $max = max(array_map('CMD\Str::width', array_column($result['errors'], '路徑')));
      Display::titleError('失敗');
      Display::markListLines(array_map(function($error) use ($max) {
        return $error['類型'] . Display::markSemicolon() . \Xterm::gray(sprintf('%-' . $max . 's', $error['路徑']), true)->dim() . Str::repeat() . \Xterm::create('─')->dim() . Str::repeat() . $error['原因'];
      }, $result['errors']));
    }

    echo Display::LN;
  }

  public static function initApiDoc() {
    \Load::systemFunc('File') ?: Display::error('載入 System/Func/File 失敗！');
    
    $dirs = [
      'Config' => [
        ENVIRONMENT => [
          'ApiDoc.php' => \fileRead(PATH_CONFIG . 'ApiDoc.php'),
        ]
      ],
      'File' => [
        'ApiDoc' => [
          'Lib' => [
            'Argv.js' => Tool::getFile('ApiDoc' . DIRECTORY_SEPARATOR . 'Lib' . DIRECTORY_SEPARATOR . 'Argv.js'),
            'S3.js' => Tool::getFile('ApiDoc' . DIRECTORY_SEPARATOR . 'Lib' . DIRECTORY_SEPARATOR . 'S3.js'),
            'Display.js' => Tool::getFile('ApiDoc' . DIRECTORY_SEPARATOR . 'Lib' . DIRECTORY_SEPARATOR . 'Display.js'),
            'EnvCheck.js' => Tool::getFile('ApiDoc' . DIRECTORY_SEPARATOR . 'Lib' . DIRECTORY_SEPARATOR . 'EnvCheck.js'),
            'Maple.js' => Tool::getFile('ApiDoc' . DIRECTORY_SEPARATOR . 'Lib' . DIRECTORY_SEPARATOR . 'Maple.js'),
            'OpenServer.js' => Tool::getFile('ApiDoc' . DIRECTORY_SEPARATOR . 'Lib' . DIRECTORY_SEPARATOR . 'OpenServer.js'),
            'WatchPHP.js' => Tool::getFile('ApiDoc' . DIRECTORY_SEPARATOR . 'Lib' . DIRECTORY_SEPARATOR . 'WatchPHP.js'),
            'Xterm.js' => Tool::getFile('ApiDoc' . DIRECTORY_SEPARATOR . 'Lib' . DIRECTORY_SEPARATOR . 'Xterm.js'),
          ],
          'Template' => [
            'i.css' => Tool::getFile('ApiDoc' . DIRECTORY_SEPARATOR . 'Template' . DIRECTORY_SEPARATOR . 'i.css'),
            'i.js' => Tool::getFile('ApiDoc' . DIRECTORY_SEPARATOR . 'Template' . DIRECTORY_SEPARATOR . 'i.js'),
            'index.html' => Tool::getFile('ApiDoc' . DIRECTORY_SEPARATOR . 'Template' . DIRECTORY_SEPARATOR . 'index.html'),
          ],
          'Output' => [],
          'apidoc' => Tool::getFile('ApiDoc' . DIRECTORY_SEPARATOR . 'apidoc.js'),
          'upload' => Tool::getFile('ApiDoc' . DIRECTORY_SEPARATOR . 'upload.js'),
          'apidoc.json' => Tool::getFile('ApiDoc' . DIRECTORY_SEPARATOR . 'apidoc.json'),
          'package.json' => Tool::getFile('ApiDoc' . DIRECTORY_SEPARATOR . 'package.json'),
        ],
      ],
    ];

    $success = $errors = [];
    self::dirsMaker($dirs, PATH, $success, $errors);

    self::display([
      'success' => array_values($success),
      'errors' => array_values($errors),
    ]);
  }

  public static function initEnv() {
    $args  = func_get_args();
    $check = array_shift($args);
    $env   = array_shift($args);

    \Load::systemFunc('File') ?: Display::error('載入 System/Func/File 失敗！');
    
    $dirs = [
      'App' => [
        'Func' => [],
        'Lib' => [],
        'Model' => [],
      ],
      'Config' => [
        $env => [
          'Database.php' => \fileRead(PATH_CONFIG . 'Database.php'),
          'Deploy.php' => \fileRead(PATH_CONFIG . 'Deploy.php'),
          'Model.php' => \fileRead(PATH_CONFIG . 'Model.php'),
        ]
      ],
      'Migration' => [],
      'System' => [
        'Env.php | x' => Tool::getTemplate('Env.template', ['env' => $env])
      ],
      'File' => [
        'Log' => [],
        'Cache' => [],
        'Session' => [],
        'Tmp' => [],
      ],
      'Public' => [
        'Storage' => []
      ]
    ];

    $success = $errors = [];
    self::dirsMaker($dirs, PATH, $success, $errors);

    self::display([
      'success' => array_values($success),
      'errors' => array_values($errors),
    ]);
  }

  public static function get() {
    $items = array_map(function($item) {
      return Check::create($item[0], $item[1] . ' Environment')
                  ->appendTip('建立 System 目錄下的 ' . \Xterm::gray('Env.php', true) . ' 檔案。')
                  ->appendTip('建立 App    目錄下的 ' . \Xterm::gray('Func', true) . \Xterm::create('、')->dim() . \Xterm::gray('Lib', true) . ' 目錄。')
                  ->appendTip('建立 File   目錄與其下的 ' . \Xterm::gray('Log', true) . \Xterm::create('、')->dim() . \Xterm::gray('Cache', true) . \Xterm::create('、')->dim() . \Xterm::gray('Session', true) . \Xterm::create('、')->dim() . \Xterm::gray('Tmp', true) . ' 目錄。')
                  ->appendTip('建立 Public 目錄下的 ' . \Xterm::gray('Storage', true) . ' 目錄。')
                  ->appendTip('建立 Config 目錄下的 ' . Display::colorBoldWhite($item[1]) . ' 目錄。')
                  ->appendTip(Display::controlC())
                  ->thing('\CMD\Init\Layout::initEnv', $item[1]);
    }, [
      ['開發環境', 'Development'],
      ['測試環境', 'Testing'],
      ['預備環境', 'Staging'],
      ['正式環境', 'Production'],
    ]);

    return Menu::create('初始專案環境', 'Init Project Environment')
               ->appendItems($items);
  }
}
