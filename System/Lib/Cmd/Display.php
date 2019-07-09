<?php

namespace CMD;

class Display {
  const LN      = "\n";
  const LR      = "\r";
  const MAX_LEN = 68;

  public static function line($title = null, $error = null) {
    static $lines = [];

    if (is_string($title)) {
      $titles = func_get_args();
      
      preg_match('/^(\s+)/', $title, $matches);
      $identSize = $matches ? strlen($matches[1]) : 0;
      
      $lines = array_map(function($i, $title) use ($identSize) {
        return "\033[K" . Str::repeat(3 + $identSize + ($i ? 2 : $i * 2)) . ($i ? Display::markHash() : Display::markList()) . Str::repeat() . ltrim($title);
      }, array_keys($titles), $titles);

      echo implode(Display::LN, $lines = array_filter($lines)) . \Xterm::black('…', true) . Str::repeat();
    }

    if (is_bool($title)) {
      if ($title === true) {
        $lines[0] .= Str::repeat() . \Xterm::black('─', true)->dim() . Str::repeat() . \Xterm::green($error ?: '完成');
        echo (count($lines) > 1 ? "\033[" . (count($lines) - 1) . "A" : '') . Display::LR . implode(Display::LN, $lines) . Display::LN;
      } else {
        $lines[0] .= Str::repeat() . \Xterm::black('─', true)->dim() . Str::repeat() . \Xterm::red('錯誤');
        echo (count($lines) > 1 ? "\033[" . (count($lines) - 1) . "A" : '') . Display::LR . implode(Display::LN, $lines) . Display::LN;
        $error && Display::error($error);
        return false;
      }  
    }

    return true;
  }

  private static function tokensLen($words) {
    $len = 0;
    for ($i = 0, $c = count($words); $i < $c; $i++)
      if (!$i) $len += $words[$i]['len'];
      else if ($words[$i - 1]['chinese'] && $words[$i]['chinese']) $len += $words[$i]['len'];
      else $len += $words[$i]['len'] + 1;
    return $len;
  }

  public static function lines($str, $max = null) {
    $words = Str::splitWords($str, $max);
    $lines = $tokens = [];

    foreach ($words as $word) {
      $tokensLen = self::tokensLen($tokens);

      if (!$tokensLen) {
        array_push($tokens, $word);
      } else {
        $end = end($tokens);

        if ($end['chinese'] && $word['chinese']) {
          if ($max && $tokensLen + $word['len'] > $max) {
            
            array_push($lines, $tokens);
            $tokens = [$word];
          } else {
            array_push($tokens, $word);
          }
        } else {
          if ($max && $tokensLen + $word['len'] + 1 > $max) {
            array_push($lines, $tokens);
            $tokens = [$word];
          } else {
            array_push($tokens, $word);
          }
        }
      }
    }

    array_push($lines, $tokens);

    return array_map(function($words) {
      $str = '';
      
      for ($i = 0, $c = count($words); $i < $c; $i++)
        if (!$i) $str = $words[$i]['word'];
        else if ($words[$i - 1]['chinese'] && $words[$i]['chinese']) $str .= $words[$i]['word'];
        else $str .= ' ' . $words[$i]['word'];

      return [$str, Str::width($str)];
    }, $lines);
  }

  public static function error($error) {
    if (!$error)
      return;
    
    echo Display::LN . Str::repeat() . \Xterm::red('【錯誤訊息】') . Display::LN;

    is_array($error) || $error = [$error];

      foreach ($error as $err)
        echo Str::repeat(3) . Display::markList() . Str::repeat() . $err . Display::LN;

    echo Display::LN;
    exit(1);
  }

  public static function mainError($content, $title = '錯誤') {
    $lines = Display::lines($content, Display::MAX_LEN - 8);
    $titleLen = array_sum(array_map(function($t) { return strlen($t) == 3 ? 2 : 1; }, Str::split($title)));

    $lineColor = new \Xterm();
    $lineColor->color(Xterm::WHITE);
    $lineColor->background(Xterm::RED);

    $titleColor = new \Xterm();
    $titleColor->color(Xterm::YELLOW);
    $titleColor->background(Xterm::RED);
    $titleColor->blod();

    echo Display::LN;
    echo $lineColor->str(' ╭') . $lineColor->str('─') . $titleColor->str(' ' . $title . ' ') . $lineColor->str(Str::repeat(Display::MAX_LEN - $titleLen - 7, '─')) . $lineColor->str('╮ ');
    echo Display::LN;
    // echo $lineColor->str(' │') . $lineColor->str(Str::repeat(Display::MAX_LEN - 4)) . $lineColor->str('│ ');
    // echo Display::LN;
    foreach ($lines as $line) {
      echo $lineColor->str(' │') . $lineColor->str('  ' . $line[0] . '  ' . Str::repeat(Display::MAX_LEN - $line[1] - 8)) . $lineColor->str('│ ');
      echo Display::LN;
    }
    echo $lineColor->str(' ╰') . $lineColor->str(Str::repeat(Display::MAX_LEN - 4, '─')) . $lineColor->str('╯ ');
    echo Display::LN;
    echo Display::LN;
    exit(1);
  }

  public static function logo() {
    $space = Display::MAX_LEN - 43 - 4;
    
    $l = $space > 1 ? (int)($space / 2) : 0;
    $r = Str::repeat($space - $l);
    $l = Str::repeat($l);

    $c = \Xterm::black('', true)->dim();
    echo Str::repeat() . Display::markBorder1() . Str::repeat(Display::MAX_LEN - 4, Display::markBorder5()) . Display::markBorder3(); echo Display::LN;
    echo Str::repeat() . Display::markBorder6() . $l . '███' . $c->str('╗') . '   ███' . $c->str('╗') . ' █████' . $c->str('╗') . ' ██████' . $c->str('╗') . ' ██' . $c->str('╗') . '     ███████' . $c->str('╗') . '' . $r . Display::markBorder6(); echo Display::LN;
    echo Str::repeat() . Display::markBorder6() . $l . '████' . $c->str('╗') . ' ████' . $c->str('║') . '██' . $c->str('╔══') . '██' . $c->str('╗') . '██' . $c->str('╔══') . '██' . $c->str('╗') . '██' . $c->str('║') . '     ██' . $c->str('╔════╝') . '' . $r . Display::markBorder6(); echo Display::LN;
    echo Str::repeat() . Display::markBorder6() . $l . '██' . $c->str('╔') . '████' . $c->str('╔') . '██' . $c->str('║') . '███████' . $c->str('║') . '██████' . $c->str('╔╝') . '██' . $c->str('║') . '     █████' . $c->str('╗') . '  ' . $r . Display::markBorder6(); echo Display::LN;
    echo Str::repeat() . Display::markBorder6() . $l . '██' . $c->str('║╚') . '██' . $c->str('╔╝') . '██' . $c->str('║') . '██' . $c->str('╔══') . '██' . $c->str('║') . '██' . $c->str('╔═══╝') . ' ██' . $c->str('║') . '     ██' . $c->str('╔══╝') . '  ' . $r . Display::markBorder6(); echo Display::LN;
    echo Str::repeat() . Display::markBorder6() . $l . '██' . $c->str('║') . ' ' . $c->str('╚═╝') . ' ██' . $c->str('║') . '██' . $c->str('║') . '  ██' . $c->str('║') . '██' . $c->str('║') . '     ███████' . $c->str('╗') . '███████' . $c->str('╗') . '' . $r . Display::markBorder6(); echo Display::LN;
    echo Str::repeat() . Display::markBorder6() . $l . '' . $c->str('╚═╝') . '     ' . $c->str('╚═╝╚═╝') . '  ' . $c->str('╚═╝╚═╝') . '     ' . $c->str('╚══════╝╚══════╝') . '' . $r . Display::markBorder6(); echo Display::LN;
    echo Str::repeat() . Display::markBorder7() . Str::repeat(Display::MAX_LEN - 4, Display::markBorder5()) . Display::markBorder8(); echo Display::LN;
  }
  
  public static function title(string $str, bool $return = false) {
    $str = Display::LN . Str::repeat() . \Xterm::yellow('【' . $str . '】') . Display::LN;
    if ($return) return $str;
    echo $str;
  }
  
  public static function titleError(string $str, bool $return = false) {
    $str = Display::LN . Str::repeat() . \Xterm::red('【' . $str . '】') . Display::LN;
    if ($return) return $str;
    echo $str;
  }
  
  public static function markArrowLine(string $str, bool $return = false) {
    $str = Str::repeat(3) . Display::markArrow() . Str::repeat() . $str;
    if ($return) return $str;
    echo $str;
  }

  public static function markListLine(string $str, bool $return = false) {
    $str = Str::repeat(3) . Display::markList() . Str::repeat() . $str . Display::LN;
    if ($return) return $str;
    echo $str;
  }
  
  public static function markListLines(array $strs, bool $return = false) {
    $strs = implode('', array_map(function($str) {
      return \CMD\Display::markListLine($str, true);
    }, array_values(array_filter($strs, 'is_string'))));
    
    if ($return) return $strs;
    echo $strs;
  }
  
  public static function controlC() { return '過程中若要離開，請直接按下鍵盤上的 ' . \Xterm::red('control + c')->blod(); }
  public static function colorBoldWhite($str) { return \Xterm::gray($str, true)->blod(); }
  public static function colorBorder($str) { return \Xterm::black($str, true); }
  
  public static function markSemicolon() { return \Xterm::create('：')->dim(); }
  public static function markList()      { return \Xterm::purple('◉'); }
  public static function markHash()      { return \Xterm::purple('↳')->dim(); }

  public static function markArrow()     { return \Xterm::red('➜', true); }
  public static function markBorder1()   { return self::colorBorder('╭'); }
  public static function markBorder2()   { return self::colorBorder('╰'); }
  public static function markBorder3()   { return self::colorBorder('╮'); }
  public static function markBorder4()   { return self::colorBorder('╯'); }
  public static function markBorder5()   { return self::colorBorder('─'); }
  public static function markBorder6()   { return self::colorBorder('│'); }
  public static function markBorder7()   { return self::colorBorder('├'); }
  public static function markBorder8()   { return self::colorBorder('┤'); }
}

