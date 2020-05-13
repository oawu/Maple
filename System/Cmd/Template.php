<?php
namespace CMD {
  class Template {
    public static function shellScript($path, $params = []) {
      $path = PATH_SYSTEM_CMD . 'Template' . DIRECTORY_SEPARATOR . 'ShellScript' . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
      return self::load($path, $params);
    }

    public static function php($path, $params = []) {
      $path = PATH_SYSTEM_CMD . 'Template' . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
      return '<?php' . "\n" . self::load($path, $params);
    }

    public static function read($path, $params = []) {
      $path = PATH_SYSTEM_CMD . 'Template' . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
      return self::load($path, $params);
    }

    public static function get($path, $params = []) {
      return file_get_contents(PATH_SYSTEM_CMD . 'Template' . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) . '.template');
    }

    private static function load($___B10g___aT_M8pL30___path___B10g___aT_M8pL30___, $___B10g___aT_M8pL30___pARams___B10g___aT_M8pL30___ = []) {
      $___B10g___aT_M8pL30___path___B10g___aT_M8pL30___ .= '.template';

      if (!is_readable($___B10g___aT_M8pL30___path___B10g___aT_M8pL30___))
        return '';

      extract($___B10g___aT_M8pL30___pARams___B10g___aT_M8pL30___);
      ob_start();

      include $___B10g___aT_M8pL30___path___B10g___aT_M8pL30___;
      $buffer = ob_get_contents();
      @ob_end_clean();
      
      return $buffer;
    }
  }
}
