<?php

namespace Valid;

final class Exception extends \Exception {
  private ?int $_errorCode = null;
  private string $_reason = '';

  public function __construct(string $reason, ?int $errorCode = null) {
    parent::__construct($reason);
    $this->_errorCode = $errorCode;
    $this->_reason = $reason;
  }

  public function getErrorCode(): ?int {
    return $this->_errorCode;
  }
  public function getReason(): string {
    return $this->_reason;
  }
}
