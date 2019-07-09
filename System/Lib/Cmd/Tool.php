<?php

namespace CMD;

class Tool {
  public static function depath(string $path) {
    return str_replace(PATH, '', $path);
  }

  public static function getFile(string $path, array $params = []) {
    $path = PATH_SYSTEM_LIB . 'Cmd' . DIRECTORY_SEPARATOR . 'Template' . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    return self::get($path, $params);
  }

  public static function getShellScript(string $path, array $params = []) {
    $path = PATH_SYSTEM_LIB . 'Cmd' . DIRECTORY_SEPARATOR . 'Template' . DIRECTORY_SEPARATOR . 'ShellScript' . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    return self::get($path, $params);
  }

  public static function getTemplate(string $path, array $params = []) {
    $path = PATH_SYSTEM_LIB . 'Cmd' . DIRECTORY_SEPARATOR . 'Template' . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    return '<?php' . Display::LN . self::get($path, $params);
  }
  
  private static function get(string $___B10g___aT_GiNkg0___path___B10g___aT_GiNkg0___, array $___B10g___aT_GiNkg0___pARams___B10g___aT_GiNkg0___ = []) {
    if (!is_readable($___B10g___aT_GiNkg0___path___B10g___aT_GiNkg0___))
      return '';

    extract($___B10g___aT_GiNkg0___pARams___B10g___aT_GiNkg0___);
    ob_start();

    include $___B10g___aT_GiNkg0___path___B10g___aT_GiNkg0___;
    $buffer = ob_get_contents();
    @ob_end_clean();
    
    return $buffer;
  }
}