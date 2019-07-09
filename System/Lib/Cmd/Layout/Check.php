<?php

namespace CMD\Layout;

use CMD\Str      as Str;
use CMD\Display  as Display;

class Check extends Doing {
  public function choice() {
    if (!is_callable($thingFunc = $this->thingFunc))
      return $this->back();
    
    $this->showTips();
    $cho = $this->check('確定？');

    return $cho === 'n' ? $this->back() : parent::choice();
  }
}
