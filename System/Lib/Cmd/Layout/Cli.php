<?php

namespace CMD\Layout;

class Cli {
  protected static function fail() {
    echo json_encode([
      'status' => false,
      'errors' => \arrayFlatten(func_get_args())
    ]);
    exit(1);
  }

  protected static function success() {
    echo json_encode([
      'status' => true,
      'messages' => \arrayFlatten(func_get_args())
    ]);
    exit(0);
  }
}