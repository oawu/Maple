<?php

namespace CMD {
  class Migration {
    public static function latestVersion () {
      $format = \config('Migration', 'fileFormat');

      $versions = array_values(array_filter(array_map(function($file) use ($format) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);

        if ($ext != 'php' || !is_readable($path = PATH_MIGRATION . $file) || $format === null || !preg_match_all($format, $file, $matches) || !($matches['vers'] && $matches['name']))
          return null;

        $data = include($path);
        
        if (!isset($data['up'], $data['at'], $data['down']))
          return null;

        return (int) array_shift($matches['vers']);
      }, @scandir(PATH_MIGRATION) ?: [])));

      sort($versions);

      return $versions ? end($versions) : 0;
    }
  }
}
