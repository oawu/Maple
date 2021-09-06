<?php

namespace CMD {
  class FeatureDeploy {
    public static function run($argvs) {
      if (!$argvs) return;

      QUIET || print("\n部署專案至 " . $argvs[0]['name'] . "\n");
      
      Display::main('本地端環境');
      Display::title('檢查是否可執行 dep 指令');
      empty(shell_exec(sprintf('which %s', escapeshellarg('dep'))))
        ? Display::failure('找不定正確的 Dep 指令，請先安裝 dep 指令吧！')
        : Display::success(true, '可以');
        
      foreach ($argvs as $i => $argv) {
        $i && (QUIET || print("\n部署專案至 " . $argv['name'] . "\n"));
        $i && Display::main('本地端環境');

        Display::title('建立 ' . $argv['name'] . ' 的 deploy.php 部署檔案');
        is_dir($path = PATH_FILE_TMP . 'deploy.php') && Display::failure('已存在相同名稱的目錄！');
        @fileWrite($path, Template::php('Deploy', ['stage' => $argv]));
        is_readable($path) || Display::failure('寫入檔案失敗！');
        Display::success();
        @passthru('cd ' . PATH_FILE_TMP . ' && dep deploy ' . $argv['stage']);
      }
    }
  }
}

namespace CMD\Deploy {
  use \CMD\Display;
  use \CMD\Template;

  class Tool {

    private static function getCMD($cmd, &$errors = []) {
      $errors = ['無法執行「' . $cmd . '」指令！'];
      try {
        $tmp = \Deployer\locateBinaryPath($cmd);
        $tmp === null || $errors = [];
        return $tmp;
      } catch (\Exception $exception) {
        array_push($errors, '錯誤訊息：' . $exception->getMessage());
        return null;
      }
      return null;
    }

    private static function run(string $cmd, &$errors = []) {
      try {
        return \Deployer\run($cmd);
      } catch (\Exception $exception) {
        $errors = ['執行 ' . $cmd . ' 指令失敗！', '錯誤訊息：' . $exception->getMessage()];
        return null;
      }
      $errors = ['執行 ' . $cmd . ' 指令失敗！', '錯誤訊息：不明原因錯誤！'];
      return null;
    }

    private static function runShellScript($shellScript, &$errors = []) {
      $result = self::run($shellScript, $errors);
      $errors && array_unshift($errors, ['執行 Shell Script 發生錯誤', '請檢查 Shell Script 語法']);
      return $result;
    }

    private static function closure($closure, $result, &$errors = []) {
      if (!is_callable($closure))
        return $result;

      try {
        return $closure($result);
      } catch (\Exception $exception) {
        $errors = ['錯誤訊息：' . $exception->getMessage()];
        return null;
      }
    }

    public static function runCMD($title, $cmd, $errors = [], $closure = null) {
      if (!(($cmd = trim($cmd)) && ($cmds = preg_split('/\s+/', $cmd))))
        return true;

      Display::title($title);

      $cmd = array_shift($cmds);
      $cmds || $cmds = '';
      $cmds && $cmds = ' ' . implode(' ', $cmds);

      is_array($errors) || $errors = [$errors];

      $$cmd = self::getCMD($cmd, $errs);
      $errs && Display::failure($errs);

      $result = self::run($$cmd . $cmds, $errs);
      $errs && Display::failure(array_merge($errors, $errs));

      $result = self::closure($closure, $result, $errs);
      $errs && Display::failure(array_merge($errors, $errs));

      return Display::success($result);
    }

    public static function dirExist($title, $path, $errors = []) {
      Display::title($title);

      is_array($errors) || $errors = [$errors];
      $result = self::runShellScript(Template::shellScript('DirExist', ['path' => $path]), $errs);
      $errs && Display::failure($errs);

      return $result !== 'true'
        ? Display::failure($errors ?: ['目錄不存在，目錄路徑：' . $path])
        : Display::success(true);
    }

    public static function cd($title, $path, $errors = []) {
      Display::title($title);
      
      is_array($errors) || $errors = [$errors];
      self::closure(function($path) { return \Deployer\cd($path); }, $path, $errs);

      return $errs
        ? Display::failure($errs)
        : Display::success(true);
    }

    public static function dirExistOrCreate($title, $path, $errors = []) {
      Display::title($title);
      
      is_array($errors) || $errors = [$errors];
      $result = self::runShellScript(Template::shellScript('DirExistOrCreate', ['path' => $path]), $errs);
      $errs && Display::failure($errs);
      
      return $result !== 'true'
        ? Display::failure($errors ?: ['目錄不存在，目錄路徑：' . $path])
        : Display::success(true);
    }

    public static function checkCMD($title, $cmd, $errors = []) {
      Display::title($title);
      is_array($errors) || $errors = [$errors];

      $result = self::runShellScript(Template::shellScript('CheckCMD', ['cmd' => $cmd]), $errs);
      $errs && Display::failure($errs);
      $result === 'true' || Display::failure(array_merge($errors, ['伺服器上無法指行「' . $cmd . '」指令！']));

      self::getCMD($cmd, $errs);
      $errs && Display::failure(array_merge($errors, $errs));
      
      return Display::success(true);
    }

    public static function fileNotExist($title, $path, $errors = []) {
      Display::title($title);
      
      is_array($errors) || $errors = [$errors];
      $result = self::runShellScript(Template::shellScript('FileNotExist', ['path' => $path]), $errs);
      $errs && Display::failure($errs);
      
      return $result !== 'true'
        ? Display::failure($errors ?: ['檔案已存在，檔案路徑：' . $path])
        : Display::success(true);
    }
    
    public static function fileExist($title, $path, $errors = []) {
      Display::title($title);

      is_array($errors) || $errors = [$errors];
      $result = self::runShellScript(Template::shellScript('FileExist', ['path' => $path]), $errs);
      $errs && Display::failure($errs);

      return $result !== 'true'
        ? Display::failure($errors ?: ['檔案不存在，檔案路徑：' . $path])
        : Display::success(true);
    }

    public static function fileExistOrCreate($title, $path, $errors = []) {
      Display::title($title);

      is_array($errors) || $errors = [$errors];
      $result = self::runShellScript(Template::shellScript('FileExistOrCreate', ['path' => $path]), $errs);
      $errs && Display::failure($errs);

      return $result !== 'true'
        ? Display::failure($errors ?: ['檔案不存在，檔案路徑：' . $path])
        : Display::success(true);
    }
    
    public static function copyLockInfo($title, $lockPath, $logPath, $name, $errors = []) {
      Display::title($title);

      is_array($errors) || $errors = [$errors];
      self::runShellScript(Template::shellScript('CopyLockInfo', [
        'lockPath' => $lockPath,
        'logPath' => $logPath . $name
      ]), $errs);

      $errs && Display::failure($errs);
      return Display::success(true);
    }

    public static function unlock($title, $path, $errors = []) {
      Display::title($title);

      is_array($errors) || $errors = [$errors];
      $result = self::runShellScript(Template::shellScript('Unlock', ['path' => $path]), $errs);
      $errs && Display::failure($errs);

      return $result !== 'true'
        ? Display::failure($errors ?: ['檔案依然存在，檔案路徑：' . $path])
        : Display::success(true);
    }

    public static function lock($title, $path, $errors = []) {
      Display::title($title);

      is_array($errors) || $errors = [$errors];
      $result = self::runShellScript(Template::shellScript('Lock', ['path' => $path, 'info' => implode("\n", ['userName：' . get_current_user(), 'hostName：' . gethostname(), 'hostAddr：' . gethostbyname(gethostname()), 'datatime：' . date('Y-m-d H:i:s'), 'deployer：' . php_uname()]) . "\n"]), $errs);
      $errs && Display::failure($errs);
      $result = self::runShellScript(Template::shellScript('FileExist', ['path' => $path]), $errs);

      return $result !== 'true'
        ? Display::failure($errors ?: ['檔案不存在，檔案路徑：' . $path])
        : Display::success(true);
    }

    public static function getNowBr($title, $cmd) {
      return self::runCMD($title, $cmd, [], function($result) {
        $branch = array_filter(array_map('trim', explode("\n", $result)), function($branch) { return $branch !== '' && preg_match_all('/^\*\s+/', $branch, $match); });
        $branch = array_shift($branch);

        if (!(isset($branch) && $branch != ''))
          throw new \Exception('無法取得目前的分支名稱！');

        if (($branch = preg_replace('/^\*\s+/', '', $branch)) === '')
          throw new \Exception('無法取得目前的分支名稱！');
        
        return $branch;
      });
    }

    public static function finish($title, $path, $errors = []) {
      Display::title($title);
      
      is_array($errors) || $errors = [$errors];
      @unlink($path);
      
      return file_exists($path)
        ? Display::failure($errors ?? ['檔案依然存在！'])
        : Display::success(true);
    }
  }
}
