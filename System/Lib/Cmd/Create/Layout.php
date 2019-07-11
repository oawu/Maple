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

  private static function splitCRUDColumnFormat2(&$column1s, &$column2s, &$column3s) {
    foreach (['column1s', 'column2s', 'column3s'] as $column)
      $$column = array_values(array_filter(array_map(function($val) { if (!(isset($val['must']) && is_bool($val['must']))) $val['must'] = false; return $val; }, $$column), function($val) { return isset($val['name'], $val['text']) && is_string($val['name']) && is_string($val['text']) && $val['name'] !== '' && $val['text'] !== ''; }));
  }

  private static function setCRUDFocus(&$column1s, &$column2s) {
    $hasFocus = false;
    foreach ($column1s as &$column1) {
      if (isset($column1['focus']) && $column1['focus'] === true) {
        $hasFocus = true;
        continue;
      }
      $hasFocus && $column1['focus'] = false;
    }

    foreach ($column2s as &$column2) {
      if (isset($column2['focus']) && $column2['focus'] === true) {
        $hasFocus = true;
        continue;
      }
      $hasFocus && $column2['focus'] = false;
    }

    if (!$hasFocus) if ($column1s) $column1s[0]['focus'] = true; else if ($column2s) $column2s[0]['focus'] = true; else;
  }

  private static function splitCRUDColumnFormat($columns) {
    return array_values(array_filter(array_map(function($column) {
      return count($tmp = array_values(array_filter(array_unique(preg_split('/:/', is_string($column) ? $column : '')), function($t) {
        return $t !== '';
      }))) == 1 ? ['name' => $tmp[0], 'text' => $tmp[0], 'must' => true] : ['name' => $tmp[0], 'text' => $tmp[1], 'must' => true];
    }, $columns)));
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

  public static function validatorConfigCRUD() {
    $config = config('AdminCRUD');

    if (!(isset($config['title'], $config['routerUri'], $config['controllerName'], $config['modelName']) && $config['routerUri'] !== '' && $config['controllerName'] !== '' && $config['modelName'] !== ''))
      Display::error('Config 格式有誤！');

    if (isset($config['parent'], $config['parent']['title'], $config['parent']['routerUri'], $config['parent']['controllerName'], $config['parent']['modelName']) && $config['parent']['routerUri'] !== '' && $config['parent']['controllerName'] !== '' && $config['parent']['modelName'] !== '')
      $errors = self::validatorNestedCRUD($config['parent']['title'], $config['parent']['routerUri'], $config['parent']['controllerName'], $config['parent']['modelName'], $config['title'], $config['routerUri'], $config['controllerName'], $config['modelName']);
    else
      $errors = self::validatorCRUD($config['title'], $config['routerUri'], $config['controllerName'], $config['modelName']);
    
    $errors && Display::error($errors);
    return null;
  }

  public static function validatorNestedCRUD() {
    $args  = func_get_args();
    
    $parentTitle          = array_shift($args);
    $parentRouterUri      = array_shift($args);
    $parentControllerName = array_shift($args);
    $parentModelName      = array_shift($args);
    
    $title          = array_shift($args);
    $routerUri      = array_shift($args);
    $controllerName = array_shift($args);
    $modelName      = array_shift($args);

    $errors = [];
    
    preg_match_all('/^\\\M\\\.+/', $parentModelName, $match) || $parentModelName = '\\M\\' . $parentModelName;

    preg_match_all('/^[A-Z].*/', $parentControllerName, $match)
      || array_push($errors, '父層 Controller 名稱不是大駝峰，請確認命名是否正確！');

    preg_match_all('/^\\\M\\\[A-Z].[0-9A-Za-z-_ ]*/', $parentModelName, $match)
      || array_push($errors, '父層 Model 名稱不是大駝峰，請確認命名是否正確！');

    class_exists($parentModelName)
      || array_push($errors, '父層 Model 不存在！');

    return array_merge($errors, self::validatorCRUD($title, $routerUri, $controllerName, $modelName));;
  }

  public static function validatorCRUD() {
    $args           = func_get_args();

    $title          = array_shift($args);
    $routerUri      = array_shift($args);
    $controllerName = array_shift($args);
    $modelName      = array_shift($args);

    preg_match_all('/^\\\M\\\.+/', $modelName, $match) || $modelName = '\\M\\' . $modelName;

    $errors = [];

    preg_match_all('/^[A-Z].*/', $controllerName, $match)
      || array_push($errors, 'Controller 名稱不是大駝峰，請確認命名是否正確！');

    is_dir(PATH_ROUTER . 'Admin' . DIRECTORY_SEPARATOR)
      || array_push($errors, 'Admin Router 目錄不存在！');

    is_writable(PATH_ROUTER . 'Admin' . DIRECTORY_SEPARATOR)
      || array_push($errors, 'Admin Router 目錄不可讀寫！');

    is_file(PATH_ROUTER . 'Admin' . DIRECTORY_SEPARATOR . $controllerName . '.php')
      && array_push($errors, 'Admin Router「' . $controllerName . '.php」檔案已經存在！');
    
    is_dir(PATH_APP_CONTROLLER . 'Admin' . DIRECTORY_SEPARATOR)
      || array_push($errors, 'Admin Controller 目錄不存在！');
    
    is_writable(PATH_APP_CONTROLLER . 'Admin' . DIRECTORY_SEPARATOR)
      || array_push($errors, 'Admin Controller 目錄不可讀寫！');
    
    is_file(PATH_APP_CONTROLLER . 'Admin' . DIRECTORY_SEPARATOR . $controllerName . '.php')
      && array_push($errors, 'Admin Controller「' . $controllerName . '.php」檔案已經存在！');

    is_dir(PATH_APP_VIEW . 'Admin' . DIRECTORY_SEPARATOR)
      || array_push($errors, 'Admin View 目錄不存在！');

    is_writable(PATH_APP_VIEW . 'Admin' . DIRECTORY_SEPARATOR)
      || array_push($errors, 'Admin View 目錄不可讀寫！');

    is_file(PATH_APP_VIEW . 'Admin' . DIRECTORY_SEPARATOR . $controllerName)
      && array_push($errors, 'Admin View「' . $controllerName . '」存在相同名稱的檔案！');

    is_dir(PATH_APP_VIEW . 'Admin' . DIRECTORY_SEPARATOR . $controllerName . DIRECTORY_SEPARATOR)
      && array_push($errors, 'Admin View「' . $controllerName . '」目錄已經存在！');

    preg_match_all('/^\\\M\\\[A-Z].[0-9A-Za-z-_ ]*/', $modelName, $match)
      || array_push($errors, 'Model 名稱不是大駝峰，請確認命名是否正確！');

    class_exists($modelName)
      || array_push($errors, 'Model 不存在！');

    return $errors;
  }

  public static function createConfigCRUD() {
    $config = config('AdminCRUD');
    
    $images = isset($config['images']) && (is_string($config['images']) || is_array($config['images'])) ? $config['images'] : [];
    $texts = isset($config['texts']) && (is_string($config['texts']) || is_array($config['texts'])) ? $config['texts'] : [];
    $textareas = isset($config['textareas']) && (is_string($config['textareas']) || is_array($config['textareas'])) ? $config['textareas'] : [];
    $enable = isset($config['enable']) && $config['enable'] === true;
    $sort = isset($config['sort']) && $config['sort'] === true;
    
    if (isset($config['parent'], $config['parent']['title'], $config['parent']['routerUri'], $config['parent']['controllerName'], $config['parent']['modelName']) && $config['parent']['routerUri'] !== '' && $config['parent']['controllerName'] !== '' && $config['parent']['modelName'] !== '')
      $errors = self::createNestedCRUD(
        null,
        $config['parent']['title'],
        $config['parent']['routerUri'],
        $config['parent']['controllerName'],
        $config['parent']['modelName'],
        $config['title'],
        $config['routerUri'],
        $config['controllerName'],
        $config['modelName'],
        $images,
        $texts,
        $textareas,
        $enable,
        $sort);
    else
      $errors = self::createCRUD(
        null,
        $config['title'],
        $config['routerUri'],
        $config['controllerName'],
        $config['modelName'],
        $images,
        $texts,
        $textareas,
        $enable,
        $sort);
  }
  
  public static function createNestedCRUD() {
    $args                 = func_get_args();
    $input                = array_shift($args);

    $parentTitle          = array_shift($args);
    $parentRouterUri      = array_shift($args);
    $parentControllerName = array_shift($args);
    $parentModelName      = array_shift($args);
    
    $title                = array_shift($args);
    $routerUri            = array_shift($args);
    $controllerName       = array_shift($args);
    $modelName            = array_shift($args);

    $images               = array_shift($args);
    $texts                = array_shift($args);
    $textareas            = array_shift($args);
    $enable               = array_shift($args);
    $sort                 = array_shift($args);

    self::createCRUD($input, $title, $routerUri, $controllerName, $modelName, $images, $texts, $textareas, $enable, $sort, $parentTitle, $parentRouterUri, $parentControllerName, $parentModelName);
  }

  public static function createCRUD() {
    \Load::systemFunc('File') ?: Display::error('載入 System/Func/File 失敗！');
    
    $args                 = func_get_args();
    $input                = array_shift($args);
    $title                = array_shift($args);
    $routerUri            = array_shift($args);
    $controllerName       = array_shift($args);
    $modelName            = array_shift($args);
    
    $images               = array_shift($args);
    $texts                = array_shift($args);
    $textareas            = array_shift($args);

    $images               = is_array($images)    ? $images    : self::splitCRUDColumnFormat(self::splitColumn(is_string($images)    ? $images    : ''));
    $texts                = is_array($texts)     ? $texts     : self::splitCRUDColumnFormat(self::splitColumn(is_string($texts)     ? $texts     : ''));
    $textareas            = is_array($textareas) ? $textareas : self::splitCRUDColumnFormat(self::splitColumn(is_string($textareas) ? $textareas : ''));
    
    $enable               = array_shift($args);
    $sort                 = array_shift($args);

    $parentTitle          = array_shift($args);
    $parentRouterUri      = array_shift($args);
    $parentControllerName = array_shift($args);
    $parentModelName      = array_shift($args);
    
    self::setCRUDFocus($texts, $textareas);
    self::splitCRUDColumnFormat2($images, $texts, $textareas);

    if ($parentModelName) preg_match_all('/^\\\M\\\.+/', $parentModelName, $match) || $parentModelName = '\\M\\' . $parentModelName;
    
    $hasParent = isset($parentTitle) && isset($parentRouterUri) && isset($parentControllerName) && isset($parentModelName);
    $hasParent || $parentTitle = $parentRouterUri = $parentControllerName = $parentModelName = null;
    $parentModelFkey = $hasParent ? lcfirst(deNamespace($parentModelName)) . 'Id' : null;

    preg_match_all('/^\\\M\\\.+/', $modelName, $match) || $modelName = '\\M\\' . $modelName;

    $routerFilePath     = PATH_ROUTER . 'Admin' . DIRECTORY_SEPARATOR . $controllerName . '.php';
    $controllerFilePath = PATH_APP_CONTROLLER . 'Admin' . DIRECTORY_SEPARATOR . $controllerName . '.php';
    $viewDirPath        = PATH_APP_VIEW . 'Admin' . DIRECTORY_SEPARATOR . $controllerName . DIRECTORY_SEPARATOR;

    $router = Tool::getTemplate('CRUD' . DIRECTORY_SEPARATOR . 'Router.template', [
      'title' => $title,
      'routerUri' => $routerUri,
      'controllerName' => $controllerName,
      'enable' => $enable,
      'sort' => $sort,
      'hasParent' => $hasParent,
      'parentRouterUri' => $parentRouterUri,
      'parentModelFkey' => $parentModelFkey]);

    $controller = Tool::getTemplate('CRUD' . DIRECTORY_SEPARATOR . 'Controller.template', [
      'parentTitle' => $parentTitle,
      'title' => $title,
      'modelName' => $modelName,
      'controllerName' => $controllerName,
      'images' => $images,
      'texts' => array_map(function($text) {
        $validator = '';

        switch ($text['type'] ?? 'text') {
          case 'number': $validator = '->isNumber()'; break;
          case 'email': $validator = '->isEmail()'; break;
          case 'date': $validator = '->isDate()'; break;
          default: $validator = '->isString(' . ($text['must'] ? '1' : '0') . ', 190)'; break;
        }

        return 'Validator::' . ($text['must'] ? 'must' : 'optional') . "(" . '$params' . ", '" . $text['name'] . "', '" . $text['text'] . "')" . $validator . ";\n";
      }, $texts),
      'textareas' => array_map(function($textarea) {
        $validator = '';
        switch ($textarea['type'] ?? 'pure') {
          case 'ckeditor': $validator = '->isStr()->strTrim()->allowableTags(false)->strMinLength(' . ($textarea['must'] ? '1' : '0') . ')'; break;
          default: $validator = '->isString(' . ($textarea['must'] ? '1' : '0') . ')'; break;
        }
        return 'Validator::' . ($textarea['must'] ? 'must' : 'optional') . "(" . '$params' . ", '" . $textarea['name'] . "', '" . $textarea['text'] . "')" . $validator . ";\n";
      }, $textareas),
      'enable' => $enable,
      'sort' => $sort,
      'hasParent' => $hasParent,
      'parentControllerName' => $parentControllerName,
      'parentModelName' => $parentModelName,
      'parentModelFkey' => $parentModelFkey]);

    $views = array_map(function($file) use ($viewDirPath, $hasParent, $modelName, $controllerName, $images, $texts, $textareas, $enable) {
      return [
        'name' => $file,
        'path' => $viewDirPath . lcfirst($file) . '.php',
        'content' => Tool::getTemplate('CRUD' . DIRECTORY_SEPARATOR . 'View' . DIRECTORY_SEPARATOR . $file . '.template', [
          'hasParent' => $hasParent,
          'modelName' => $modelName,
          'controllerName' => $controllerName,
          'images' => $images,
          'texts' => $texts,
          'textareas' => $textareas,
          'enable' => $enable])];
    }, ['Index', 'Add', 'Edit', 'Show']);

    file_exists($routerFilePath) || fileWrite($routerFilePath, $router);
    file_exists($routerFilePath) || Display::error('Router 寫入失敗！');

    file_exists($controllerFilePath) || fileWrite($controllerFilePath, $controller);
    file_exists($controllerFilePath) || Display::error('Controller 寫入失敗！');

    is_dir($viewDirPath) || umaskMkdir($viewDirPath, 0777, true);
    if (is_dir($viewDirPath))
      foreach ($views as $view) {
        file_exists($view['path']) || fileWrite($view['path'], $view['content']);
        file_exists($view['path']) || Display::error('View「' . $view['name'] . '」寫入失敗！');
      }

    Display::title('完成');
    Display::markListLine('新增 Admin CRUD「' . ($hasParent ? ($parentTitle === '' ? \Xterm::black('空字串', true)->dim() : Display::colorBoldWhite($parentTitle)) . \Xterm::create('＞', true)->dim() : '') . Display::colorBoldWhite($title) . '」成功！');
    Display::markListLine('Router File    ' . Display::markSemicolon() . \Xterm::gray('Router' . DIRECTORY_SEPARATOR . 'Admin' . DIRECTORY_SEPARATOR . $controllerName . '.php', true));
    Display::markListLine('Controller File' . Display::markSemicolon() . \Xterm::gray('App' . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR . 'Admin' . DIRECTORY_SEPARATOR . $controllerName . '.php', true));
    Display::markListLine('Index View File' . Display::markSemicolon() . \Xterm::gray('App' . DIRECTORY_SEPARATOR . 'View' . DIRECTORY_SEPARATOR . 'Admin' . DIRECTORY_SEPARATOR . 'index.php', true));
    Display::markListLine('Add View File  '   . Display::markSemicolon() . \Xterm::gray('App' . DIRECTORY_SEPARATOR . 'View' . DIRECTORY_SEPARATOR . 'Admin' . DIRECTORY_SEPARATOR . 'add.php', true));
    Display::markListLine('Edit View File '  . Display::markSemicolon() . \Xterm::gray('App' . DIRECTORY_SEPARATOR . 'View' . DIRECTORY_SEPARATOR . 'Admin' . DIRECTORY_SEPARATOR . 'edit.php', true));
    Display::markListLine('Show View File '  . Display::markSemicolon() . \Xterm::gray('App' . DIRECTORY_SEPARATOR . 'View' . DIRECTORY_SEPARATOR . 'Admin' . DIRECTORY_SEPARATOR . 'show.php', true));
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

    if (file_exists(PATH_CONFIG . ENVIRONMENT . DIRECTORY_SEPARATOR . 'AdminCRUD.php') && ($config = config('AdminCRUD')) && isset($config['title'], $config['routerUri'], $config['controllerName'], $config['modelName'])) {
      $item3 = Input::create('依 Config 新增後台 CRUD', 'Create Admin CRUD by Config')
                    ->appendTip('Config 位置' . Display::markSemicolon() . \Xterm::gray('Config' . DIRECTORY_SEPARATOR . ENVIRONMENT . DIRECTORY_SEPARATOR . 'AdminCRUD.php', true))
                    ->appendTip('標題           ' . Display::markSemicolon() . (isset($config['parent']['title']) ? ($config['parent']['title'] === '' ? \Xterm::black('空字串', true)->dim() : Display::colorBoldWhite($config['parent']['title'])) . \Xterm::create('＞', true)->dim() : '') . Display::colorBoldWhite($config['title']))
                    ->appendTip('Router Uri     ' . Display::markSemicolon() . \Xterm::gray($config['routerUri'], true))
                    ->appendTip('Controller 名稱' . Display::markSemicolon() . \Xterm::gray($config['controllerName'], true))
                    ->appendTip('Model 名稱     ' . Display::markSemicolon() . \Xterm::gray($config['modelName'], true))
                    ->appendTip(Display::controlC())
                    ->isCheck('請確認以上要新增的 CRUD 資訊？')
                    ->setValidator('\CMD\Create\Layout::validatorConfigCRUD')
                    ->thing('\CMD\Create\Layout::createConfigCRUD');
    } else {
      $item3 = null;
    }

    // $item4 = Input::create('依步驟新增後台 CRUD', 'Create Admin CRUD')
    //               ->isCheck()
    //               ->appendTip('Controller 名稱請使用大駝峰命名。')
    //               ->appendTip('Model 名稱請使用大駝峰命名。')
    //               ->appendTip('欄位請用 name:title 格式，例如：title:標題。')
    //               ->appendTip('多欄位請用空白鍵隔開。')
    //               ->appendTip('過程中若要離開，請直接按下鍵盤上的 control + c')
    //               ->appendInput('請輸入標題')
    //               ->appendInput('請輸入 Router Uri')
    //               ->appendInput('請輸入 Controller 名稱')
    //               ->appendInput('請輸入 Model 名稱')
    //               ->appendInput('請輸入 ' . Display::colorBoldWhite('Image') . ' 欄位', false)
    //               ->appendInput('請輸入 ' . Display::colorBoldWhite('Text') . ' 欄位', false)
    //               ->appendInput('請輸入 ' . Display::colorBoldWhite('Textarea') . ' 欄位', false)
    //               ->appendCheck('是否有' . Display::colorBoldWhite('開關') . '功能')
    //               ->appendCheck('是否有' . Display::colorBoldWhite('排序') . '功能')
    //               ->setValidator('\CMD\Create\Layout::validatorCRUD')
    //               ->thing('\CMD\Create\Layout::createCRUD');

    // $item5 = Input::create('依步驟新增巢狀後台 CRUD', 'Create Admin Nested CRUD')
    //               ->isCheck()
    //               ->appendTip('Controller 名稱請使用大駝峰命名。')
    //               ->appendTip('Model 名稱請使用大駝峰命名。')
    //               ->appendTip('欄位請用 name:title 格式，例如：title:標題。')
    //               ->appendTip('多欄位請用空白鍵隔開。')
    //               ->appendTip('過程中若要離開，請直接按下鍵盤上的 control + c')

    //               ->appendInput('請輸入父層標題', true, '/./')
    //               ->appendInput('請輸入父層 Router Uri')
    //               ->appendInput('請輸入父層 Controller 名稱')
    //               ->appendInput('請輸入父層 Model 名稱')

    //               ->appendInput('請輸入標題', true, '/./')
    //               ->appendInput('請輸入 Router Uri')
    //               ->appendInput('請輸入 Controller 名稱')
    //               ->appendInput('請輸入 Model 名稱')
    //               ->appendInput('請輸入 ' . Display::colorBoldWhite('Image') . ' 欄位', false, '/./')
    //               ->appendInput('請輸入 ' . Display::colorBoldWhite('Text') . ' 欄位', false, '/./')
    //               ->appendInput('請輸入 ' . Display::colorBoldWhite('Textarea') . ' 欄位', false, '/./')
    //               ->appendCheck('是否有' . Display::colorBoldWhite('開關') . '功能')
    //               ->appendCheck('是否有' . Display::colorBoldWhite('排序') . '功能')
    //               ->setValidator('\CMD\Create\Layout::validatorNestedCRUD')
    //               ->thing('\CMD\Create\Layout::createNestedCRUD');

    return Menu::create('新增檔案', 'Create Migration or Model')
               ->appendItem($item1)
               ->appendItem($item2)
               ->appendItem($item3);
  }
}