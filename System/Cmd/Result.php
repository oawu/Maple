<?php

namespace Cmd;

use \Cmd\Result\Group;

final class Result {
  public static function help(string $text): self {
    return self::create()->setText($text);
  }
  public static function create(string $title = '', ?callable $callback = null): self {
    $result = new self($title, $callback);

    return $result;
  }

  private ?string $_text = null;
  private string $_title = '';
  private array $_groups = [];
  private ?\Exception $_error = null;

  private function __construct(string $title = '', ?callable $callback = null) {
    $this->_title = $title;

    try {
      $_result = $callback;
      if ($callback) {
        $_result = $callback($this);
      }
    } catch (\Exception $e) {
      $this->setError($e);
    }
  }

  public function setError(\Exception $e): self {
    $this->_error = $e;
    return $this;
  }
  public function getError(): ?\Exception {
    return $this->_error;
  }
  public function setText(string $text): self {
    $this->_text = $text;
    return $this;
  }
  public function display(bool $isJson = false): string {
    if ($isJson) {
      $groups = [];

      foreach ($this->_groups as $group) {
        $groups[] = json_decode($group->display($isJson), true);
      }

      return json_encode([
        'title' => $this->_title,
        'groups' => $groups,
        'error' => $this->_error ? $this->_error->getMessage() : null,
      ], JSON_UNESCAPED_UNICODE);
    }

    if ($this->_text !== null) {
      return $this->_text;
    }

    $str = '';
    if ($this->_title !== '') {
      $str = "\n" . $this->_title . ' 開始' . "\n";
    }
    foreach ($this->_groups as $group) {
      $str .= $group->display($isJson);
    }
    if ($this->_error !== null) {
      $str .= "\n";
      $str .= "\n──────────────────────\n ※※※※※ 發生錯誤 ※※※※※\n──────────────────────\n";
      $str .= ' ◉ ' . $this->_error->getMessage() . "\n";
      $str .= "\n";
    } else {
      if ($this->_title !== '') {
        $str .= "\n" . $this->_title . ' 完成' . "\n";
      }
    }
    $str .= "\n";

    return $str;
  }
  public function pushGroup(Group $group): self {
    $this->_groups[] = $group;
    return $this;
  }
}
