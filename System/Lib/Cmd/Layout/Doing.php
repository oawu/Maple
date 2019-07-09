<?php

namespace CMD\Layout;

use CMD\Str      as Str;
use CMD\Display  as Display;
use CMD\Keyboard as Keyboard;

class Doing extends Item {
  protected $thingFunc,
            $tips = [],
            $thingArgs = [];

  public function appendTip(string $tip) {
    array_push($this->tips, $tip);
    return $this;
  }
  
  public function thing($thingFunc, $thingArgs = []) {
    $this->thingFunc = $thingFunc;
    is_array($thingArgs) || $thingArgs = [$thingArgs];
    $this->thingArgs = $thingArgs;
    return $this;
  }

  public function showTips() {
    $cho = $this->index() + 1;
    Item::show($cho, $this->parent, true);
    
    if ($this->tips) {
      Display::title('注意事項');
      echo implode('', array_map(function($tip) { return Str::repeat(3) . Display::markList() . Str::repeat() . $tip . Display::LN; }, $this->tips));
    }

    return $this;
  }

  public function check($title = '是否正確') {
    Display::title('確認');

    echo $title = Display::markArrowLine($title . '[y：確定, n：取消]' . Display::markSemicolon(), true);

    $cho = null;
    Keyboard::listener(function($codes, $keyboard) use (&$cho, $title) {
      if (count($codes) != 1)
        return ;

      $codes = array_shift($codes);

      if (in_array($cho, ['y', 'n'])) {
        if ($codes == 10) {
          echo "\r\033[K" . $title . Display::colorBoldWhite($cho === 'y' ? '確定' : '取消', true);
          echo Display::LN;
          return $keyboard->stop();
        } else {
          $cho == null;
        }
      }

      echo "\r\033[K" . $title;

      if (in_array($cho = strtolower(chr($codes)), ['y', 'n']))
        echo $cho;
    })->run();

    return $cho;
  }

  public function choice() {
    return is_callable($thingFunc = $this->thingFunc)
      ? call_user_func_array($thingFunc, array_merge([$this], $this->thingArgs, func_get_args()))
      : $this->back();
  }
}
