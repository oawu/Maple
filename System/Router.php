<?php

use \Router\Group;

final class Router {
  public const TYPE_STR = [
    'str',
    'string'
  ];
  public const TYPE_INT = [
    'int',
    'int8',
    'int16',
    'int32',
    'int64',
  ];
  public const TYPE_UINT = [
    'uint',
    'uint8',
    'uint16',
    'uint32',
    'uint64',
  ];
  public const TYPE_FLOAT = [
    'float',
    'double',
    'num',
    'number',
  ];

  private static $_current = null;
  private static $_routers = [];

  /**
   * 初始化路由器
   *
   * @param string $dir 要載入的路由器目錄路徑
   * @return void
   */
  public static function init(string $dir): void {
    if (!is_readable($dir) || !is_dir($dir)) {
      return;
    }

    // 載入當前資料夾的 php 檔案
    foreach (glob($dir . '*.php') as $file) {
      require_once $file;
    }

    // 遞迴處理子目錄
    foreach (glob($dir . '*', GLOB_ONLYDIR) as $subdir) {
      $realSubdir = realpath($subdir);
      if ($realSubdir !== false) {
        self::init($realSubdir . DIRECTORY_SEPARATOR);
      }
    }
  }
  public static function all(): array {
    return self::$_routers;
  }
  public static function getCurrent(): ?self {
    return self::$_current;
  }
  public static function current(): ?self {
    return self::getCurrent();
  }
  public static function cli(string ...$paths): self {
    return new Router('cli', ...$paths);
  }
  public static function get(string ...$paths): self {
    return new Router('get', ...$paths);
  }
  public static function post(string ...$paths): self {
    return new Router('post', ...$paths);
  }
  public static function put(string ...$paths): self {
    return new Router('put', ...$paths);
  }
  public static function delete(string ...$paths): self {
    return new Router('delete', ...$paths);
  }
  public static function del(string ...$paths): self {
    return new Router('delete', ...$paths);
  }
  public static function head(string ...$paths): self {
    return new Router('head', ...$paths);
  }
  public static function options(string ...$paths): self {
    return new Router('options', ...$paths);
  }
  public static function patch(string ...$paths): self {
    return new Router('patch', ...$paths);
  }
  public static function execute() {
    $method = Request::getMethod();
    $paths = Request::getPaths();

    if (!isset(self::$_routers[$method])) {
      \notFound('此 Method「' . $method . '」對應不到路由！');
      return;
    }

    $routers = self::$_routers[$method];
    $params = [];

    foreach ($routers as $router) {
      $result = self::_rule($paths, $router->getPaths(false));

      if ($result !== null) {
        $params = $result;
        self::$_current = $router;
        break;
      }
    }

    if (!self::$_current) {
      \notFound('此路徑「' . implode('/', $paths) . '」找不到對應的路由！');
      return;
    }

    // 清除路由器
    self::$_routers = [];
    Request::setParams($params);

    $return = self::_executeMiddleware(self::getCurrent());

    $task = self::current()->_getTask();
    if ($task === null) {
      \notFound('此路由未設定要執行的 func 或 controller！');
      return;
    }

    if ($task['type'] === 'func') {
      return self::_executeFunc($task['func'], $return);
    }

    if ($task['type'] === 'controller') {
      ['class' => $class, 'method' => $method] = $task;
      return self::_executeController($class, $method, $return);
    }

    return null;
  }

  private static function _rule(array $paths, array $rules): ?array {
    if (count($paths) !== count($rules)) {
      return null;
    }

    $results = [];
    foreach ($rules as $idx => $rule) {
      $path = $paths[$idx];
      $type = $rule['type'];

      if ($type === 'x') {
        return null;
      }

      $val = $rule['val'];

      if ($type === 'equal') {
        if ($val === $path) {
          continue;
        } else {
          return null;
        }
      }

      $name = $rule['name'];
      $len = $rule['len'];

      $isInt = in_array($type, self::TYPE_INT);
      $isUint = in_array($type, self::TYPE_UINT);
      $isFloat = in_array($type, self::TYPE_FLOAT);
      $isStr = in_array($type, self::TYPE_STR);

      if (!$isInt && !$isUint && !$isFloat && !$isStr) {
        return null;
      }

      if ($isStr) {
        $path = (string)$path;

        if ($len !== null && $len['min'] !== null && strlen($path) < $len['min']) {
          return null;
        }
        if ($len !== null && $len['max'] !== null && strlen($path) > $len['max']) {
          return null;
        }

        $results[$name] = $path;
        continue;
      }

      if (!is_numeric($path)) {
        return null;
      }

      if ($isFloat) {
        $results[$name] = (float)$path;
        continue;
      }

      $path = (int)$path;

      if ($isUint && $path < 0) {
        return null;
      }

      switch ($type) {
        case 'int':
          if ($len !== null && $len['min'] !== null && $path < $len['min']) {
            return null;
          }
          if ($len !== null && $len['max'] !== null && $path > $len['max']) {
            return null;
          }
          $results[$name] = $path;
          break;
        case 'int8':
          if (!($path >= -128 && $path <= 127)) {
            return null;
          }
          $results[$name] = $path;
          break;
        case 'int16':
          if (!($path >= -32768 && $path <= 32767)) {
            return null;
          }
          $results[$name] = $path;
          break;
        case 'int32':
          if (!($path >= -2147483648 && $path <= 2147483647)) {
            return null;
          }
          $results[$name] = $path;
          break;
        case 'int64':
          if (!($path >= -9223372036854775808 && $path <= 9223372036854775807)) {
            return null;
          }
          $results[$name] = $path;
          break;

        case 'uint':
          $results[$name] = $path;
          break;
        case 'uint8':
          if (!($path <= 255)) {
            return null;
          }
          $results[$name] = $path;
          break;
        case 'uint16':
          if (!($path <= 65535)) {
            return null;
          }
          $results[$name] = $path;
          break;
        case 'uint32':
          if (!($path <= 4294967295)) {
            return null;
          }
          $results[$name] = $path;
          break;
        case 'uint64':
          if (!($path <= 18446744073709551615)) {
            return null;
          }
          $results[$name] = $path;
          break;
        default:
          return null;
      }
    }

    return $results;
  }
  private static function _executeMiddleware(?Router $router) {
    $return = null;

    $middlewares = $router->getMiddlewares();
    foreach ($middlewares as $middleware) {
      ['class' => $class, 'method' => $method] = $middleware;


      if (!class_exists($class)) {
        \notFound('此路由中介層「' . $class . '」不存在！');
        return;
      }

      $middleware = new $class();
      if (!method_exists($middleware, $method)) {
        \notFound('此路由中介層方法「' . $method . '」不存在！');
        return;
      }

      $return = $middleware->$method($return);
    }
    return $return;
  }
  private static function _executeFunc(callable $func, $return) {
    return $func($return);
  }
  private static function _executeController(string $class, string $method, $return) {
    if (!class_exists($class)) {
      \notFound('此路由控制器「' . $class . '」不存在！');
      return;
    }

    $controller = new $class();
    if (!method_exists($controller, $method)) {
      \notFound('此路由控制器方法「' . $class . '」不存在！');
      return;
    }

    return $controller->$method($return);
  }

  private string $_method;
  private array $_groups;
  private array $_paths;
  private string $_title = '';
  private array $_middlewares = [];
  private ?array $_task = null;

  private function __construct(string $method, string ...$paths) {
    $this->_method = strtoupper($method);

    $groupsTraces = array_filter(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT), fn($groupsTrace) => isset($groupsTrace['class'], $groupsTrace['object']) && $groupsTrace['class'] === 'Router\Group' && $groupsTrace['object'] instanceof Group);

    $this->setGroups(...array_reverse(array_map(fn($groupsTrace) => $groupsTrace['object'], $groupsTraces)));
    $this->setPath(...$paths);

    if (!isset(self::$_routers[$this->_method])) {
      self::$_routers[$this->_method] = [];
    }

    self::$_routers[$this->_method][] = $this;
  }

  public function setGroups(Group ...$groups): self {
    $this->_groups = $groups;
    return $this;
  }
  public function getGroups(): array {
    return $this->_groups;
  }
  public function groups(): array {
    return $this->getGroups();
  }
  public function setPath(string ...$paths): self {
    $this->_paths = \Router\Helper::paths(...$paths);
    return $this;
  }
  public function getPaths(bool $isOnlyVal = true): array {
    $paths = [];
    foreach ($this->getGroups() as $group) {
      foreach ($group->getPaths($isOnlyVal) as $path) {
        $paths[] = $path;
      }
    }
    foreach ($this->_paths as $path) {
      if ($isOnlyVal) {
        $paths[] = $path['val'];
      } else {
        $paths[] = $path;
      }
    }

    return $paths;
  }
  public function path(string ...$paths): self {
    return $this->setPath(...$paths);
  }
  public function setTitle(string $title): self {
    $this->_title = $title;
    return $this;
  }
  public function getTitle(): string {
    return $this->_title;
  }
  public function title(string $title): self {
    return $this->setTitle($title);
  }
  public function setMiddleware(string ...$middlewares): self {
    $this->_middlewares = \Router\Helper::middlewares(...$middlewares);
    return $this;
  }
  public function getMiddlewares(): array {
    $middlewares = [];
    foreach ($this->getGroups() as $group) {
      foreach ($group->getMiddlewares() as $middleware) {
        $middlewares[] = $middleware;
      }
    }
    foreach ($this->_middlewares as $middleware) {
      $middlewares[] = $middleware;
    }
    return $middlewares;
  }
  public function middleware(string ...$middlewares): self {
    return $this->setMiddleware(...$middlewares);
  }
  public function controller(string $controller): self {
    [$class, $method] = explode('@', $controller) + ['', ''];

    if ($class === '') {
      return $this;
    }

    if ($method === '') {
      $method = 'index';
    }

    $this->_task = [
      'type' => 'controller',
      'class' => $class,
      'method' => $method,
    ];

    return $this;
  }
  public function func(callable $func): self {
    $this->_task = [
      'type' => 'func',
      'func' => $func,
    ];
    return $this;
  }

  private function _getTask(): ?array {
    return $this->_task;
  }
}
