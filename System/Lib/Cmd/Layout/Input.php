<?php

namespace CMD\Layout;

use CMD\Str      as Str;
use CMD\Display  as Display;
use CMD\Keyboard as Keyboard;

class Input extends Doing {
  private $check,
          $validator,
          $inputs = [],
          $autocompletes = [];

  public function setAutocomplete() {
    $this->autocompletes = array_filter(func_get_args(), 'is_string');
    return $this;
  }

  public function setValidator($validator) {
    $this->validator = $validator;
    return $this;
  }

  public function isCheck($check = true) {
    $this->check = $check;
    return $this;
  }

  public function appendInput(string $title, bool $need = true, string $rule = '/[0-9A-Za-z-_ ]/') {
    array_push($this->inputs, [
      'type' => 'text',
      'title' => $title,
      'need' => $need,
      'rule' => $rule,
    ]);
    return $this;
  }

  public function appendCheck(string $title) {
    array_push($this->inputs, [
      'type' => 'check',
      'title' => $title,
    ]);
    return $this;
  }

  private function getBool($input) {
    echo $title = Display::markArrowLine($input['title'] . '？[y：確定, n：取消]' . Display::markSemicolon(), true);
    
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

    return $cho === 'y';
  }

  private function getText($input) {
    static $histories = [];

    echo $title = Display::markArrowLine($input['title'] . Display::markSemicolon(), true);

    $strs = [];
    $index = 0;
    $historyIndex = count($histories) - 1;
    Keyboard::listener(function($codes, $keyboard) use (&$strs, &$index, &$historyIndex, &$histories, $input, $title) {
      $need = $input['need'];
      $rule = $input['rule'];

      $code = implode(',', $codes);
      if (in_array($code, ['27,91,68', '27,91,67', '27,91,65', '27,91,66'])) {
        if (in_array($code, ['27,91,68', '27,91,67'])) {
          if ($code == '27,91,68' && $index > 0) {
            echo "\033[" . Str::width($strs[--$index]) . "D";
          }
          if ($code == '27,91,67' && $index < count($strs)) {
            echo "\033[" . Str::width($strs[$index++]) . "C";
          }
        } else {
          if (!$histories)
            return;

          if ($historyIndex === false)
            return;
          
          echo $strs ? "\033[" . Str::width(implode('', $strs)) . "D\033[K" : '';
          
          if ($code == '27,91,65') {
            echo implode('', $strs = Str::split($histories[$historyIndex--]));
          } else {
            echo implode('', $strs = Str::split($histories[$historyIndex++]));
          }
          $index = count($strs);

          $historyIndex < 0 && $historyIndex = count($histories) - 1;
          $historyIndex >= count($histories) && $historyIndex = 0;
        }
        return;
      }

      $historyIndex = false;

      if ($code === '127') { // 倒退
        if ($index <= 0)
          return ;

        $after = array_splice($strs, $index);
        $str = $strs[--$index];
        $before = array_splice($strs, 0, $index);
        $strs = array_merge($before, $after);
        
        echo "\033[" . Str::width($str) . "D" . "\033[K" . implode('', $after);
        echo $after ? "\033[" . Str::width(implode('', $after)) . "D" : '';
        
        $index != 0 || $historyIndex = 0;
        return;
      }
      
      if ($code == 10 && (!$need || trim(implode('', $strs)) !== '')) { // Enter
        if (trim($tmp = implode('', $strs)) !== '') echo "\033[" . Str::width($tmp) . "D\033[K" . Display::colorBoldWhite($tmp, true);
        else echo \Xterm::black('無', true)->dim();
        echo Display::LN;
        array_push($histories, trim(implode('', $strs)));
        return $keyboard->stop();
      }

      if ($code == 9 && $this->autocompletes) { // tab
        $tmp = implode('', $strs);
        echo $tmp ? "\033[" . Str::width($tmp) . "D\033[K" : '';
        $autocompletes = array_shift($this->autocompletes);
        array_push($this->autocompletes, $autocompletes);
        $index = count($strs = Str::split($autocompletes));
        echo implode('', $strs);
        return;
      }

      // $codesStr = call_user_func_array('pack', array_merge(["C*"], $codes));
      $codesStrs = array_map(function($code) { return pack("C*", $code); }, $codes);
      $codesStr = implode('', $codesStrs);

      if ($rule && preg_match_all($rule, $codesStr)) {
        $after = array_splice($strs, $index);
        echo $codesStr . implode('', $after);
        
        $index += count($codes);
        $strs = array_merge($strs, $codesStrs, $after);
        
        echo $after ? "\033[" . Str::width(implode('', $after)) . "D" : '';
      }
    })->run();

    return trim(implode('', $strs));
  }

  public function showError($errors) {
    if ($errors) {
      is_array($errors) || $errors = [$errors];

      Display::title('輸入資訊有錯誤');

      foreach ($errors as $error) {
        $lines = Display::lines($error, Display::MAX_LEN - 8);

        foreach ($lines as $line)
          echo Str::repeat(3) . Display::markList('※') . Str::repeat() . $line[0] . Display::LN;
      }
    }
    return $this;
  }

  public function choice() {
    if (!is_callable($thingFunc = $this->thingFunc))
      return $this->back();
    $strs = [];
    
    $error = [];

    do {
      $strs = [];
      
      do {

        $strs = [];
        $this->showTips();
        $this->showError($error);
        
        if ($this->inputs) {
          Display::title('請輸入以下資訊');
          foreach ($this->inputs as $input) {
            switch ($input['type']) {
              case 'check':
                array_push($strs, $this->getBool($input));
                
                break;
              
              default:
              case 'text':
                array_push($strs, $this->getText($input));
                break;
            }
          }
        }
        
        $strs = array_filter($strs, function($str) { return $str !== null && (is_string($str) || is_bool($str));});
      
      } while(($validator = $this->validator) && is_callable($validator) && ($error = call_user_func_array($validator, $strs)));
    } while(($this->check && $this->check(is_string($this->check) ? $this->check : '請確認以上資訊是否正確？') == 'n') || count($strs) != count($this->inputs));

    return  call_user_func_array('parent::choice', $strs);
  }
}