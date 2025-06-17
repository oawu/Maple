<?php

namespace Cmd\Result;

use \Cmd\Display;
use \Cmd\Result;
use \Cmd\Result\Group\Step;

final class Group {
  public static function create(string $title, ?callable $callback = null) {
    $result = null;
    foreach (debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT) as $trace) {
      if (isset($trace['object']) && $trace['object'] instanceof Result) {
        $result = $trace['object'];
        break;
      }
    }

    $isRunable = $result && $result->getError() === null;
    if (!$isRunable) {
      return null;
    }

    $group = new self($title, $result, $callback);
    return $group->getReturn();
  }

  private string $_title = '';
  private array $_steps = [];
  private Result $_result;
  private $_return = null;

  private function __construct(string $title, Result $result, ?callable $callback = null) {
    $this->_title = $title;
    $this->_result = $result;
    $result->pushGroup($this);

    try {
      $this->_return = $callback;
      if ($callback) {
        $this->_return = $callback($this, $result);
      }
    } catch (\Exception $e) {
      $this->setError($e);
      $this->_return = null;
    }
  }

  public function getReturn() {
    return $this->_return;
  }
  public function getResult(): Result {
    return $this->_result;
  }
  public function pushStep(Step $step): self {
    $this->_steps[] = $step;
    return $this;
  }
  public function setError(\Exception $e): self {
    $this->getResult()->setError($e);
    return $this;
  }
  public function display(bool $isJson = false): string {
    if ($isJson) {
      return json_encode([
        'title' => $this->_title,
        'steps' => array_map(static function (Step $step) use ($isJson) {
          return json_decode($step->display($isJson), true);
        }, $this->_steps),
      ], JSON_UNESCAPED_UNICODE);
    }

    $str = Display::main($this->_title);
    foreach ($this->_steps as $step) {
      $str .= $step->display($isJson);
    }
    return $str;
  }
}
