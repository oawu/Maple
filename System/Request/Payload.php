<?php

namespace Request;

use \Request;

abstract class Payload {
  private static ?array $_body = null;
  private static ?array $_text = null;
  private static ?array $_json = null;
  private static ?array $_data = null;
  private static ?array $_file = null;
  private static ?array $_parse = null;

  public static function getBody(): string {
    if (self::$_body !== null) {
      return self::$_body['val'];
    }

    $body = '';
    $original = fopen('php://input', 'r');
    while ($chunk = fread($original, 1024)) {
      $body .= $chunk;
    }

    fclose($original);
    $body = ['val' => $body];
    self::$_body = $body;
    return $body['val'];
  }
  public static function getText(): string {
    if (self::$_text !== null) {
      return self::$_text['val'];
    }

    $text = ['val' => self::getBody()];
    self::$_text = $text;
    return $text['val'];
  }
  public static function getJson() {
    if (self::$_json !== null) {
      return self::$_json['val'];
    }

    $val = self::getText();
    $json = ['val' => self::_isJson($val) ? $val : null];
    self::$_json = $json;
    return $json['val'];
  }
  public static function getData(): array {
    if (self::$_data !== null) {
      return self::$_data['val'];
    }

    $server = Request::getServers();
    $method = strtoupper(trim($server['REQUEST_METHOD'] ?? ''));

    if ($method === 'POST') {
      $data = ['val' => isset($_POST) && is_array($_POST) ? $_POST : []];
      self::$_data = $data;
      return $data['val'];
    }

    $data = self::_parse();

    $data = ['val' => isset($data['form']) && is_array($data['form']) ? $data['form'] : []];
    self::$_data = $data;
    return $data['val'];
  }
  public static function getFiles(): array {
    if (self::$_file !== null) {
      return self::$_file['val'];
    }

    $server = Request::getServers();
    $method = strtoupper(trim($server['REQUEST_METHOD'] ?? ''));

    if ($method !== 'POST') {
      $data = self::_parse();
      $file = ['val' => isset($data['file']) && is_array($data['file']) ? $data['file'] : []];
      self::$_file = $file;
      return $file['val'];
    }

    $files = $_FILES;

    $news = [];
    foreach ($files as $key1 => $file) {
      foreach ($file as $type => $val1) {
        $tmps = self::_cover2($val1, $key1);
        foreach ($tmps as $key2 => $val2) {
          if (!array_key_exists($key2, $news)) {
            $news[$key2] = [];
          }
          $news[$key2][$type] = $val2;
        }
      }
    }

    $files = [];
    foreach ($news as $key => $new) {
      $files[] = urlencode($key) . '=' . urlencode(json_encode($new));
    }

    parse_str(implode('&', $files), $files);
    $files = self::_cover1($files);
    $file = ['val' => is_array($files) ? $files : []];
    self::$_file = $file;
    return $file['val'];
  }
  public static function body(): string {
    return self::getBody();
  }
  public static function text(): string {
    return self::getText();
  }
  public static function json() {
    return self::getJson();
  }
  public static function data(): array {
    return self::getData();
  }
  public static function files(): array {
    return self::getFiles();
  }

  private static function _parse(): array {
    if (self::$_parse !== null) {
      return self::$_parse['val'];
    }

    $payload = self::getBody();

    $headers = Request::getHeaders();
    $type = $headers['Content-Type'] ?? '';

    $result = preg_match('/^\s*(?P<type>application\/x-www-form-urlencoded)(\s*;\s*)?(?P<other>.*)?\s*$/i', $type, $matches);
    if ($result && $matches && $matches['type']) {
      parse_str($payload, $data);
      $parse = ['val' => ['form' => is_array($data) ? $data : [], 'file' => []]];
      self::$_parse = $parse;
      return $parse['val'];
    }

    $result = preg_match('/^\s*(?P<type>multipart\/form-data)(\s*;\s*)?boundary=(?P<boundary>.*)?\s*$/i', $type, $matches);
    if (!($result && $matches && $matches['type'] && $matches['boundary'])) {
      $parse = ['val' => ['form' => [], 'file' => []]];
      self::$_parse = $parse;
      return $parse['val'];
    }

    $boundary = '--' . $matches['boundary'];

    $forms = [];
    $files = [];

    $parts = explode($boundary, $payload);
    foreach ($parts as $part) {
      $part = trim($part);

      if (empty($part)) {
        continue;
      }

      if (preg_match('/\s*Content-Disposition\s*:\s*form-data;\s*name\s*=\s*"(?P<key>[^"]+)";\s*filename\s*=\s*"(?P<name>[^"]+)"\s*/i', $part, $matches) && $matches && $matches['key'] && $matches['name']) {
        $key = $matches['key'];
        $name = $matches['name'];
        $type = preg_match('/\s*Content-Type\s*:\s*(?P<type>[^\r\n]+)/', $part, $matches) && $matches && $matches['type'] ? $matches['type'] : 'application/octet-stream';

        $index = strpos($part, "\r\n\r\n");
        if ($index === false) {
          continue;
        }

        $data = trim(substr($part, $index + 4));
        $tmpName = tempnam(ini_get('upload_tmp_dir'), 'Maple_');
        $bytes = @file_put_contents($tmpName, $data);

        if ($bytes === false) {
          continue;
        }

        array_push($files, urlencode($key) . '=' . urlencode(json_encode([
          'name' => $name,
          'type' => $type,
          'tmp_name' => $tmpName,
          'error' => 0,
          'size' => strlen($data)
        ])));
        continue;
      }

      if (preg_match('/\s*Content-Disposition\s*:\s*form-data;\s*name\s*=\s*"(?P<key>[^"]+)"\s*/i', $part, $matches) && $matches && $matches['key']) {
        $index = strpos($part, "\r\n\r\n");
        array_push($forms, urlencode($matches['key']) . '=' . urlencode($index === false ? '' : trim(substr($part, $index + 4))));
        continue;
      }
    }
    parse_str(implode('&', $files), $files);
    $files = self::_cover1($files);

    parse_str(implode('&', $forms), $forms);

    $parse = ['val' => [
      'form' => is_array($forms) ? $forms : [],
      'file' => is_array($files) ? $files : []
    ]];
    self::$_parse = $parse;
    return $parse['val'];
  }
  private static function _cover1(array $files): array {
    $new = [];
    foreach ($files as $key => $file) {
      $new[$key] = is_array($file)
        ? self::_cover1($file)
        : json_decode($file, true);
    }
    return $new;
  }
  private static function _cover2($array, string $prefix = ''): array {
    $result = [];
    if (is_array($array)) {
      foreach ($array as $key => $value) {
        $result = $result + self::_cover2($value, $prefix . '[' . $key . ']');
      }
    } else {
      $result[$prefix] = $array;
    }

    return $result;
  }
  private static function _isJson(string &$str, bool $asArray = true): bool {
    if (!is_string($str)) {
      return false;
    }

    $decoded = json_decode($str, $asArray);

    if (json_last_error() === JSON_ERROR_NONE) {
      $str = $decoded;
      return true;
    }
    return false;
  }
}
