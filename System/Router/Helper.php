<?php

namespace Router;

use \Router;
use \Helper as _Helper;

final class Helper {
  public static function paths(string ...$_paths): array {
    $paths = [];
    foreach ($_paths as $path) {
      $tmps = _Helper::explodePath($path, '/', ['\\', '/']);
      foreach ($tmps as $path) {
        $paths[] = $path;
      }
    }

    $types = [];
    foreach (Router::TYPE_STR as $type) {
      $types[] = $type;
    }
    foreach (Router::TYPE_INT as $type) {
      $types[] = $type;
    }
    foreach (Router::TYPE_UINT as $type) {
      $types[] = $type;
    }
    foreach (Router::TYPE_FLOAT as $type) {
      $types[] = $type;
    }

    return array_map(static function (string $val) use ($types): array {
      $_name = '(?P<name>\w+)';
      $_type = '(?P<type>\w+)';
      $_len = '(?:\(\s*(?P<minlen>\d+)?\s*,?\s*(?P<maxlen>\d+)?\s*\))?';
      $_main = $_name . '\s*:\s*' . $_type . '\s*' . $_len . '';
      $pattern = '/^\{\{\s*' . $_main . '\s*\}\}$/';

      if (preg_match($pattern, $val, $matches)) {
        $name = $matches['name'];
        $type = $matches['type'];
        $minlen = isset($matches['minlen']) && $matches['minlen'] !== '' ? (int)$matches['minlen'] : null;
        $maxlen = isset($matches['maxlen']) && $matches['maxlen'] !== '' ? (int)$matches['maxlen'] : null;

        if (!in_array($type, $types)) { // 如果不是合法的類型
          return [
            'type' => 'x',
            'val' => $val,
          ];
        }

        return [
          'type' => $type,
          'val' => $val,
          'name' => $name,
          'len' => (in_array($type, Router::TYPE_STR) || $type == 'int') && ($minlen !== null || $maxlen !== null) ? [
            'min' => $minlen,
            'max' => $maxlen,
          ] : null,
        ];
      }

      // 如果不是變數格式，則當作一般字串
      return [
        'type' => 'equal',
        'val' => $val
      ];
    }, $paths);
  }
  public static function middlewares(string ...$middlewares): array {

    $pathsList = [];
    foreach ($middlewares as $middleware) {
      $pathsList[] = _Helper::explodePath($middleware, '\\', ['\\']);
    }

    $result = [];
    foreach ($pathsList as $paths) {
      $middleware = array_pop($paths) ?? '';

      [$class, $method] = explode('@', $middleware) + ['', ''];

      if ($class === '') {
        continue;
      }

      if ($method === '') {
        $method = 'index';
      }

      $paths[] = $class;

      $result[] = [
        'class' => implode('\\', $paths),
        'method' => $method,
      ];
    }

    return $result;
  }
}
