<?php

namespace Cmd\Result\Group;

use \Cmd\Display;
use \Cmd\Result;
use \Cmd\Result\Group;

final class Step {
  public static function create(string $title, $callback = null) {
    $group = null;
    foreach (debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT) as $trace) {
      if (isset($trace['object']) && $trace['object'] instanceof Group) {
        $group = $trace['object'];
        break;
      }
    }

    $isRunable = $group && $group->getResult()->getError() === null;
    if (!$isRunable) {
      return null;
    }

    $step = new self($title, $group, $callback);
    return $step->getReturn();
  }

  private string $_title = '';
  private string $_desc = '完成';
  private Group $_group;

  private $_return = null;
  private function __construct(string $title, Group $group, $callback = null) {
    $this->_title = $title;
    $this->_group = $group;
    $group->pushStep($this);

    try {
      if (is_callable($callback)) {
        $this->_return = $callback($this, $group, $group->getResult());
        $this->_desc = '完成';
      } else {
        $this->_desc = $callback;
        $this->_return = $callback;
      }
    } catch (\Exception $e) {
      $this->setError($e);
      $this->_return = null;
      $this->_desc = '錯誤';
    }
  }

  public function setError(\Exception $e): self {
    $this->getResult()->setError($e);
    return $this;
  }

  public function getReturn() {
    return $this->_return;
  }

  public function getGroup(): Group {
    return $this->_group;
  }

  public function getResult(): Result {
    return $this->getGroup()->getResult();
  }

  public function display(bool $isJson = false): string {
    if ($isJson) {
      return json_encode([
        'title' => $this->_title,
        'result' => $this->_desc,
      ], JSON_UNESCAPED_UNICODE);
    }

    $str = Display::title($this->_title);
    $str .= Display::result($this->_desc);
    return $str;
  }
}
