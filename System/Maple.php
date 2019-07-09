<?php

/* --------------------------------------------------
 *  定義環境常數
 * -------------------------------------------------- */

Load::system('Env') ?: gg('載入 System/Env 失敗！');



/* --------------------------------------------------
 *  載入核心 2
 * -------------------------------------------------- */

Load::systemCore('Url.php')        ?: gg('載入 System/Url 失敗！');
Load::systemCore('Controller.php') ?: gg('載入 System/Controller 失敗！');
Load::systemCore('Router')         ?: gg('載入 System/Router 失敗！');
Load::systemCore('Output')         ?: gg('載入 System/Output 失敗！');
Load::systemCore('Security')       ?: gg('載入 System/Security 失敗！');
Load::systemCore('Input')          ?: gg('載入 System/Input 失敗！');



/* --------------------------------------------------
 *  載入 Autoload
 * -------------------------------------------------- */

foreach (config('Autoload') as $method => $files)
  if (is_callable(['Load', $method]))
    foreach ($files as $file)
      Load::$method($file) ?: gg('Autoload 載入 ' . $method . '("' . $file . '") 失敗！');



/* --------------------------------------------------
 *  開始
 * -------------------------------------------------- */

Benchmark::start('整體');

Output::router(Router::current());

// 執行結束的一些瑣事，ex: 結束 DB 的 connection、Log fclose 
Status::endFuncs();

/* --------------------------------------------------
 *  結束
 * -------------------------------------------------- */

exit(0);