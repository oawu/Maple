<?php

final class S3Request {
  private $s3 = null;

  private $verb;
  private $bucket;
  private $uri;
  private $resource = '';

  private $parameters = [];
  private $amzHeaders = [];
  private $headers    = ['Host' => '', 'Date' => '', 'Content-MD5' => '', 'Content-Type' => ''];

  public $fp   = null;
  public $data = null;
  public $size = 0;

  public $response = null;

  public function __construct(string $verb, S3 $s3, string $bucket = '', string $uri = '') {
    $this->s3 = $s3;

    $this->verb = strtoupper($verb);
    $this->bucket = strtolower($bucket);

    $this->uri = $uri ? '/' . str_replace('%2F', '/', rawurlencode($uri)) : '/';
    $this->resource = ($this->bucket ? '/' . $this->bucket : '') . $this->uri;
    
    $this->headers['Host'] = ($this->bucket ? $this->bucket . '.' : '') . 's3.amazonaws.com';
    $this->headers['Date'] = gmdate('D, d M Y H:i:s T');

    $this->response = new stdClass();
    $this->response->error = null;
    $this->response->body  = '';
    $this->response->code  = null;
  }

  public function setParameter(string $key, $value): self { $value && $this->parameters[$key] = $value; return $this; }
  public function setHeaders(array $arr): self { foreach ($arr as $key => $value) $this->setHeader($key, $value); return $this; }
  public function setHeader(string $key, $value): self { $value && $this->headers[$key] = $value; return $this; }
  public function setAmzHeaders(array $arr): self { foreach ($arr as $key => $value) $this->setAmzHeader($key, $value); return $this; }
  public function setAmzHeader(string $key, $value): self { $value && $this->amzHeaders[preg_match('/^x-amz-.*$/', $key) ? $key : 'x-amz-meta-' . $key] = $value; return $this; }
  public function setData(string $data): self { $this->data = $data; $this->setSize(strlen($data)); return $this; }
  public function setFile(string $file, string $mode = 'rb', bool $autoSetSize = true): self { $this->fp = @fopen($file, $mode); $autoSetSize && $this->setSize(filesize($file)); return $this; }
  public function setSize(int $size): self { $this->size = $size; return $this; }
  public function getSize(): int { return $this->size; }
  public function getFile() { return $this->fp; }
  public function isSuccessResponse(stdClass &$response = null, array $codes = [200]): bool { $response = $this->getResponse(); return $response->error === null && in_array($response->code, $codes); }
  public function isFailResponse(stdClass &$response = null, array $codes = [200]): bool { return !$this->isSuccessResponse($response, $codes); }
  private function makeAmz(): string { $amz = []; foreach ($this->amzHeaders as $header => $value) $value && array_push($amz, strtolower($header) . ':' . $value); if (!$amz) return ''; sort($amz); return "\n" . implode("\n", $amz); }
  private function makeHeader(): array { $headers = []; foreach ($this->amzHeaders as $header => $value) $value && array_push($headers, $header . ': ' . $value); foreach ($this->headers as $header => $value) $value && array_push($headers, $header . ': ' . $value); array_push($headers, 'Authorization: ' . $this->s3->signature($this->headers['Host'] == 'cloudfront.amazonaws.com' ? $this->headers['Date'] : $this->verb . "\n" . $this->headers['Content-MD5'] . "\n" . $this->headers['Content-Type'] . "\n" . $this->headers['Date'] . $this->makeAmz() . "\n" . $this->resource)); return $headers; }

  public function getResponse(): stdClass {
    $query = '';

    if ($this->parameters) {
      $query = substr($this->uri, -1) !== '?' ? '?' : '&';

      foreach ($this->parameters as $var => $value)
        $query .= ($value == null) || ($value == '') ? $var . '&' : $var . '=' . rawurlencode($value) . '&';

      $this->uri .= $query = substr($query, 0, -1);

      if (isset($this->parameters['acl']) || isset($this->parameters['location']) || isset($this->parameters['torrent']) || isset($this->parameters['logging']))
        $this->resource .= $query;
    }

    $url = ($this->s3->isUseSSL() && extension_loaded('openssl') ? 'https://' : 'http://') . $this->headers['Host'] . $this->uri;

    $curlSetopts = [
      CURLOPT_URL => $url,
      CURLOPT_USERAGENT => 'S3/php',
      CURLOPT_HTTPHEADER => $this->makeHeader(),
      CURLOPT_HEADER => false,
      CURLOPT_RETURNTRANSFER => false,
      CURLOPT_WRITEFUNCTION => [&$this, 'responseWriteCallback'],
      CURLOPT_HEADERFUNCTION => [&$this, 'responseHeaderCallback'],
      CURLOPT_FOLLOWLOCATION => true,
    ];

    $this->s3->isUseSSL() && $curlSetopts[CURLOPT_SSL_VERIFYHOST] = 1;
    $this->s3->isUseSSL() && $curlSetopts[CURLOPT_SSL_VERIFYPEER] = $this->s3->isVerifyPeer() ? 1 : FALSE;

    switch ($this->verb) {
      case 'PUT': case 'POST':
        if ($this->fp !== null) {
          $curlSetopts[CURLOPT_PUT] = true;
          $curlSetopts[CURLOPT_INFILE] = $this->fp;
          $this->size && $curlSetopts[CURLOPT_INFILESIZE] = $this->size;
          break;
        }

        $curlSetopts[CURLOPT_CUSTOMREQUEST] = $this->verb;

        if ($this->data !== null) {
          $curlSetopts[CURLOPT_POSTFIELDS] = $this->data;
          $this->size && $curlSetopts[CURLOPT_BUFFERSIZE] = $this->size;
        }
        break;

      case 'HEAD':
        $curlSetopts[CURLOPT_CUSTOMREQUEST] = 'HEAD';
        $curlSetopts[CURLOPT_NOBODY] = true;
        break;

      case 'DELETE':
        $curlSetopts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        break;

      case 'GET': default: break;
    }
    $curl = curl_init();
    curl_setopt_array($curl, $curlSetopts);
    
    if (curl_exec($curl))
      $this->response->code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    else
      $this->response->error = [
        'code' => curl_errno($curl),
        'message' => curl_error($curl),
        'resource' => $this->resource
      ];

    curl_close($curl);

    if ($this->response->error === null && isset($this->response->headers['type']) && $this->response->headers['type'] == 'application/xml' && isset($this->response->body) && ($this->response->body = simplexml_load_string($this->response->body)))
      if (!in_array($this->response->code, [200, 204]) && isset($this->response->body->Code, $this->response->body->Message))
        $this->response->error = [
          'code' => (string)$this->response->body->Code,
          'message' => (string)$this->response->body->Message,
          'resource' => isset($this->response->body->Resource) ? (string)$this->response->body->Resource : null
        ];

    $this->fp !== null && is_resource($this->fp) && fclose($this->fp);

    return $this->response;
  }

  private function responseWriteCallback(&$curl, &$data) {
    if ($this->response->code == 200 && $this->fp !== null)
      return fwrite($this->fp, $data);

    $this->response->body .= $data;
    return strlen($data);
  }

  private function responseHeaderCallback(&$curl, &$data) {
    if (($strlen = strlen($data)) <= 2)
      return $strlen;
    
    if (substr($data, 0, 4) == 'HTTP') {
      $this->response->code = (int)substr($data, 9, 3);
    } else {
      list($header, $value) = explode(': ', trim($data), 2);

      $header == 'Last-Modified'  && $this->response->headers['time'] = strtotime($value);
      $header == 'Content-Length' && $this->response->headers['size'] = (int)$value;
      $header == 'Content-Type'   && $this->response->headers['type'] = $value;
      $header == 'ETag'           && $this->response->headers['hash'] = $value[0] == '"' ? substr($value, 1, -1) : $value;
      preg_match('/^x-amz-meta-.*$/', $header) && $this->response->headers[$header] = is_numeric($value) ? (int)$value : $value;
    }

    return $strlen;
  }

  public static function create(string $verb, S3 $s3, string $bucket = '', string $uri = ''): self { return new S3Request($verb, $s3, $bucket, $uri); }
}

final class S3 {
  private static $instances = [];
  public static function instance(string $access, string $secret, bool $isUseSSL = false, bool $isVerifyPeer = true): self { return self::$instances[$key = md5($access . $secret . ($isUseSSL ? 1 : 0) . ($isVerifyPeer ? 1 : 0))] ?? self::$instances[$key] = new S3($access, $secret, $isUseSSL, $isVerifyPeer); }

  const ACL_PRIVATE            = 'private';
  const ACL_PUBLIC_READ        = 'public-read';
  const ACL_PUBLIC_READ_WRITE  = 'public-read-write';
  const ACL_AUTHENTICATED_READ = 'authenticated-read';

  private $access       = null;
  private $secret       = null;
  private $isUseSSL     = false;
  private $isVerifyPeer = true;
  private static $extensions = ['jpg' => ['image/jpeg', 'image/pjpeg'], 'gif' => ['image/gif'], 'png' => ['image/png', 'image/x-png'], 'pdf' => ['application/pdf', 'application/x-download'], 'gz' => ['application/x-gzip'], 'zip' => ['application/x-zip', 'application/zip', 'application/x-zip-compressed'], 'swf' => ['application/x-shockwave-flash'], 'tar' => ['application/x-tar'], 'bz' => ['application/x-bzip'], 'bz2' => ['application/x-bzip2'], 'txt' => ['text/plain'], 'html' => ['text/html'], 'htm' => ['text/html'], 'ico' => ['image/x-icon'], 'css' => ['text/css'], 'js' => ['application/x-javascript'], 'xml' => ['text/xml'], 'ogg' => ['application/ogg'], 'wav' => ['audio/x-wav', 'audio/wave', 'audio/wav'], 'avi' => ['video/x-msvideo'], 'mpg' => ['video/mpeg'], 'mov' => ['video/quicktime'], 'mp3' => ['audio/mpeg', 'audio/mpg', 'audio/mpeg3', 'audio/mp3'], 'mpeg' => ['video/mpeg'], 'flv' => ['video/x-flv'], 'php' => ['application/x-httpd-php'], 'bin' => ['application/macbinary'], 'psd' => ['application/x-photoshop'], 'ai' => ['application/postscript'], 'ppt' => ['application/powerpoint', 'application/vnd.ms-powerpoint'], 'wbxml' => ['application/wbxml'], 'tgz' => ['application/x-tar', 'application/x-gzip-compressed'], 'jpeg' => ['image/jpeg', 'image/pjpeg'], 'jpe' => ['image/jpeg', 'image/pjpeg'], 'bmp' => ['image/bmp', 'image/x-windows-bmp'], 'shtml' => ['text/html'], 'text' => ['text/plain'], 'doc' => ['application/msword'], 'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'], 'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'], 'word' => ['application/msword', 'application/octet-stream'], 'json' => ['application/json', 'text/json'], 'svg' => ['image/svg+xml'], 'mp2' => ['audio/mpeg'], 'exe' => ['application/octet-stream', 'application/x-msdownload'], 'tif' => ['image/tiff'], 'tiff' => ['image/tiff'], 'asc' => ['text/plain'], 'xsl' => ['text/xml'], 'hqx' => ['application/mac-binhex40'], 'cpt' => ['application/mac-compactpro'], 'csv' => ['text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel'], 'dms' => ['application/octet-stream'], 'lha' => ['application/octet-stream'], 'lzh' => ['application/octet-stream'], 'class' => ['application/octet-stream'], 'so' => ['application/octet-stream'], 'sea' => ['application/octet-stream'], 'dll' => ['application/octet-stream'], 'oda' => ['application/oda'], 'eps' => ['application/postscript'], 'ps' => ['application/postscript'], 'smi' => ['application/smil'], 'smil' => ['application/smil'], 'mif' => ['application/vnd.mif'], 'xls' => ['application/excel', 'application/vnd.ms-excel', 'application/msexcel'], 'wmlc' => ['application/wmlc'], 'dcr' => ['application/x-director'], 'dir' => ['application/x-director'], 'dxr' => ['application/x-director'], 'dvi' => ['application/x-dvi'], 'gtar' => ['application/x-gtar'], 'php4' => ['application/x-httpd-php'], 'php3' => ['application/x-httpd-php'], 'phtml' => ['application/x-httpd-php'], 'phps' => ['application/x-httpd-php-source'], 'sit' => ['application/x-stuffit'], 'xhtml' => ['application/xhtml+xml'], 'xht' => ['application/xhtml+xml'], 'mid' => ['audio/midi'], 'midi' => ['audio/midi'], 'mpga' => ['audio/mpeg'], 'aif' => ['audio/x-aiff'], 'aiff' => ['audio/x-aiff'], 'aifc' => ['audio/x-aiff'], 'ram' => ['audio/x-pn-realaudio'], 'rm' => ['audio/x-pn-realaudio'], 'rpm' => ['audio/x-pn-realaudio-plugin'], 'ra' => ['audio/x-realaudio'], 'rv' => ['video/vnd.rn-realvideo'], 'log' => ['text/plain', 'text/x-log'], 'rtx' => ['text/richtext'], 'rtf' => ['text/rtf'], 'mpe' => ['video/mpeg'], 'qt' => ['video/quicktime'], 'movie' => ['video/x-sgi-movie'], 'xl' => ['application/excel'], 'eml' => ['message/rfc822']];

  public function __construct(string $access, string $secret, bool $isUseSSL = false, bool $isVerifyPeer = true) {
    $this->access = $access;
    $this->secret = $secret;
    $this->isUseSSL = $isUseSSL;
    $this->isVerifyPeer = $isVerifyPeer;
  }

  private function getHash(string $string): string { return base64_encode(extension_loaded('hash') ? hash_hmac('sha1', $string, $this->secret, true) : pack('H*', sha1((str_pad($this->secret, 64, chr(0x00)) ^ (str_repeat(chr(0x5c), 64))) . pack('H*', sha1((str_pad($this->secret, 64, chr(0x00)) ^ (str_repeat(chr(0x36), 64))) . $string))))); }
  public function signature(string $string): string { return 'AWS ' . $this->access . ':' . $this->getHash($string); }
  public function isUseSSL(): bool { return $this->isUseSSL; }
  public function isVerifyPeer(): bool { return $this->isVerifyPeer; }
  public static function fileMD5(string $file): string { return base64_encode(md5_file($file, true)); }
  private static function getMimeByExtension(string $file): string {
  return (self::$extensions[strtolower(substr(strrchr($file, '.'), 1))] ?? ['text/plain'])[0]; /* 'application/octet-stream'; */ }

  public function test(stdClass &$response = null): bool { return S3Request::create('GET', $this)->isSuccessResponse($response); }
  public function buckets(): ?array { if (!$this->test($response)) return null; $buckets = []; if (!isset($response->body->Buckets)) return $buckets; foreach ($response->body->Buckets->Bucket as $bucket) array_push($buckets, (string)$bucket->Name); return $buckets; }
  public function bucketsWithDetail(): ?array { if (!$this->test($response)) return null; $results = []; if (!isset($response->body->Buckets)) return $results; isset($response->body->Owner, $response->body->Owner->ID, $response->body->Owner->DisplayName) && $results['owner'] = ['id' => (string)$response->body->Owner->ID, 'name' => (string)$response->body->Owner->ID]; $results['buckets'] = []; foreach ($response->body->Buckets->Bucket as $bucket) array_push($results['buckets'], ['name' => (string)$bucket->Name, 'time' => date('Y-m-d H:i:s', strtotime((string)$bucket->CreationDate))]); return $results; }
  public function deleteBucket(string $bucket): bool { return S3Request::create('DELETE', $this, $bucket)->isSuccessResponse($response, [200, 204]); }
  public function copyObject(string $srcBucket, string $srcURI, string $bucket, string $uri, string $acl = self::ACL_PUBLIC_READ, array $amzHeaders = [], array $headers = []): bool { return S3Request::create('PUT', $this, $bucket, $uri)->setHeaders($headers = array_merge(['Content-Length' => 0], $headers))->setAmzHeaders($amzHeaders = array_merge(['x-amz-acl' => $acl, 'x-amz-copy-source' => sprintf('/%s/%s', $srcBucket, $srcURI)], $amzHeaders))->setAmzHeader('x-amz-metadata-directive', $headers || $amzHeaders ? 'REPLACE' : null)->isSuccessResponse($response); }
  public function getObject(string $bucket, string $uri, ?string $file = null): bool { $request = S3Request::create('GET', $this, $bucket, $uri); return $request->isSuccessResponse() ? !$file || ($request->setFile($file, 'wb', false)->getFile() !== null && ($request->file = realpath($file))) : false; }
  public function getObjectInfo(string $bucket, string $uri): ?array { return S3Request::create('HEAD', $this, $bucket, $uri)->isSuccessResponse($response, [200, 404]) ? $response->code == 200 ? $response->headers : null : null; }
  public function deleteObject($bucket, $uri): bool { return S3Request::create('DELETE', $this, $bucket, $uri)->isSuccessResponse($response, [200, 204]); }


  public function bucket(string $bucket, string $prefix = null, string $marker = null, ?array $maxKeys = null, string $delimiter = null, bool $returnCommonPrefixes = false): ?array {
    if (!S3Request::create('GET', $this, $bucket)->setParameter('prefix', $prefix)->setParameter('marker', $marker)->setParameter('max-keys', $maxKeys)->setParameter('delimiter', $delimiter)->isSuccessResponse($response))
      return null;

    $nextMarker = null;
    $results = [];

    if (isset($response->body, $response->body->Contents))
      foreach ($response->body->Contents as $content)
        $results[$nextMarker = (string)$content->Key] = ['name' => (string)$content->Key, 'time' => date('Y-m-d H:i:s', strtotime((string)$content->LastModified)), 'size' => (int)$content->Size, 'hash' => substr((string)$content->ETag, 1, -1)];

    if ($returnCommonPrefixes && isset($response->body, $response->body->CommonPrefixes))
      foreach ($response->body->CommonPrefixes as $content)
        $results[(string)$content->Prefix] = ['prefix' => (string)$content->Prefix];

    if (isset($response->body, $response->body->IsTruncated) && (((string)$response->body->IsTruncated) == 'false'))
      return $results;

    if (isset($response->body, $response->body->NextMarker))
      $nextMarker = (string)$response->body->NextMarker;

    if ($maxKeys || !$nextMarker || (((string)$response->body->IsTruncated) != 'true'))
      return $results;

    do {
      if (!S3Request::create('GET', $this, $bucket)->setParameter('marker', $nextMarker)->setParameter('prefix', $prefix)->setParameter('delimiter', $delimiter)->isSuccessResponse($response))
        break;

      if (isset($response->body, $response->body->Contents))
        foreach ($response->body->Contents as $content)
          $results[$nextMarker = (string)$content->Key] = ['name' => (string)$content->Key, 'time' => date('Y-m-d H:i:s', strtotime((string)$content->LastModified)), 'size' => (int)$content->Size, 'hash' => substr((string)$content->ETag, 1, -1)];

      if ($returnCommonPrefixes && isset($response->body, $response->body->CommonPrefixes))
        foreach ($response->body->CommonPrefixes as $content)
          $results[(string)$content->Prefix] = ['prefix' => (string)$content->Prefix];

      if (isset($response->body, $response->body->NextMarker))
        $nextMarker = (string)$response->body->NextMarker;

    } while (($response !== false) && (((string)$response->body->IsTruncated) == 'true'));

    return $results;
  }

  public function createBucket(string $bucket, ?string $location = null, string $acl = self::ACL_PRIVATE): bool {
    // location valid values: af-south-1 | ap-east-1 | ap-northeast-1 | ap-northeast-2 | ap-northeast-3 | ap-south-1 | ap-southeast-1 | ap-southeast-2 | ca-central-1 | cn-north-1 | cn-northwest-1 | EU | eu-central-1 | eu-north-1 | eu-south-1 | eu-west-1 | eu-west-2 | eu-west-3 | me-south-1 | sa-east-1 | us-east-2 | us-gov-east-1 | us-gov-west-1 | us-west-1 | us-west-2
    $request = S3Request::create('PUT', $this, $bucket)->setAmzHeader('x-amz-acl', $acl);

    if ($location) {
      $dom = new \DOMDocument();
      $configuration = $dom->createElement('CreateBucketConfiguration');
      $configuration->appendChild($dom->createElement('LocationConstraint', $location));
      $dom->appendChild($configuration);
      $request->setHeader('Content-Type', 'application/xml')->setData($dom->saveXML());
    }

    return $request->isSuccessResponse();
  }


  /* $headers => "Cache-Control" => "max-age=5", setcache 5 sec */
  public function putObject(string $file, string $bucket, string $s3Path, string $acl = self::ACL_PUBLIC_READ, array $amzHeaders = [], array $headers = []): bool {
    if (!(is_file($file) && is_readable($file)))
      return false;

    $request = S3Request::create('PUT', $this, $bucket, $s3Path)
                        ->setHeaders(array_merge(['Content-Type' => self::getMimeByExtension($file), 'Content-MD5' => self::fileMD5($file)], $headers))
                        ->setAmzHeaders(array_merge(['x-amz-acl' => $acl], $amzHeaders))
                        ->setFile($file);

    if ($request->getSize() < 0 || $request->getFile() === null)
      return false;

    return $request->isSuccessResponse($response);
  }
}
