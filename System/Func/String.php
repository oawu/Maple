<?php

if (!function_exists('minText')) {
  function minText($text, $length = 200) {
    return $length ? mb_strimwidth($text, 0, $length, '…','UTF-8') : $text;
  }
}