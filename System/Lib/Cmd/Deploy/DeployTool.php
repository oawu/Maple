<?php

namespace CMD\Deploy;

use \CMD\Display as Display;
use \CMD\Tool    as Tool;

class DeployTool {
  public static function cd($title, $path) {
    Display::line('進入專案目錄',  \Xterm::gray('執行指令')->dim() . Display::markSemicolon() . \Xterm::gray('cd ' . $path)->dim()->italic());

    try {
      \Deployer\cd($path);
    } catch (\Exception $exception) {
      Display::line(false, ['進入失敗！', '檔案路徑：' . $path, '錯誤訊息：' . $exception->getMessage()]);
    }

    Display::line(true);
  }

  public static function copyLockInfo($title, $lockPath, $logPath, $name, $errors = []) {
    is_array($errors) || $errors = [$errors];

    Display::line($title, \Xterm::gray('執行指令')->dim() . Display::markSemicolon() . \Xterm::gray($name . ' << ' . 'deploy.lock')->dim()->italic());

    self::runShellScript(Tool::getShellScript('copyLockInfo.template', [
      'lockPath' => $lockPath,
      'logPath' => $logPath . $name
    ]));
    
    Display::line(true);
  }

  public static function lock($title, $path, $errors = []) {
    is_array($errors) || $errors = [$errors];

    Display::line($title, \Xterm::gray('執行指令')->dim() . Display::markSemicolon() . \Xterm::gray('echo "Deployer info" >> deploy.lock')->dim()->italic());
    
    self::runShellScript(Tool::getShellScript('lock.template', [
      'path' => $path,
      'info' => implode(Display::LN, [
        'userName：' . get_current_user(),
        'hostName：' . gethostname(),
        'hostAddr：' . gethostbyname(gethostname()),
        'datatime：' . date('Y-m-d H:i:s'),
        'deployer：' . php_uname(),
      ]) . Display::LN,
    ]));

    Display::line(true);
  }

  public static function unlock($title, $path, $errors = []) {
    is_array($errors) || $errors = [$errors];

    Display::line($title, \Xterm::gray('執行指令')->dim() . Display::markSemicolon() . \Xterm::gray('rm -f deploy.lock')->dim()->italic());

    $result = self::runShellScript(Tool::getShellScript('unlock.template', ['path' => $path]));
    $result === 'true' || Display::line(false, $errors ?: ['檔案不存在！', '檔案路徑：' . $path]);

    Display::line(true);
  }

  public static function fileExistOrCreate($title, $path, $errors = []) {
    is_array($errors) || $errors = [$errors];
    
    Display::line($title, \Xterm::gray('執行指令')->dim() . Display::markSemicolon() . \Xterm::gray('[ -f ' . $path . ']')->dim()->italic());

    $result = self::runShellScript(Tool::getShellScript('fileExistOrCreate.template', ['path' => $path]));
    $result === 'true' || Display::line(false, $errors ?: ['檔案不存在並且自動新增失敗！', '檔案路徑：' . $path]);

    Display::line(true);
  }

  public static function dirExistOrCreate($title, $path, $errors = []) {
    is_array($errors) || $errors = [$errors];
    Display::line($title, \Xterm::gray('執行指令')->dim() . Display::markSemicolon() . \Xterm::gray('[ -d ' . $path . ']')->dim()->italic());

    $result = self::runShellScript(Tool::getShellScript('dirExistOrCreate.template', ['path' => $path]));
    $result === 'true' || Display::line(false, array_merge($errors, ['目錄不存在並且自動新增失敗！', '目錄路徑：' . $path]));

    Display::line(true);
  }
  
  public static function dirExist($title, $path, $errors = []) {
    is_array($errors) || $errors = [$errors];
    Display::line($title, \Xterm::gray('執行指令')->dim() . Display::markSemicolon() . \Xterm::gray('[ -d ' . $path . ']')->dim()->italic());

    $result = self::runShellScript(Tool::getShellScript('dirExist.template', ['path' => $path]));
    $result === 'true' || Display::line(false, $errors ?: ['目錄不存在！', '目錄路徑：' . $path]);

    Display::line(true);
  }

  public static function fileExist($title, $path, $errors = []) {
    is_array($errors) || $errors = [$errors];
    Display::line($title, \Xterm::gray('執行指令')->dim() . Display::markSemicolon() . \Xterm::gray('[ -f ' . $path . ']')->dim()->italic());

    $result = self::runShellScript(Tool::getShellScript('fileExist.template', ['path' => $path]));
    $result === 'true' || Display::line(false, $errors ?: ['檔案不存在！', '檔案路徑：' . $path]);

    Display::line(true);
  }
  
  public static function dirNotExist($title, $path, $errors = []) {
    is_array($errors) || $errors = [$errors];
    Display::line($title, \Xterm::gray('執行指令')->dim() . Display::markSemicolon() . \Xterm::gray('[ ! -d ' . $path . ']')->dim()->italic());

    $result = self::runShellScript(Tool::getShellScript('dirNotExist.template', ['path' => $path]));
    $result === 'true' || Display::line(false, $errors ?: ['目錄已存在！', '目錄路徑：' . $path]);

    Display::line(true);
  }

  public static function fileNotExist($title, $path, $errors = []) {
    is_array($errors) || $errors = [$errors];
    Display::line($title, \Xterm::gray('執行指令')->dim() . Display::markSemicolon() . \Xterm::gray('[ ! -f ' . $path . ']')->dim()->italic());

    $result = self::runShellScript(Tool::getShellScript('fileNotExist.template', ['path' => $path]));
    $result === 'true' || Display::line(false, $errors ?: ['檔案已存在！', '檔案路徑：' . $path]);

    Display::line(true);
  }

  public static function checkCmd($title, $cmd) {
    Display::line($title, \Xterm::gray('執行指令')->dim() . Display::markSemicolon() . \Xterm::gray('if hash ' . $cmd . ' 2>/dev/null')->dim()->italic());

    $result = self::runShellScript(Tool::getShellScript('checkCmd.template', ['cmd' => $cmd]));

    $result === 'true' || Display::line(false, ['伺服器上無法指行 ' . Display::colorBoldWhite($cmd) . ' 指令！']);

    self::getCmd($cmd, $tmpErr);
    $tmpErr && Display::line(false, ['伺服器上無法指行 ' . Display::colorBoldWhite($cmd) . ' 指令！']);

    Display::line(true, '可以');
  }

  private static function getCmd($cmd, &$errors = []) {
    try {
      return \Deployer\locateBinaryPath($cmd);
    } catch (\Exception $exception) {
      $errors = ['找不到指令：' . $cmd, '錯誤訊息：' . $exception->getMessage()];
      return null;
    }
    return null;
  }

  public static function getNowBr($title, $cmd) {
    return self::runCmd($title, $cmd, function($result) {
    $branch = array_filter(array_map('trim', explode(Display::LN, $result)), function($branch) { return $branch !== '' && preg_match_all('/^\*\s+/', $branch, $match); });
      $branch = array_shift($branch);
      if (!(isset($branch) && $branch != ''))
        throw new \Exception('無法取得目前的分支名稱！');

      if (($branch = preg_replace('/^\*\s+/', '', $branch)) === '')
        throw new \Exception('無法取得目前的分支名稱！');
      
      return $branch;
    });
  }

  private static function runShellScript(string $shellScript) {
    $result = self::run($shellScript, $errors);
    $errors && Display::line(false, array_merge($errors, ['執行 Shell Script 發生錯誤！', '請檢查 Shell Script：' . $shellScript], $errors));
    return $result;
  }

  private static function run(string $cmd, &$errors = []) {
    try {
      return \Deployer\run($cmd);
    } catch (\Exception $exception) {
      $errors = array_merge(['執行 ' . $cmd . ' 指令失敗！'], ['錯誤訊息：' . $exception->getMessage()]);
      return null;
    }
    $errors = array_merge(['執行 ' . $cmd . ' 指令失敗！'], ['錯誤訊息：' . '不明原因錯誤！']);
    return null;
  }

  public static function runCmd(string $title, string $cmd, $closure = null, $errors = []) {

    if (!(($cmd = trim($cmd)) && ($cmds = preg_split('/\s+/', $cmd))))
      return true;

    $cmd = array_shift($cmds);
    $cmds || $cmds = '';
    $cmds && $cmds = ' ' . implode(' ', $cmds);

    if (is_array($closure) || is_string($closure)) {
      $errors = $closure;
      $closure = null;
    }

    is_array($errors) || $errors = [$errors];

    // Display::line($title . '，指令' . Display::markSemicolon() . \Xterm::gray($cmd . $cmds)->dim()->italic());
    Display::line($title, \Xterm::gray('執行指令')->dim() . Display::markSemicolon() . \Xterm::gray($cmd . $cmds)->dim()->italic());
    
    $$cmd = self::getCmd($cmd, $tmpErr);
    $tmpErr && Display::line(false, $tmpErr);

    $$cmd !== null || Display::line(false, ['不可以執行 ' . $cmd . ' 指令！']);

    $result = self::run($$cmd . $cmds, $tmpErr);
    $tmpErr && Display::line(false, array_merge($errors, $tmpErr));

    if (is_callable($closure)) {
      try {
        $result = $closure($result);
      } catch (\Exception $exception) {
        Display::line(false, ['錯誤訊息：' . $exception->getMessage()]);
      }
    }

    Display::line(true);

    return $result;
  }
}