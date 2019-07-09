<?php

class Asset {
  private $jsList = [];
  private $cssList = [];

  public function __construct() {
  }

  public static function create() {
    return new Asset();
  }

  public static function img($path) {
    if (preg_match('/^https?:\/\/.*/', $path)) return $path;
    $path = ltrim(preg_replace('/\/+/', '/', $path), '/');
    $url = Url::base($path . '?v=' . md5_file(PATH_PUBLIC . str_replace('/', DIRECTORY_SEPARATOR, $path)));
    return $url;
  }

  public function removeCSS($path) {
    $path = !preg_match('/^https?:\/\/.*/', $path) ? ltrim(preg_replace('/\/+/', '/', $path), '/') : $path;
    $index = array_search($path, $this->cssList);
    if ($index !== false) unset($this->cssList[$index]);
    return $this;
  }

  public function removeJS($path) {
    $path = !preg_match('/^https?:\/\/.*/', $path) ? ltrim(preg_replace('/\/+/', '/', $path), '/') : $path;
    $index = array_search($path, $this->jsList);
    if ($index !== false) unset($this->jsList[$index]);
    return $this;
  }
  
  public function addCSS($path) {
    array_push($this->cssList, !preg_match('/^https?:\/\/.*/', $path) ? ltrim(preg_replace('/\/+/', '/', $path), '/') : $path);
    return $this;
  }

  public function addJS($path) {
    array_push($this->jsList, !preg_match('/^https?:\/\/.*/', $path) ? ltrim(preg_replace('/\/+/', '/', $path), '/') : $path);
    return $this;
  }

  public function renderCSS() {
    return implode('', array_map(function($path) {
      $url = $path;
      
      if (!preg_match('/^https?:\/\/.*/', $path) && is_readable($tmp = PATH_PUBLIC . $path)) {
        $url = Url::base($path . '?v=' . md5_file(PATH_PUBLIC . $path));
      } else {
        return '';
      }

      return tag('link', [
        'href' => $url,
        'rel' => 'stylesheet',
        'type' => 'text/css']);
    }, $this->cssList));
  }

  public function renderJS() {
    return implode('', array_map(function($path) {
      $url = $path;

      if (!preg_match('/^https?:\/\/.*/', $path) && is_readable($tmp = PATH_PUBLIC . $path)) {
        $url = Url::base($path . '?v=' . md5_file(PATH_PUBLIC . $path));
      } else {
        return '';
      }

      return tag('script', '', [
        'src' => $url,
        'language' => 'javascript',
        'type' => 'text/javascript']);
    }, $this->jsList));
  }




  // private $version, $list;
  
  // public function __construct($version = 0) {
  //   $this->version = $version;
  // }

  // public function getList($type = null) {
  //   return isset($this->list[$type]) ? $this->list[$type] : $this->list;
  // }

  // public function addList($type, $path) {
  //   is_string($path) || $path = '/' . ltrim(preg_replace('/\/+/', '/', implode('/', arrayFlatten($path))), '/');
  //   isset($list[$type]) || $list[$type] = [];
    
  //   preg_match('/^https?:\/\/.*/', $path) && $minify = false;
  //   $this->list[$type][$path] = $minify;
  //   return $this;
  // }

  // public function addJS($path) {
  //   return $this->addList('js', $path, $minify);
  // }


  // public static function url($uri) {
  //   return asset($uri);
  // }

}