<?php

namespace CMD;

class Str {
  static public function split($str) {
    return array_filter(preg_split('//u', $str), function($t) { return $t !== ''; });
  }

  static public function width($str) {
    return array_sum(array_map(function($t) { return mb_strwidth($t); }, Str::split($str)));
  }

  static public function repeat($multiplier = 1, $input = ' ') {
    return str_repeat($input, $multiplier);
  }

  public static function docx3($str, $max = null) {
    $strs = [];
    foreach (Str::split($str) as $val) {
      if (($width = Str::width($val)) > $max) {
        if ($max) {
          array_push($strs, '…');
        } else {
          array_pop($strs);
          array_push($strs, '…');
        }
        break;
      } else {
        $max -= $width;
        array_push($strs, $val);
      }
    }
    return implode('', $strs);
  }

  public static function splitWords($str, $max = null) {
    $chars = Str::split($str);
    $words = $tmps = [];

    foreach ($chars as $char) {
      if (strlen($char) == 3) {
        array_push($words, ['word' => implode('', $tmps), 'len' => count($tmps), 'chinese' => false]);
        $tmps = [];

        array_push($words, ['word' => $char, 'len' => 2, 'chinese' => true]);
      } else if ($char == ' ') {
        array_push($words, ['word' => implode('', $tmps), 'len' => count($tmps), 'chinese' => false]);
        $tmps = [];

      } else {
        array_push($tmps, $char);

        if ($max && count($tmps) >= $max) {
          array_push($words, ['word' => implode('', $tmps), 'len' => count($tmps), 'chinese' => false]);
          $tmps = [];
        }
      }
    }

    array_push($words, ['word' => implode('', $tmps), 'len' => count($tmps), 'chinese' => false]);
    $tmps = [];

    return array_filter($words, function($word) { return $word['word'] !== ''; });
  }
}