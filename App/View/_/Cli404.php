<?php

$messages = implode("\n " . \Xterm::black(str_repeat('─', 78), true)->dim() . " \n", array_map(function($message) { return '  ' . str_replace("\n", "\n  ", dump($message)); }, $messages));
$details  = implode("\n", array_map(function($detail) { return '  ' . \Xterm::green('◉') . ' ' . $detail['title'] . \Xterm::create('：')->dim() . \Xterm::gray($detail['content'], true)->blod(); }, $details));
$traces   = implode("\n", array_map(function($trace) { return '  ' . \Xterm::purple('◉') . ' ' . $trace['info'] . "\n" . '    ' . \Xterm::purple('↳')->dim() . ' ' . \Xterm::gray($trace['path'])->dim()->italic(); }, $traces));

if (!($messages || $details || $traces))
  echo "\n" . Xterm::red("【錯誤】") . "\n";

if ($messages)
  echo "\n" . Xterm::yellow("【錯誤訊息】") . "\n" . $messages . "\n";

if ($details)
  echo "\n" . Xterm::yellow("【錯誤原因】") . "\n" . $details . "\n";

if ($traces)
  echo "\n" . Xterm::yellow("【錯誤追蹤】") . "\n" . $traces . "\n";
