<?php

use \Orm\Model;
use \Orm\Core\Thumbnail\Gd;
use \Orm\Core\Thumbnail\Imagick;
use \Orm\Core\Plugin\Uploader;
use \Orm\Core\Plugin\Uploader\File;
use \Orm\Core\Plugin\Uploader\Image;

$configs = Config::get('MySql');

foreach ($configs as $key => $config) {
  Model::setConfig($key, Orm\Core\Config::create()
    ->setHostname($config['host'])
    ->setUsername($config['username'])
    ->setPassword($config['password'])
    ->setDatabase($config['database']));
}

Model::setNamespace('App\Model');

Model::setCaseTable(Model::CASE_CAMEL);
Model::setCaseColumn(Model::CASE_CAMEL);


Model::setErrorFunc(fn(...$args) => error('Model 錯誤，' . implode(', ', $args)));
Model::setQueryLogFunc(fn(string $db, string $sql, array $vals, bool $status, float $during) => Log::query($db, $sql, $vals, $status, $during));
Model::setLogFunc(fn(string $message) => Log::info($message));

if (ENVIRONMENT == 'Production') {
  Model::setCacheFunc('MetaData', fn(string $key, callable $closure) => Cache::file(md5('_:DB:MetaData:' . $key), 86400, $closure));
}

Model::setHashids(8, KEY, 'abcdefghijklmnopqrstuvwxyz1234567890');

$configModel = Config::get('Model') ?? [];

if (($configModel['thumbnail'] ?? 'Gd') == 'Imagick') {
  Model::setImageThumbnail(fn($file) => Imagick::create($file));
} else {
  Model::setImageThumbnail(fn($file) => Gd::create($file));
}

Model::setUploader(static function (Uploader $uploader) use ($configModel): void {
  $_config = $configModel['uploader'] ?? [];

  if ($uploader instanceof File) {
    $config = $configModel['uploader']['file'] ?? [];

    $driver     = $config['driver']     ?? $_config['driver']     ?? ['type' => 'Local', 'params' => ['storage' => PATH_PUBLIC]];
    $namingSort = $config['namingSort'] ?? $_config['namingSort'] ?? ['origin', 'md5', 'random'];
    $tmpDir     = $config['tmpDir']     ?? $_config['tmpDir']     ?? (rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
    $baseDirs   = $config['baseDirs']   ?? $_config['baseDirs']   ?? ['Storage'];
    $baseUrl    = $config['baseUrl']    ?? $_config['baseUrl']    ?? 'http://127.0.0.1/';
    $defaultUrl = $config['defaultUrl'] ?? $_config['defaultUrl'] ?? 'http://127.0.0.1/404.png';

    $uploader->setDriver($driver['type'], $driver['params']);
    $uploader->setNamingSort(...$namingSort);
    $uploader->setTmpDir($tmpDir);
    $uploader->setBaseDir(...$baseDirs);
    $uploader->setBaseUrl($baseUrl);
    $uploader->setDefaultUrl($defaultUrl);
  }
  if ($uploader instanceof Image) {
    $config = $configModel['uploader']['image'] ?? [];

    $driver     = $config['driver']     ?? $_config['driver']     ?? ['type' => 'Local', 'params' => ['storage' => PATH_PUBLIC]];
    $namingSort = $config['namingSort'] ?? $_config['namingSort'] ?? ['md5', 'random', 'origin'];
    $tmpDir     = $config['tmpDir']     ?? $_config['tmpDir']     ?? (rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
    $baseDirs   = $config['baseDirs']   ?? $_config['baseDirs']   ?? ['Storage'];
    $baseUrl    = $config['baseUrl']    ?? $_config['baseUrl']    ?? 'http://127.0.0.1/';
    $defaultUrl = $config['defaultUrl'] ?? $_config['defaultUrl'] ?? 'http://127.0.0.1/404.png';
    $versions   = $config['versions']   ?? [];

    $uploader->setDriver($driver['type'], $driver['params']);
    $uploader->setNamingSort(...$namingSort);
    $uploader->setTmpDir($tmpDir);
    $uploader->setBaseDir(...$baseDirs);
    $uploader->setBaseUrl($baseUrl);
    $uploader->setDefaultUrl($defaultUrl);

    foreach ($versions as $version => $config) {
      $uploader->addVersion($version)->setMethod($config['method'])->setArgs(...$config['params']);
    }
  }
});

register_shutdown_function(fn() => Model::close());
