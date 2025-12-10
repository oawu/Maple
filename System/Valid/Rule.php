<?php

namespace Valid;

trait ArraySetting {
  private ?int $_maxCount = null;
  private ?int $_minCount = null;
  private ?int $_equalCount = null;

  public function setMaxCount(?int $val): self {
    $this->_maxCount = $val;
    return $this;
  }
  public function setMinCount(?int $val): self {
    $this->_minCount = $val;
    return $this;
  }
  public function setEqualCount(?int $val): self {
    $this->_equalCount = $val;
    return $this;
  }
  public function maxCount(?int $val): self {
    return $this->setMaxCount($val);
  }
  public function minCount(?int $val): self {
    return $this->setMinCount($val);
  }
  public function equalCount(?int $val): self {
    return $this->setEqualCount($val);
  }
  public function getMaxCount(): ?int {
    return $this->_maxCount;
  }
  public function getMinCount(): ?int {
    return $this->_minCount;
  }
  public function getEqualCount(): ?int {
    return $this->_equalCount;
  }
}

trait UploadFileSetting {
  private ?int $_maxSize = null;
  private ?int $_minSize = null;
  private ?int $_equalSize = null;

  public function setMaxSize(?int $val): self {
    $this->_maxSize = $val;
    return $this;
  }
  public function setMinSize(?int $val): self {
    $this->_minSize = $val;
    return $this;
  }
  public function setEqualSize(?int $val): self {
    $this->_equalSize = $val;
    return $this;
  }
  public function maxSize(?int $val): self {
    return $this->setMaxSize($val);
  }
  public function minSize(?int $val): self {
    return $this->setMinSize($val);
  }
  public function equalSize(?int $val): self {
    return $this->setEqualSize($val);
  }
  public function getMaxSize(): ?int {
    return $this->_maxSize;
  }
  public function getMinSize(): ?int {
    return $this->_minSize;
  }
  public function getEqualSize(): ?int {
    return $this->_equalSize;
  }
}

trait BoolSetting {
  private ?bool $_equal = null;

  public function setEqual(?bool $equal): self {
    $this->_equal = $equal;
    return $this;
  }
  public function equal(?bool $equal): self {
    return $this->setEqual($equal);
  }
  public function getEqual(): ?bool {
    return $this->_equal;
  }
}

trait IntRangeSetting {
  private ?int $_min = null;
  private ?int $_max = null;
  private ?int $_equal = null;

  public function setMin(?int $val): self {
    $this->_min = $val;
    return $this;
  }
  public function setMax(?int $val) {
    $this->_max = $val;
    return $this;
  }
  public function setEqual(?int $equal): self {
    $this->_equal = $equal;
    return $this;
  }
  public function min(?int $val): self {
    return $this->setMin($val);
  }
  public function max(?int $val): self {
    return $this->setMax($val);
  }
  public function equal(?int $equal): self {
    return $this->setEqual($equal);
  }
  public function setRange($min = null, $max = null) {
    return $this->setMin($min)->setMax($max);
  }
  public function range($min = null, $max = null): self {
    return $this->setRange($min, $max);
  }
  public function getMin(): ?int {
    return $this->_min;
  }
  public function getMax(): ?int {
    return $this->_max;
  }
  public function getEqual(): ?int {
    return $this->_equal;
  }
}

trait StringLengthSetting {
  private ?int $_minLength = null;
  private ?int $_maxLength = null;
  private ?string $_equal = null;

  public function setMinLength(?int $val): self {
    $this->_minLength = $val;
    return $this;
  }
  public function setMaxLength(?int $val) {
    $this->_maxLength = $val;
    return $this;
  }
  public function setEqual(?string $equal): self {
    $this->_equal = $equal;
    return $this;
  }
  public function minLength(?int $val): self {
    return $this->setMinLength($val);
  }
  public function maxLength(?int $val): self {
    return $this->setMaxLength($val);
  }
  public function min(?int $val): self {
    return $this->setMinLength($val);
  }
  public function max(?int $val): self {
    return $this->setMaxLength($val);
  }
  public function equal(?string $equal): self {
    return $this->setEqual($equal);
  }
  public function setLengthRange($min = null, $max = null) {
    return $this->setMinLength($min)->setMaxLength($max);
  }
  public function lengthRange($min = null, $max = null): self {
    return $this->setLengthRange($min, $max);
  }
  public function length($min = null, $max = null): self {
    return $this->setLengthRange($min, $max);
  }
  public function getMinLength(): ?int {
    return $this->_minLength;
  }
  public function getMaxLength(): ?int {
    return $this->_maxLength;
  }
  public function getEqual(): ?string {
    return $this->_equal;
  }
}

trait FloatRangeSetting {
  private ?float $_min = null;
  private ?float $_max = null;
  private ?float $_equal = null;

  public function setMin(?float $val): self {
    $this->_min = $val;
    return $this;
  }
  public function setMax(?float $val) {
    $this->_max = $val;
    return $this;
  }
  public function setEqual(?float $equal): self {
    $this->_equal = $equal;
    return $this;
  }
  public function min(?float $val): self {
    return $this->setMin($val);
  }
  public function max(?float $val): self {
    return $this->setMax($val);
  }
  public function equal(?float $equal): self {
    return $this->setEqual($equal);
  }
  public function setRange($min = null, $max = null) {
    return $this->setMin($min)->setMax($max);
  }
  public function range($min = null, $max = null): self {
    return $this->setRange($min, $max);
  }
  public function getMin(): ?float {
    return $this->_min;
  }
  public function getMax(): ?float {
    return $this->_max;
  }
  public function getEqual(): ?float {
    return $this->_equal;
  }
}

abstract class Rule {
  public static function create(bool $required = true, string $title = '', ?int $code = null) { // php 8.0 以上 return static
    return new static($required, $title, $code);
  }

  private bool $_isRequired;
  private string $_title;
  private ?int $_code = null;
  private ?string $_description = null;
  private bool $_isStrict = true;
  private $_ifNull = null;
  private $_ifNoKey = null;
  private $_data = null;

  private function __construct(bool $required, string $title, ?int $code = null) {
    $this->_isRequired = $required;
    $this->setTitle($title);
    $this->setCode($code);
  }

  public function setTitle(string $title): self {
    $this->_title = $title;
    return $this;
  }
  public function title(string $title): self {
    return $this->setTitle($title);
  }
  public function getTitle(): string {
    return $this->_title;
  }
  public function setDescription(?string $description): self {
    $this->_description = $description;
    return $this;
  }
  public function description(?string $description): self {
    return $this->setDescription($description);
  }
  public function getDescription(): string {
    return $this->_description ?? '';
  }
  public function setIsStrict(bool $isStrict): self {
    $this->_isStrict = $isStrict;
    return $this;
  }
  public function isStrict(bool $isStrict): self {
    return $this->setIsStrict($isStrict);
  }
  public function getIsStrict(): bool {
    return $this->_isStrict;
  }
  public function setCode(?int $code): self {
    $this->_code = $code;
    return $this;
  }
  public function code(?int $code): self {
    return $this->setCode($code);
  }
  public function getCode(): ?int {
    return $this->_code;
  }
  public function setIfNull($val): self {
    $this->_ifNull = $val;
    return $this;
  }
  public function ifNull($val): self {
    return $this->setIfNull($val);
  }
  public function null($val): self {
    return $this->setIfNull($val);
  }
  public function getIfNull() {
    return $this->_ifNull;
  }
  public function setIfNoKey($val): self {
    $this->_ifNoKey = ['val' => $val];
    return $this;
  }
  public function ifNoKey($val): self {
    return $this->setIfNoKey($val);
  }
  public function noKey($val): self {
    return $this->setIfNoKey($val);
  }
  public function getIfNoKey(): ?array {
    return $this->_ifNoKey;
  }
  public function setIfNullOrNoKey($val): self {
    return $this->setIfNull($val)->setIfNoKey($val);
  }
  public function ifNullOrNoKey($val): self {
    return $this->setIfNullOrNoKey($val);
  }
  public function nullOrNoKey($val): self {
    return $this->setIfNullOrNoKey($val);
  }
  public function setData($data): self {
    $this->_data = $data;
    return $this;
  }
  public function data($data): self {
    return $this->setData($data);
  }
  public function getData() {
    return $this->_data;
  }
  public function getReasonTitle(): string {
    $key = $this->getDescription();
    $title = $this->getTitle();
    if ($key === '' && $title === '') {
      return '';
    }
    if ($key !== '' && $title === '') {
      return $key;
    }
    if ($key === '' && $title !== '') {
      return '「' . $title . '」';
    }
    return  $key . '「' . $title . '」';
  }
  public function getIsRequired() {
    return $this->_isRequired;
  }
  public function check($data) {
    $this->setData($data);

    $data = $this->getData();

    if ($this->getIsRequired() && $data === null) {
      throw new \Valid\Exception($this->getReasonTitle() . '不可為 NULL', $this->getCode());
    }

    if (!$this->getIsRequired() && $data === null) {
      return $this->getIfNull();
    }

    return $data;
  }
}
