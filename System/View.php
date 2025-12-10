<?php

final class View {
  public static function load(?string $___maple9____path___, array $___maple9____params___ = [], bool $___maple9____is_display___ = true): string {
    ob_start();
    if ($___maple9____path___ === null) {
      echo Helper::dump($___maple9____params___);
      $buffer = ob_get_contents();
    } else {
      extract($___maple9____params___);
      include $___maple9____path___;
      $buffer = ob_get_contents();
    }

    @ob_end_clean();

    if ($___maple9____is_display___) {
      echo $buffer;
      return '';
    }
    return $buffer;
  }
  public static function isValid(string $path): bool {
    return is_file($path) && is_readable($path);
  }
  public static function create(string ...$paths): self {
    return new self(...$paths);
  }

  private $_path = null;
  private $_vals = [];
  private $_parent = null;

  private function __construct(string ...$paths) {
    $this->setPath(...$paths);
  }

  public function __toString() {
    $parent = $this->_parent;

    if (!($parent instanceof View)) {
      return $this->toString();
    }

    foreach ($this->getVals() as $key => $val) {
      $parent->with($key, $val);
    }

    return $parent->toString();
  }
  public function setPath(string ...$_paths): self {
    $first = $_paths[0] ?? '';

    $isRoot = $first && ($first === '/' || $first === '\\');

    $paths = [];
    foreach ($_paths as $path) {
      $tmps = Helper::explodePath($path, '/', ['\\', '/']);
      foreach ($tmps as $path) {
        $paths[] = $path;
      }
    }

    $path = ($isRoot ? DIRECTORY_SEPARATOR : '') . implode(DIRECTORY_SEPARATOR, $paths);

    $ext  = pathinfo($path, PATHINFO_EXTENSION) == '' ? '.php' : '';
    $path = $path . $ext;

    if (!$isRoot) {
      $path = PATH_APP_VIEW . $path;
    }

    if (self::isValid($path)) {
      $this->_path = $path;
    }

    return $this;
  }
  public function getPath(): ?string {
    return $this->_path;
  }
  public function with(string $key, $val): self {
    $this->_vals[$key] = $val;
    return $this;
  }
  public function withReference(string $key, &$val): self {
    $this->_vals[$key] = &$val;
    return $this;
  }
  public function parent(View $parent, string $key): self {
    $this->_parent = $parent->with($key, $this);
    return $this;
  }
  public function getVals(): array {
    return array_map(fn($val) => $val instanceof View ? $val->toString() : $val, $this->_vals);
  }
  public function toString(): string {
    return self::load($this->_path, $this->getVals(), false);
  }
}
