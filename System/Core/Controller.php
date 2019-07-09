<?php

abstract class Controller {}

class ControllerException extends Exception {
  private $messages = [];
  private $statusCode = 400;

  public function __construct($messages) {
    parent::__construct('');
    
    isset($messages[0])
      && is_numeric($messages[0])
      && $this->statusCode = array_shift($messages);

    $this->messages = $messages;
  }

  public function getStatusCode() {
    return $this->statusCode;
  }
  public function getMessages() {
    return $this->messages;
  }
}

spl_autoload_register(function($className) {
  if (!preg_match('/Controller$/', $className))
    return false;

  Load::controller('_' . DIRECTORY_SEPARATOR . $className);
  return class_exists($className);
});

if (!function_exists('ifError')) {
  function ifError($defined = null) {
    static $closure;

    if (is_callable($defined))
      return $closure = $defined;

    if ($closure === null)
      return null;

    return call_user_func_array($closure, func_get_args());
  }
}

if (!function_exists('ifApiError')) {
  function ifApiError($defined = null) {
    Status::$isApi = true;
    return ifError($defined);
  }
}

if (!function_exists('ifErrorTo')) {
  function ifErrorTo($routerName) {
    Status::$isApi = false;
    $url = call_user_func_array('Url::router', func_get_args());

    ifError(function($error, $params = []) use ($url) {
      Url::refreshWithFailureFlash($url, $error, $params);
    });

    return true;
  }
}

if (!function_exists('error')) {
  function error() {
    $args = func_get_args();

    if (!Status::$isApi) {
      $params = false;
      foreach (debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT) as $obj) {
      
        // Shari sup up up
        if (isset($obj['function'], $obj['args']) && $obj['args'] && $obj['function'] == 'transaction' && $obj['args'][0] instanceof Closure) {
          $func = new ReflectionFunction($obj['args'][0]);
          $vars = $func->getStaticVariables();
          if (array_key_exists('params', $vars)) {
            $args = array_merge($args, [$vars['params']]);
            $params = true;
            break;
          }
        }
     
        if (!$params && isset($obj['function'], $obj['class'], $obj['args']) && $obj['class'] == 'Validator' && $obj['function'] == 'post' && $obj['args'][0] instanceof Closure) {
          $args = array_merge($args, [Input::post()]);
          $params = true;
          break;
        }
      }

      $params
        || Router::requestMethod() != 'post'
        || $args = array_merge($args, [Input::post()]);
    }

    throw new ControllerException($args);
  }
}
