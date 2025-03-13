<?php

namespace Error;

final class NotFound extends \Exception {
  public static function throw(string $message = '', ?int $code = null): void {
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
    $exception = new self($message, $code);
    $exception->setFile($backtrace['file'])->setLine($backtrace['line']);
    throw $exception;
  }

  private ?int $_code = null;

  public function __construct(string $message, ?int $code = null) {
    $this->_code = $code;
    parent::__construct(trim($message));
  }
  public function getStatusCode(): int {
    return $this->_code ?? 404;
  }
  public function setFile(string $file): self {
    $this->file = $file;
    return $this;
  }
  public function setLine(int $line): self {
    $this->line = $line;
    return $this;
  }
}
