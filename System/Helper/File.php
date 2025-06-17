<?php

namespace Helper;

abstract class File {
  public static function read(string $file): ?string {
    if (function_exists('file_get_contents')) {
      $result = @file_get_contents($file);
      if (is_string($result)) {
        return $result;
      }
    }

    if (!is_readable($file)) {
      return null;
    }

    $fp = @fopen($file, 'rb');

    if ($fp === false) {
      return null;
    }

    flock($fp, LOCK_SH);

    $data = filesize($file) > 0
      ? fread($fp, filesize($file))
      : '';

    flock($fp, LOCK_UN);
    fclose($fp);

    return $data;
  }
  public static function write(string $path, string $data, string $mode = 'wb'): bool {
    if (!$fp = @fopen($path, $mode)) {
      return false;
    }

    flock($fp, LOCK_EX);

    for ($result = $written = 0, $length = strlen($data); $written < $length; $written += $result) {
      if (($result = fwrite($fp, substr($data, $written))) === false) {
        break;
      }
    }

    flock($fp, LOCK_UN);
    fclose($fp);

    return is_int($result);
  }
  public static function delete(string $path, bool $delDir = false, bool $htdocs = false, int $level = 0): bool {
    $path = rtrim($path, '/\\');

    if (!$currentDir = @opendir($path)) {
      return false;
    }

    while (false !== ($filename = @readdir($currentDir))) {
      if ($filename !== '.' && $filename !== '..') {
        $filepath = $path . DIRECTORY_SEPARATOR . $filename;

        if (is_dir($filepath) && $filename[0] !== '.' && !is_link($filepath)) {
          self::delete($filepath, $delDir, $htdocs, $level + 1);
        } elseif ($htdocs !== true || !preg_match('/^(\.htaccess|index\.(html|htm|php)|web\.config)$/i', $filename)) {
          @unlink($filepath);
        } else {
        }
      }
    }

    closedir($currentDir);

    return $delDir === true && $level > 0 ? @rmdir($path) : true;
  }
  public static function dirInfo(string $sourceDir, bool $recursive = true): ?array {
    if (!(file_exists($sourceDir) && is_dir($sourceDir) && !is_link($sourceDir))) {
      return null;
    }

    $files = [];
    $sourceDir = rtrim(realpath($sourceDir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    $fp = @opendir($sourceDir);
    if ($fp === false) {
      return null;
    }

    while (false !== ($file = readdir($fp))) {

      if (is_dir($sourceDir . $file) && $file !== '.' && $file !== '..' && $recursive) {
        $infos = self::dirInfo($sourceDir . $file . DIRECTORY_SEPARATOR, $recursive);
        if ($infos !== null) {
          foreach ($infos as $info) {
            $files[] = $info;
          }
        }
      } elseif (is_file($file)) {
        $info = self::fileInfo($sourceDir . $file);
        if ($info !== null) {
          $info['dir'] = $sourceDir;
          $files[] = $info;
        }
      }
    }

    closedir($fp);
    return array_values($files);
  }
  public static function fileInfo(string $file): ?array {
    if (!(file_exists($file) && is_file($file) && !is_link($file))) {
      return null;
    }

    return [
      'name' => basename($file),
      'path' => $file,
      'size' => filesize($file),
      'date' => filemtime($file),
      'readable' => is_readable($file),
      'writable' => is_writable($file),
      'executable' => is_executable($file),
      'fileperms' => fileperms($file),
    ];
  }
}
