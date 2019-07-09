<?php
namespace _M;

class TmpUploader {
  private $class, $defaultUrl, $versions = [];

  public function __construct($class) {
    $this->class = $class == 'image' ? '\_M\ImageUploader' : '\_M\FileUploader';
  }

  public function toArray() {
    return $this->class == '\_M\FileUploader' ? [
      'class' => $this->class,
      'defaultUrl' => $this->defaultUrl,
    ] : [
      'class' => $this->class,
      'defaultUrl' => $this->defaultUrl,
      'versions' => $this->versions,
    ];
  }

  public function default($defaultUrl = null) {
    $this->defaultUrl = $defaultUrl;
    return $this;
  }

  public function version($name) {
    if ($this->class == '\_M\FileUploader')
      \gg('File Uploader 不可設定 version。');

    $args = func_get_args();
    $this->versions[array_shift($args)] = $args;
    return $this;
  }
}
