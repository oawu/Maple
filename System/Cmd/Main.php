<?php

namespace CMD {
  use \Load;
  use function \failure;
  use function \config;

  class Main {
    private static function init($argvs) {
      if (!$env = array_shift($argvs))
        return FeatureHelp::create('Init');

      if (!in_array($env, ['dev', 'test', 'pro', 'sta', 'Development', 'Testing', 'Staging', 'Production']))
        return FeatureHelp::create('Init');

      $env === 'dev'  && $env = 'Development';
      $env === 'test' && $env = 'Testing';
      $env === 'sta'  && $env = 'Staging';
      $env === 'pro'  && $env = 'Production';

      Load::systemFunc('File')            ?: failure('載入 File 失敗！');
      Load::systemCmd('Display')          ?: failure('載入 Display 失敗！');
      Load::systemCmd('Template')         ?: failure('載入 Template 失敗！');
      Load::systemCmd('FeatureInit')      ?: failure('載入 FeatureInit 失敗！');

      return FeatureInit::create($env);
    }

    private static function create($argvs) {
      if (!$type = array_shift($argvs))
        return FeatureHelp::create('Create');

      if (!in_array($type, ['-M', '-I', '--model', '--migration']))
        return FeatureHelp::create('Create');
      
      return in_array($type, ['-M', '--model'])
        ? self::createModel($argvs)
        : self::createMigration($argvs);
    }

    private static function createModel($argvs) {
      if (!$name = trim(array_shift($argvs) ?? ''))
        return FeatureHelp::create('CreateModel');

      Load::systemFunc('File')            ?: failure('載入 File 失敗！');
      Load::systemCmd('Display')          ?: failure('載入 Display 失敗！');
      Load::systemCmd('Template')         ?: failure('載入 Template 失敗！');
      Load::systemCmd('FeatureCreate')    ?: failure('載入 FeatureCreate 失敗！');

      return FeatureCreate::model($name, $argvs);
    }

    private static function createMigration($argvs) {
      if (count($argvs) < 2)
        return FeatureHelp::create('CreateMigration');

      Load::system('Env')                 ?: failure('載入 Env 失敗！');
      Load::systemFunc('File')            ?: failure('載入 File 失敗！');
      Load::systemCmd('Display')          ?: failure('載入 Display 失敗！');
      Load::systemCmd('Template')         ?: failure('載入 Template 失敗！');
      Load::systemCmd('Migration')        ?: failure('載入 Migration 失敗！');
      Load::systemCmd('FeatureCreate')    ?: failure('載入 FeatureCreate 失敗！');

      return FeatureCreate::migration($argvs);
    }
    
    private static function migration($argvs) {
      Load::system('Key')                 ?: failure('載入 Key 失敗！');
      Load::system('Env')                 ?: failure('載入 Env 失敗！');
      Load::systemCore('Model')           ?: failure('載入 Model 失敗！');
      Load::systemFunc('File')            ?: failure('載入 File 失敗！');
      Load::systemCmd('Display')          ?: failure('載入 Display 失敗！');
      Load::systemCmd('Template')         ?: failure('載入 Template 失敗！');
      Load::systemCmd('Migration')        ?: failure('載入 Migration 失敗！');
      Load::systemCmd('FeatureMigration') ?: failure('載入 FeatureMigration 失敗！');

      $type = array_shift($argvs) ?? '';
      $isRefresh = in_array($type, ['-R', '--refresh']);

      if ($isRefresh)
        return ENVIRONMENT === 'Production'
          ? failure("警告！正式站 Migration 禁止使用 --refresh(-R) 參數")
          : FeatureMigration::refresh(Migration::latestVersion());

      $type === '' && $type = Migration::latestVersion();

      if (is_numeric($type) && $type >= 0)
        return FeatureMigration::version(0 + $type);

      return FeatureHelp::create('Migration');
    }

    private static function clean($argvs) {
      $dirs = array_shift($argvs) ?? '';
      if ($dirs && !in_array($dirs, ['-C', '--cache', '-T', '--tmp']))
        return FeatureHelp::create('Clean');

      in_array($dirs, ['-C', '--cache']) && $dirs = [PATH_FILE_CACHE];
      in_array($dirs, ['-T', '--tmp'])   && $dirs = [PATH_FILE_TMP];
      $dirs === '' && $dirs = [PATH_FILE_CACHE, PATH_FILE_TMP];

      Load::system('Env')                 ?: failure('載入 Env 失敗！');
      Load::systemFunc('File')            ?: failure('載入 File 失敗！');
      Load::systemCmd('Display')          ?: failure('載入 Display 失敗！');
      Load::systemCmd('FeatureClean')     ?: failure('載入 FeatureClean 失敗！');

      return FeatureClean::dirs($dirs);
    }

    private static function deploy($argvs) {
      Load::system('Env')                 ?: failure('載入 Env 失敗！');
      Load::systemFunc('File')            ?: failure('載入 File 失敗！');
      Load::systemCmd('Display')          ?: failure('載入 Display 失敗！');
      Load::systemCmd('Template')         ?: failure('載入 Template 失敗！');
      Load::systemCmd('FeatureDeploy')    ?: failure('載入 FeatureDeploy 失敗！');

      $config = config('Deploy');
      $config ?? failure('取得 Config 失敗！');

      $argvs || $argvs = array_map(function($stage) { return $stage['stage']; }, $config);
      $argvs = array_filter(array_map(function($argv) use ($config) { $config = array_filter($config, function($stage) use ($argv) { return $stage['stage'] == $argv; }); return array_shift($config); }, $argvs));
      $argvs || failure('沒有此 stage 設定！');
      return FeatureDeploy::run($argvs);
    }

    public static function start($feature, $argvs) {
      return Load::systemCmd('FeatureHelp')
        ? $feature != 'init'
          ? $feature != 'create'
            ? $feature != 'migration'
              ? $feature != 'clean'
                ? $feature != 'deploy'
                  ? FeatureHelp::create()
                  : self::deploy($argvs)
                : self::clean($argvs)
              : self::migration($argvs)
            : self::create($argvs)
          : self::init($argvs)
        : failure('載入 FeatureHelp 失敗！');
    }
  }
}
