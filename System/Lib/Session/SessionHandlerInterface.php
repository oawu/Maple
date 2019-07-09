<?php

if (!interface_exists('SessionHandlerInterface', false)) {
  interface SessionHandlerInterface {
    public function open($savePath, $name);
    public function close();
    public function read($sessionId);
    public function write($sessionId, $sessionData);
    public function destroy($sessionId);
    public function gc($maxlifetime);
  }
}
