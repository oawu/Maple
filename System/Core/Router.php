<?php

class Router {
  private $name;
  private $segment;
  private $dirs;
  private $path;
  private $class;
  private $method;
  private static $names;
  private static $current;
  private static $requestMethod;
  private static $params = [];
  private static $className;
  private static $methodName;
  private static $routers;
  private static $regxPattern = [
    'id' => '[0-9]+',
    'any' => '[^/]+',
    'num' => '-?[0-9](.[0-9]+)?',
  ];

  public function __construct($segment = null, $dirs = []) {
    $this->segment = $segment;
    $this->dirs = $dirs;
  }
  
  public function controller($controller) {
    $controller = trim($controller, '/');

    strpos($controller, '@') !== false || $controller = $controller . '@' . 'index';

    list($this->path, $this->method) = explode('@', $controller);
    
    $this->class = pathinfo($this->path, PATHINFO_BASENAME);
    $this->path  = pathinfo($this->path, PATHINFO_DIRNAME);
    $this->path  = ltrim($this->dirs['prefix'] . DIRECTORY_SEPARATOR . ($this->path === '.' ? '' : $this->path . DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR);

    return $this->name(ucfirst($this->class) . ucfirst($this->method));
  }
  
  public function alias($name) {
    return $this->name($name);
  }

  public function name($name = null) {
    if ($name === null)
      return $this->name;

    $this->name = $this->dirs['prefix'] . $name;
    
    if (isset(self::$names[$this->name]))
      return self::$names[$this->name];

    return self::$names[$this->name] = $this;
  }
  
  public static function params() {
    return self::$params;
  }

  public static function param($key) {
    return array_key_exists($key, self::$params) ? self::$params[$key] : null;
  }

  public static function requestMethod() {
    return self::$requestMethod !== null
      ? self::$requestMethod
      : self::$requestMethod = strtolower(isCli()
        ? 'cli'
        : ($_POST['_method'] ?? $_SERVER['REQUEST_METHOD'] ?? 'get'));
  }

  private static function getDirs() {
    $dirs = array_filter(array_map(function($trace) {
      return isset($trace['class']) && ($trace['class'] == 'Router') && isset($trace['function']) && ($trace['function'] == 'dir') && isset($trace['type']) && ($trace['type'] == '::') && isset($trace['args'][0], $trace['args'][1])
        ? ['dir' => trim($trace['args'][0], '/') . '/', 'prefix' => trim($trace['args'][1], DIRECTORY_SEPARATOR)]
        : null;
    }, debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT)));

    $dirs = array_shift($dirs);
    $dirs || $dirs = ['dir' => '', 'prefix' =>''];

    $dirs['prefix'] || $dirs['prefix'] = implode('', array_map(function($t) { return ucfirst($t); }, explode('/', preg_replace ('/[\s_]+/', '/', $dirs['dir']))));

    return $dirs;
  }

  private static function setSegment($segment) {
    static $keys, $vals;
    
    if ($keys === null || $vals === null) {
      $keys = array_map(function($t) { return ':' . $t; }, array_keys(self::$regxPattern));
      $vals = array_map(function($t) { return ':' . $t; }, array_values(self::$regxPattern));
    }

    $segment = trim($segment, '/');
    $segment = str_replace($keys, $vals, $segment);
    
    return preg_replace('/\((\w+?):/', '(?<$1>', $segment);
  }

  public static function __callStatic($name, $args) {
    if (in_array($name, ['className', 'methodName']))
      return self::$$name;

    if (!in_array($name = strtolower($name), ['get', 'post', 'put', 'delete', 'del', 'cli']))
      throw new Exception('Router 不存在「' . $name . '」的 Static 方法！');

    $name == 'del' && $name = 'delete';

    $args || $args = [''];
    $dirs = self::getDirs();
    
    $segment = array_shift($args);
    $segment = self::setSegment($segment);
    $segment = trim($dirs['dir'] . $segment, '/');
    
    self::$routers[$name] = self::$routers[$name] ?? [];
    return self::$routers[$name][$segment] = new Router($segment, $dirs);
  }

  public static function all() {
    return self::$routers;
  }
  
  public static function dir($dir, $prefix, $closure = null) {
    if (is_callable($prefix)) {
      $closure = $prefix;
      $prefix = ucfirst($dir);
    }
    return $closure();
  }

  public static function file($name) {
    return Load::router($name);
  }

  public static function findByName($name) {
    if (isset(self::$names[$name]))
      return self::$names[$name];

    foreach (self::$routers as $method => $routers)
      foreach ($routers as $segment => $router)
        if ($segment === self::setSegment($name) || $router->name() === $name)
          return $router;

    return null;
  }

  public function exec() {
    if (!isset($this->path, $this->class, $this->method))
      return new GG('迷路惹！', 404);

    if (!Load::controller($this->path . $this->class . '.php'))
      return Log::warning('載入 Controller Class 失敗！', '載入路徑：' . $this->path . $this->class . '.php') && new GG('迷路惹！', 404);
    
    if (!class_exists($this->class))
      return Log::warning('找不到 Controller Class！', '請檢查「' . $this->path . $this->class . '.php」檔案的 Class 名稱是否正確！') && new GG('迷路惹！', 404);

    self::$className = $this->class;
    self::$methodName = $this->method;

    try {
      $obj = new $this->class();

      if (method_exists($obj, self::$methodName))
        return call_user_func_array([$obj, self::$methodName], static::$params);
      
      return new GG('迷路惹！', 404);
    } catch (ControllerException $e) {
      Status::$code = $e->getStatusCode();
      return call_user_func_array('ifError', $e->getMessages());
    }
  }

  public static function current() {
    if (self::$current !== null)
      return self::$current ? self::$current : null;

    $method = self::requestMethod();
    
    if (!isset(self::$routers[$method]))
      return self::$current = false;

    foreach (self::$routers[$method] as $segment => $obj) {
      if (preg_match('#^' . $segment . '$#', implode('/', Url::segments()), $matches)) {

        $params = [];
        foreach (array_filter(array_keys($matches), 'is_string') as $key)
          self::$params[$key] = $matches[$key];

        return self::$current = $obj;
      }
    }

    return self::$current = false;
  }

  public function segment($segment = null) {
    if ($segment === null)
      return $this->segment;

    $this->segment = $segment;
    return $this;
  }

  public function __call($name, $arguments) {
    if ($name == 'className')
      return $this->class;
    
    if ($name == 'methodName')
      return $this->method;
    
    if ($name == 'path')
      return $this->path;
    
    throw new Exception('Router 不存在「' . $name . '」方法！');
  }
  
  public static function load() {
    return array_map(function($file) {
      return !in_array($file, ['.', '..']) ? !is_dir(PATH_ROUTER . $file) ? pathinfo($file, PATHINFO_EXTENSION) == 'php' ? Load::file(PATH_ROUTER . $file) : null : array_map(function($subfile) use ($file) {
        return pathinfo($subfile, PATHINFO_EXTENSION) == 'php' ? Load::file(PATH_ROUTER . $file . DIRECTORY_SEPARATOR . $subfile) : null;
      }, @scandir(PATH_ROUTER . $file) ?: []) : null;
    }, @scandir(PATH_ROUTER) ?: []);
  }
  
  public static function aliasAppendParam($routerName, $param = null) {
    static $routerNames = [];
    array_key_exists($routerName, $routerNames) || $routerNames[$routerName] = [];
    
    if ($param === null)
      foreach ($routerNames as $key => $val)
        if (preg_match_all('/^' . $key . '/', $routerName, $matches))
          return $val;

    return array_push($routerNames[$routerName], $param);
  }
}

Router::load();