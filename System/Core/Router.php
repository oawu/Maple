<?php
namespace Router {
  class Group {
    private $uri = '';
    private $dir = '';
    private $mid = '';
    private $name = null;

    public function __construct($options, $items = null) {
      $this->uri($options['uri'] ?? null);
      $this->dir($options['dir'] ?? null);
      $this->mid($options['mid'] ?? null);
      $this->items($items);
    }

    public function uri($uri = null) {
      if ($uri === null) {
        return $this->uri;
      }

      if ($uri !== null) {
        $this->uri = $uri;
      } else {
        $this->uri = '';
      }

      return $this;
    }

    public function mid($mid = null) {
      if ($mid === null) {
        return $this->mid;
      }

      if ($mid !== null) {
        $this->mid = $mid;
      } else {
        $this->mid = '';
      }

      return $this;
    }

    public function dir($dir = null) {
      if ($dir === null) {
        return $this->dir;
      }

      if ($dir !== null) {
        $this->dir = $dir;
      } else {
        $this->dir = '';
      }

      return $this;
    }

    public function name($name = null) {
      if ($name === null) {
        if ($this->name !== null) {
          return $this->name;
        }
        return $this->uri;
      }

      if ($name !== null) {
        $this->name = $name;
      } else {
        $this->name = $this->uri;
      }

      return $this;
    }

    public function items($closure) {
      if (!is_callable($closure)) {
        return $this;
      }

      $closure();
      return $this;
    }
  }
}

namespace {
  class Router {
    private $class;
    private $method;
    private $name;
    private $func;
    private $title;

    private $uris = [];
    private $dirs = [];
    private $mids = [];

    private $groupNames = [];
    private $segment = '';

    private static $names = [];
    private static $routers = [];
    private static $regxPattern = [
      'id' => '[0-9]+',
      'any' => '[^/]+',
      'num' => '-?[0-9](.[0-9]+)?',
    ];

    public function __construct($method, $groups, $segment) {
      $this->uris = array_reduce(array_filter(array_map(function($group) { return $group->uri(); }, $groups), function($uri) { return $uri !== '' && $uri !== []; }), function($a, $b) { return array_merge($a, is_array($b) ? $b : [$b]); }, []);
      $this->dirs = array_reduce(array_filter(array_map(function($group) { return $group->dir(); }, $groups), function($dir) { return $dir !== '' && $dir !== []; }), function($a, $b) { return array_merge($a, is_array($b) ? $b : [$b]); }, []);
      $this->mids = array_reduce(array_filter(array_map(function($group) { return $group->mid(); }, $groups), function($mid) { return $mid !== '' && $mid !== []; }), function($a, $b) { return array_merge($a, is_array($b) ? $b : [$b]); }, []);
      $this->groupNames = array_reduce(array_filter(array_map(function($group) { return $group->name(); }, $groups), function($name) { return $name !== '' && $name !== []; }), function($a, $b) { return array_merge($a, is_array($b) ? $b : [$b]); }, []);

      $this->segment = self::setSegment(($this->uris ? implode('/', $this->uris) . '/' : '') . trim($segment, '/'));

      if (!isset(self::$routers[$method])) {
        self::$routers[$method] = [];
      }

      return self::$routers[$method][$this->segment] = &$this;
    }

    public function title($title = null) {
      if ($title === null) {
        return $this->title;
      }

      $this->title = $title;
      return $this;
    }

    public function return($func) {
      $this->func = $func;
      return $this;
    }

    public function controller($controller) {
      $controller = trim($controller, '/');
      if (strpos($controller, '@') === false) {
        $controller = $controller . '@' . 'index';
      }

      list($path, $this->method) = explode('@', $controller);

      $this->class = pathinfo($path, PATHINFO_BASENAME);
      $path = pathinfo($path, PATHINFO_DIRNAME);

      if ($path !== '.') {
        array_push($this->dirs, $path);
      }

      return $this->name(implode('', array_map('ucfirst', [$this->class, $this->method])));
    }

    public function name($name = null) {
      if ($name === null) {
        return $this->name;
      }

      $key = implode('', array_map('ucfirst', array_merge($this->groupNames, [$this->class, $this->method])));

      if (isset(self::$names[$key])) {
        unset(self::$names[$key]);
      }

      return self::$names[$this->name = implode('', array_map('ucfirst', $this->groupNames)) . $name] = &$this;
    }

    public function alias($name) {
      return $this->name($name);
    }

    public function mid() {
      $this->mids = array_merge($this->mids, array_reduce(func_get_args(), function($a, $b) { return array_merge($a, is_array($b) ? $b : [$b]); }, []));
      return $this;
    }

    public function __call($name, $arguments) {
      if ($name == 'class') {
        return $this->class;
      }

      if ($name == 'method') {
        return $this->method;
      }

      if ($name == 'func') {
        return $this->func;
      }

      if ($name == 'mids') {
        return $this->mids;
      }

      if ($name == 'path') {
        return implode(DIRECTORY_SEPARATOR, array_merge($this->dirs, [$this->class]));
      }

      QQ('Router 不存在「' . $name . '」方法！');
    }

    public static function group($options, $items = null) {
      return new \Router\Group($options, $items);
    }

    public static function init() {
      $file1s = @scandir(PATH_ROUTER);

      if ($file1s === false) {
        $file1s = [];
      }

      foreach ($file1s as $file1) {
        if (!in_array($file1, ['.', '..'])) {
          $path = PATH_ROUTER . $file1;

          if (pathinfo($file1, PATHINFO_EXTENSION) == 'php') {
            Load::file(PATH_ROUTER . $file1);
          }

          if (is_dir($path)) {
            $file2s = @scandir($path);

            if ($file2s === false) {
              $file2s = [];
            }

            foreach ($file2s as $$file2) {
              if (!in_array($file2, ['.', '..'])) {
                if (pathinfo($file2, PATHINFO_EXTENSION) == 'php') {
                  Load::file($path . DIRECTORY_SEPARATOR . $file2);
                }
              }
            }
          }
        }
      }
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
      $methods = [
        'cli',
        'get',
        'post',
        'put',
        'delete',
        'del',
        'head',
        'options',
        'patch',
      ];

      if (!in_array($name = strtolower($name), $methods)) {
        QQ('Router 不存在「' . $name . '」的 Static 方法！');
      }

      $name == 'del' && $name = 'delete';

      if (!$args) {
        $args = [''];
      }

      $groups = array_reverse(array_map(function($trace) {
        return $trace['object'];
      }, array_filter(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT), function($trace) {
        return isset($trace['function'], $trace['object'])
          && $trace['function'] == '__construct'
          && $trace['object'] instanceof \Router\Group;
      })));

      return new Router($name, $groups, array_shift($args));
    }

    public static function clean() {
      self::$names = null;
      self::$routers = null;
      self::$regxPattern = null;
      return true;
    }

    public static function &names() {
      return self::$names;
    }

    public static function &all() {
      return self::$routers;
    }
  }
}
