<?php

namespace CMD\Create;

use \CMD\Layout\Menu  as Menu;
use \CMD\Layout\Input as Input;
use \CMD\Str          as Str;
use \CMD\Tool         as Tool;
use \CMD\Display      as Display;

class Layout {
  private static function columnDouble($c1, $c2) {
    if (!$c1) return false;
    if (!$c2) return false;
    foreach ($c2 as $c) if (in_array($c, $c1)) return true;
    return false;
  }

  private static function scanModel($path) {
    return arrayFlatten(array_values(array_filter(array_map(function($name) use ($path) {
      if (is_dir($path .  $name) && !in_array($name, ['.', '..']))
        return self::scanModel($path .  $name . DIRECTORY_SEPARATOR);
      if (is_file($path .  $name) && pathinfo($name, PATHINFO_EXTENSION) == 'php')
        return pathinfo($name, PATHINFO_FILENAME);
    }, @scandir($path) ?: []))));
  }

  private static function splitColumn($columns) {
    return array_filter(array_unique(preg_split('/\s+/', is_string($columns) ? $columns : '')), function($t) {
      return $t !== '';
    });
  }

  public static function validatorModel() {
    $args         = func_get_args();
    $modelName    = array_shift($args);
    $imageColumns = array_shift($args);
    $fileColumns  = array_shift($args);

    $modelNames = self::scanModel(PATH_APP_MODEL);
    $errors = [];
    
    in_array($modelName, $modelNames)
      && array_push($errors, 'Model 名稱重複。');

    $imageColumns = array_filter(array_unique(preg_split('/\s+/', is_string($imageColumns) ? $imageColumns : '')), function($t) { return $t !== ''; });
    $fileColumns  = array_filter(array_unique(preg_split('/\s+/', is_string($fileColumns) ? $fileColumns : '')), function($t) { return $t !== ''; });

    self::columnDouble($imageColumns, $fileColumns)
      && array_push($errors, '檔案上傳器有欄位與圖片上傳器欄位衝突。');

    return $errors;
  }
  
  public static function createMigration() {
    \Load::systemFunc('File') ?: Display::error('載入 System/Func/File 失敗！');
    
    $args  = func_get_args();
    $input = array_shift($args);
    $name  = array_shift($args);

    is_writable(PATH_MIGRATION)   ?: Display::error('您的 Migration 目錄沒有讀寫權限！');
    \Load::systemLib('Migration') ?: Display::error('載入 System/Lib/Migration 失敗！');

    $files = array_keys(\Migration::files());
    $nextVersion = $files ? end($files) + 1 : 1;
    $path = PATH_MIGRATION . sprintf('%03s-%s.php', $nextVersion, $name);

    file_exists($path) && Display::error('Migration 名稱重複！');

    $args = preg_split('/\s/', $name);
    
    switch (strtolower(array_shift($args))) {
      case 'create':
        $tName = array_shift($args);
        $tName = $tName === null ? '{資料表}' : $tName;
        
        $migrationStr = Tool::getTemplate('Migration.template', [
          'type' => 'create',
          'tName' => $tName]);
        break;

      case 'drop':
        $tName = array_shift($args);
        $tName = $tName === null ? '{資料表}' : $tName;
        $migrationStr = Tool::getTemplate('Migration.template', [
          'type' => 'drop',
          'tName' => $tName]);
        break;

      case 'alter':
        $tName  = array_shift($args);
        $action = strtolower(array_shift($args));
        $field  = array_shift($args);

        $tName = $tName === null ? '{資料表}' : $tName;
        in_array($action, ['add', 'drop', 'change']) || $action = '';
        $field = $field === null ? '{欄位名稱}' : $field;

        $migrationStr = Tool::getTemplate('Migration.template', [
          'type' => 'alter' . $action,
          'tName' => $tName,
          'field' => $field]);
        break;
      
      defaultLable:
      default:
        $migrationStr = Tool::getTemplate('Migration.template', ['type' => null]);
        break;
    }

    fileWrite($path, $migrationStr);
    file_exists($path) || Display::error('Migration 寫入失敗！');

    Display::title('完成');
    Display::markListLine('新增 Migration「' . Display::colorBoldWhite($name) . '」成功。');
    Display::markListLine('Migration 檔案位置' . Display::markSemicolon() . Display::colorBoldWhite(Tool::depath($path)));
    echo Display::LN;
  }

  public static function createModel() {
    \Load::systemFunc('File') ?: Display::error('載入 System/Func/File 失敗！');
    
    $args         = func_get_args();
    $input        = array_shift($args);
    $modelName    = array_shift($args);
    $imageColumns = array_shift($args);
    $fileColumns  = array_shift($args);

    $imageColumns = self::splitColumn($imageColumns);
    $fileColumns  = self::splitColumn($fileColumns);

    $modelStr = Tool::getTemplate('Model.template', [
      'modelName'    => $modelName,
      'space'        => Str::repeat(Str::width($modelName)),
      'imageColumns' => $imageColumns,
      'fileColumns'  => $fileColumns,
    ]);

    is_writable(PATH_APP_MODEL) || Display::error('您的 Model 目錄沒有讀寫權限！');
    
    $path = PATH_APP_MODEL . $modelName . '.php';
    \fileWrite($path, $modelStr, 'x');

    $exists = file_exists($path);

    Display::title('完成');
    Display::markListLine('新增 Model「' . Display::colorBoldWhite($modelName) . '」成功，檔案' . Display::markSemicolon() . Display::colorBoldWhite(Tool::depath($path)));
    Display::markListLine('圖片上傳器欄位' . Display::markSemicolon() . ($imageColumns ? implode(\Xterm::black('、', true)->blod(), array_map('\CMD\Display::colorBoldWhite', $imageColumns)) : \Xterm::black('無', true)->dim()));
    Display::markListLine('檔案上傳器欄位' . Display::markSemicolon() . ($fileColumns ?  implode(\Xterm::black('、', true)->blod(), array_map('\CMD\Display::colorBoldWhite', $fileColumns))  : \Xterm::black('無', true)->dim()));
    echo Display::LN;
  }

  public static function get() {
    if (!\Load::system('Env'))
      return null;

    $item1 = Input::create('新增 Migration 檔案', 'Create Migration')
                  ->appendTip('快速建立資料表，範例：' . \Xterm::gray('create TableName', true))
                  ->appendTip('快速新增欄位，範例：' . \Xterm::gray('alter TableName add fieldName', true))
                  ->appendTip(Display::controlC())
                  ->appendInput('請輸入名稱')
                  ->isCheck()
                  ->setAutocomplete('create', 'alter', 'drop', 'insert', 'update', 'delete')
                  ->thing('\CMD\Create\Layout::createMigration');

    $item2 = Input::create('新增 Model 檔案', 'Create Model')
                  ->isCheck()
                  ->appendTip('Model 名稱請使用' . Display::colorBoldWhite('大駝峰') . '命名。')
                  ->appendTip('圖片、檔案上傳器多筆欄位時，用' . Display::colorBoldWhite('空白隔') . '開即可。')
                  ->appendTip(Display::controlC())
                  ->appendInput('請輸入 Model 名稱')
                  ->appendInput('請輸入' . Display::colorBoldWhite('圖片上傳器') . '欄位', false)
                  ->appendInput('請輸入' . Display::colorBoldWhite('檔案上傳器') . '欄位', false)
                  ->setValidator('\CMD\Create\Layout::validatorModel')
                  ->thing('\CMD\Create\Layout::createModel');

    return Menu::create('新增檔案', 'Create Migration or Model')
               ->appendItem($item1)
               ->appendItem($item2);
  }
}