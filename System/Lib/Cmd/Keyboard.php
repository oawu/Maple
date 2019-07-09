<?php

namespace CMD;

class Keyboard {
  private $closure = null;
  private $stdin = null;
  private $running = false;

  public function __construct($closure = null) {

    $this->setStdin()
         ->setClosure($closure);
  }

  public function setStdin($stdin = null) {
    $this->stdin = $stdin ?: fopen('php://stdin', 'r');
    system('stty -icanon -echo');
    stream_set_blocking($this->stdin, 0);
    return $this;
  }

  public function setClosure($closure) {
    $this->closure = $closure;
    return $this;
  }

  private function codes() {
    return unpack("C*", fgets($this->stdin));
  }

  public function stop() {
    $this->running = false;
    return $this;
  }

  public function run() {
    $this->running = true;
    while ($this->running) {
      $reads = [$this->stdin];
      $writes = [];
      $except = null;
      @stream_select($reads, $writes, $except, null, null) !== false
        && is_callable($this->closure)
        && call_user_func($this->closure, $this->codes(), $this);
    }

    return $this;
  }

  static public function create() {
    return new self();
  }

  static public function listener($closure) {
    return new self($closure);
  }
}