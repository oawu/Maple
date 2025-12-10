<?php

namespace Cmd;

final class Template {
  public static function php(string $path, array $params = []): string {
    $path = PATH_SYSTEM_CMD . 'Template' . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    return '<?php' . "\n" . self::_load($path, $params);
  }

  public static function read(string $path, array $params = []): string {
    $path = PATH_SYSTEM_CMD . 'Template' . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    return self::_load($path, $params);
  }

  public static function get(string $path, array $params = []): string {
    return file_get_contents(PATH_SYSTEM_CMD . 'Template' . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) . '.template');
  }

  private static function _load(string $___B10g___aT_M9pL30___path___B10g___aT_M9pL30___, array $___B10g___aT_M9pL30___pARams___B10g___aT_M9pL30___ = []): string {
    $___B10g___aT_M9pL30___path___B10g___aT_M9pL30___ .= '.template';

    if (!is_readable($___B10g___aT_M9pL30___path___B10g___aT_M9pL30___)) {
      return '';
    }

    extract($___B10g___aT_M9pL30___pARams___B10g___aT_M9pL30___);
    ob_start();

    include $___B10g___aT_M9pL30___path___B10g___aT_M9pL30___;
    $buffer = ob_get_contents();
    @ob_end_clean();

    return $buffer;
  }
}
