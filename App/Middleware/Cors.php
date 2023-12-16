<?php

namespace Middleware;

use Response;
use Request;

class Cors {
  private $origins = ['http://127.0.0.1:8000', 'https://test.ioa.tw'];
  private $methods = ['POST', 'PUT', 'DELETE', 'OPTIONS'];
  private $headers = ['Content-Type', 'Authorization', 'X-Requested-With', 'authorization'];

  public function index() {
    
    ifApiError(function() {
      return ['messages' => func_get_args()];
    });
    Response::addHeader("Access-Control-Allow-Headers: " . implode(', ', $this->headers));
    Response::addHeader("Access-Control-Allow-Methods: " . implode(', ', $this->methods));

    $origin = Request::headers('origin');
    $this->origins
      && in_array($origin, $this->origins)
      && Response::addHeader("Access-Control-Allow-Origin: " . $origin);

    \Load::systemLib('Valid');
    \Valid::error(function($message, $code) {
      error($code, $message . '！');
    });

  }
}
