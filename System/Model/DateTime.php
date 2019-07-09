<?php

namespace _M;

class DateTime {
  const FORMAT_DATE = 'Y-m-d';
  const FORMAT_DATETIME = 'Y-m-d H:i:s';

  private $format;
  private $datetime;

  public function __construct($str, $type) {
    $this->format = $type == 'datetime'
                      ? self::FORMAT_DATETIME
                      : self::FORMAT_DATE;

    $this->datetime = \DateTime::createFromFormat($this->format, $str);
  }

  public static function createByString($str, $type) {
    return new static($str, $type);
  }

  public function isFormat() {
    return !($this->datetime === false);
  }

  public function timestamp() {
    return $this->isFormat() ? $this->datetime->getTimestamp() : null;
  }

  public function format($format = null, $default = null) {
    // U -> timestamp, 'c' -> ISO 8601 date(2004-02-12T15:19:21+00:00)
    // http://php.net/manual/en/function.date.php
    return $this->isFormat() ? $this->datetime->format($format === null ? $this->format : $format) : $default;
  }

  public function __toString() {
    return $this->format(null, '');
  }
}