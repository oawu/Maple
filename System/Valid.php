<?php

use \Valid\Rule;
use \Valid\Rule\Num\_Int;
use \Valid\Rule\Num\_Float;
use \Valid\Rule\_String;
use \Valid\Rule\String\_Url;
use \Valid\Rule\String\_Email;
use \Valid\Rule\String\_Date;
use \Valid\Rule\String\_Time;
use \Valid\Rule\String\_Datetime;
use \Valid\Rule\_Bool;
use \Valid\Rule\_Enum;
use \Valid\Rule\_Any;
use \Valid\Rule\_UploadFile;
use \Valid\Rule\_Array;
use \Valid\Rule\_Object;

final class Valid {
  private static $_ifError = null;

  public static function check($data, $rule, ?callable $_callback = null) {
    if (is_array($rule)) {
      $rule = _Object::create(true, '資料')->setRules($rule);
    }

    $result = null;
    try {
      if ($rule instanceof Rule) {
        $result = $rule->check($data);
      } else {
        throw new \Exception('條件錯誤');
      }
    } catch (\Valid\Exception $e) {
      $callback = $_callback ?? self::$_ifError;
      if ($callback) {
        $callback($e->getReason(), $e->getErrorCode());
      }
    } catch (\Exception $e) {
      $callback = $_callback ?? self::$_ifError;
      if ($callback) {
        $callback('其他錯誤', null);
      }
    }

    return $result;
  }
  public static function setIfError(?callable $callback): void {
    self::$_ifError = $callback;
  }
  public static function ifError(?callable $callback): void {
    self::setIfError($callback);
  }
  public static function error(?callable $callback): void {
    self::setIfError($callback);
  }

  public static function int(string $title = '', ?int $code = null): _Int { return _Int::create(true, $title, $code); }
  public static function int8(string $title = '', ?int $code = null): _Int { return _Int::create(true, $title, $code)->min(-127)->max(127); }
  public static function int16(string $title = '', ?int $code = null): _Int { return _Int::create(true, $title, $code)->min(-32767)->max(32767); }
  public static function int32(string $title = '', ?int $code = null): _Int { return _Int::create(true, $title, $code)->min(-2147483647)->max(2147483647); }
  public static function int64(string $title = '', ?int $code = null): _Int { return _Int::create(true, $title, $code)->min(-9223372036854775807)->max(9223372036854775807); }
  public static function uInt(string $title = '', ?int $code = null): _Int { return _Int::create(true, $title, $code)->min(0); }
  public static function uInt8(string $title = '', ?int $code = null): _Int { return _Int::create(true, $title, $code)->min(0)->max(255); }
  public static function uInt16(string $title = '', ?int $code = null): _Int { return _Int::create(true, $title, $code)->min(0)->max(65535); }
  public static function uInt32(string $title = '', ?int $code = null): _Int { return _Int::create(true, $title, $code)->min(0)->max(4294967295); }
  public static function uInt64(string $title = '', ?int $code = null): _Int { return _Int::create(true, $title, $code)->min(0)->max(PHP_INT_MAX); }
  public static function id(string $title = '', ?int $code = null): _Int { return _Int::create(true, $title, $code)->min(1)->max(PHP_INT_MAX); }
  public static function timestamp(string $title = '', ?int $code = null): _Int { return _Int::create(true, $title, $code)->min(0); }
  public static function float(string $title = '', ?int $code = null): _Float { return _Float::create(true, $title, $code); }
  public static function uFloat(string $title = '', ?int $code = null): _Float { return _Float::create(true, $title, $code)->min(0); }
  public static function string(string $title = '', ?int $code = null): _String { return _String::create(true, $title, $code); }
  public static function url(string $title = '', ?int $code = null): _Url { return _Url::create(true, $title, $code); }
  public static function email(string $title = '', ?int $code = null): _Email { return _Email::create(true, $title, $code); }
  public static function date(string $title = '', ?int $code = null): _Date { return _Date::create(true, $title, $code); }
  public static function time(string $title = '', ?int $code = null): _Time { return _Time::create(true, $title, $code); }
  public static function datetime(string $title = '', ?int $code = null): _Datetime { return _Datetime::create(true, $title, $code); }
  public static function bool(string $title = '', ?int $code = null): _Bool { return _Bool::create(true, $title, $code); }
  public static function enum(string $title = '', array $items, ?int $code = null) { return _Enum::create(true, $title, $code)->setItems($items); }
  public static function any(string $title = '', ?int $code = null) { return _Any::create(true, $title, $code); }
  public static function uploadFile(string $title = '', ?int $code = null) { return _UploadFile::create(true, $title, $code); }
  public static function array(string $title = '', Rule $rule, ?int $code = null): _Array { return _Array::create(true, $title, $code)->setRule($rule); }
  public static function object(string $title, array $rules, ?int $code = null) { return _Object::create(true, $title, $code)->setRules($rules); }

  public static function int_(string $title = '', ?int $code = null): _Int { return _Int::create(false, $title, $code); }
  public static function int8_(string $title = '', ?int $code = null): _Int { return _Int::create(false, $title, $code)->min(-127)->max(127); }
  public static function int16_(string $title = '', ?int $code = null): _Int { return _Int::create(false, $title, $code)->min(-32767)->max(32767); }
  public static function int32_(string $title = '', ?int $code = null): _Int { return _Int::create(false, $title, $code)->min(-2147483647)->max(2147483647); }
  public static function int64_(string $title = '', ?int $code = null): _Int { return _Int::create(false, $title, $code)->min(-9223372036854775807)->max(9223372036854775807); }
  public static function uInt_(string $title = '', ?int $code = null): _Int { return _Int::create(false, $title, $code)->min(0); }
  public static function uInt8_(string $title = '', ?int $code = null): _Int { return _Int::create(false, $title, $code)->min(0)->max(255); }
  public static function uInt16_(string $title = '', ?int $code = null): _Int { return _Int::create(false, $title, $code)->min(0)->max(65535); }
  public static function uInt32_(string $title = '', ?int $code = null): _Int { return _Int::create(false, $title, $code)->min(0)->max(4294967295); }
  public static function uInt64_(string $title = '', ?int $code = null): _Int { return _Int::create(false, $title, $code)->min(0)->max(PHP_INT_MAX); }
  public static function id_(string $title = '', ?int $code = null): _Int { return _Int::create(false, $title, $code)->min(1)->max(PHP_INT_MAX); }
  public static function timestamp_(string $title = '', ?int $code = null): _Int { return _Int::create(false, $title, $code)->min(0); }
  public static function float_(string $title = '', ?int $code = null): _Float { return _Float::create(false, $title, $code); }
  public static function uFloat_(string $title = '', ?int $code = null): _Float { return _Float::create(false, $title, $code)->min(0); }
  public static function string_(string $title = '', ?int $code = null): _String { return _String::create(false, $title, $code); }
  public static function url_(string $title = '', ?int $code = null): _Url { return _Url::create(false, $title, $code); }
  public static function email_(string $title = '', ?int $code = null): _Email { return _Email::create(false, $title, $code); }
  public static function date_(string $title = '', ?int $code = null): _Date { return _Date::create(false, $title, $code); }
  public static function time_(string $title = '', ?int $code = null): _Time { return _Time::create(false, $title, $code); }
  public static function datetime_(string $title = '', ?int $code = null): _Datetime { return _Datetime::create(false, $title, $code); }
  public static function bool_(string $title = '', ?int $code = null): _Bool { return _Bool::create(false, $title, $code); }
  public static function enum_(string $title = '', array $items, ?int $code = null) { return _Enum::create(false, $title, $code)->setItems($items); }
  public static function any_(string $title = '', ?int $code = null) { return _Any::create(false, $title, $code); }
  public static function uploadFile_(string $title = '', ?int $code = null) { return _UploadFile::create(false, $title, $code); }
  public static function array_(string $title, Rule $rule, ?int $code = null): _Array { return _Array::create(false, $title, $code)->setRule($rule); }
  public static function object_(string $title, array $rules, ?int $code = null) { return _Object::create(false, $title, $code)->setRules($rules); }
}
