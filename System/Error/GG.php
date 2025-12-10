<?php

namespace Error;

final class GG extends \Exception {
  public static function throw(array $messages, ?int $code = null): void {
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
    $exception = new self($messages, $code);
    $exception->setFile($backtrace['file'])->setLine($backtrace['line']);
    throw $exception;
  }

  private ?int $_code = null;
  private array $_messages = [];

  public function __construct(array $messages, ?int $code = null) {
    $this->_code = $code;
    $this->_messages = array_filter(array_map('trim', $messages), fn($m) => $m !== '');
    parent::__construct(implode('、', $this->_messages));
  }

  public function getStatusCode(): int {
    return $this->_code ?? 400;
  }
  public function getMessages(): array {
    return $this->_messages;
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
