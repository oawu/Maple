<?php

if (!function_exists('isEmail')) {
  function isEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
  }
}

if (!function_exists('isURL')) {
  function isURL($url) {
    return preg_match('/^https?:\/\/.*/', $url);
  }
}

if (!function_exists('isDate')) {
  function isDate($date) {
    return DateTime::createFromFormat('Y-m-d', $date) !== false;
  }
}

if (!function_exists('isDatetime')) {
  function isDatetime($date) {
    return DateTime::createFromFormat('Y-m-d H:i:s', $date) !== false;
  }
}

if (!function_exists('isUploadFile')) {
  function isUploadFile($file) {
    return isset($file['name'], $file['type'], $file['tmp_name'], $file['error'], $file['size']);
  }
}

if (!function_exists('uploadFileInFormats')) {
  function uploadFileInFormats($file, $formats) {
    static $extension;
    
    $formats = array_unique(array_map('trim', $formats));

    if (!$format = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION))) {
      $extension || $extension = config('Extension');
      foreach ($extension as $ext => $mime)
        if (in_array($file['type'], $mime) && ($format = $ext))
          break;
    }

    return $format && in_array($format, $formats);
  }
}