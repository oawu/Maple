<?php

Router::get('')->controller('Main@index');
Router::post('')->controller('Main@index');
Router::cli('')->controller('Main@index');

Router::group([
  'uri' => 'api',
  'dir' => 'API',
  'mid' => 'Cors'
], function() {
  Router::options('.*')->return('ok')->title('CORS OPTIONS');
  Router::get('users')->controller('User@index');
  Router::post('user')->controller('User@create');
});
