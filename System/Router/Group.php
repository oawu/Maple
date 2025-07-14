<?php

namespace Router;

use \Router\Helper;

final class Group {
  public static function create(string ...$_paths): self {
    return new self(...$_paths);
  }

  private array $_paths = [];
  private array $_middlewares = [];
  private ?string $_corsOptionsResponse = null;

  private function __construct(string ...$_paths) {
    $this->setPath(...$_paths);
  }

  public function setPath(string ...$paths): self {
    $this->_paths = Helper::paths(...$paths);
    return $this;
  }
  public function getPaths(bool $isOnlyVal = true): array {
    $paths = [];
    foreach ($this->_paths as $path) {
      if ($isOnlyVal) {
        $paths[] = $path['val'];
      } else {
        $paths[] = $path;
      }
    }
    return $paths;
  }
  public function path(string ...$paths): self {
    return $this->setPath(...$paths);
  }
  public function setMiddleware(string ...$middlewares): self {
    $this->_middlewares = Helper::middlewares(...$middlewares);
    return $this;
  }
  public function getMiddlewares(): array {
    return $this->_middlewares;
  }
  public function middleware(string ...$middlewares): self {
    return $this->setMiddleware(...$middlewares);
  }

  public function setCorsOptionsResponse(?string $response): self {
    $this->_corsOptionsResponse = $response;
    return $this;
  }
  public function getCorsOptionsResponse(): ?string {
    return $this->_corsOptionsResponse;
  }
  public function corsOptionsResponse(?string $response): self {
    return $this->setCorsOptionsResponse($response);
  }
  public function corsResponse(?string $response): self {
    return $this->setCorsOptionsResponse($response);
  }
  public function corsOptions(?string $response): self {
    return $this->setCorsOptionsResponse($response);
  }
  public function cors(?string $response): self {
    return $this->setCorsOptionsResponse($response);
  }

  public function routers(callable $func): self {
    $func($this);
    return $this;
  }
}
