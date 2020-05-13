<?php

namespace CMD {
  use \Load;

  class FeatureHelp {
    public static function create($feature = null) {
      Load::systemCmd('Template') ?: failure('載入 File 失敗！');

      switch ($feature) {
        case 'Init':            return print(Template::read('Help/Init'));
        case 'Create':          return print(Template::read('Help/Create'));
        case 'CreateModel':     return print(Template::read('Help/CreateModel'));
        case 'CreateMigration': return print(Template::read('Help/CreateMigration'));
        case 'Migration':       return print(Template::read('Help/Migration'));
        case 'Clean':           return print(Template::read('Help/Clean'));
        default:                return print(Template::read('Help'));
      }
    }
  }
}
