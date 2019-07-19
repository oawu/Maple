<?php

namespace CMD\Layout;

class Gui {
  public static function get($feature = null) {
    $main = \CMD\Layout\Menu::create('主選單', 'Main menu')
                            ->appendItem($init      = \CMD\Init\Layout::get())
                            ->appendItem($create    = \CMD\Create\Layout::get())
                            ->appendItem($migration = \CMD\Migration\Layout::get())
                            ->appendItem($clean     = \CMD\Clean\Layout::get())
                            ->appendItem($deploy    = \CMD\Deploy\Layout::get())
                            ->appendItem($apiDoc    = \CMD\ApiDoc\Layout::get())
                            ->appendItem($update    = \CMD\Update\Layout::get());

    switch ($feature) {
      case 'init':      return $init->choice();      break;
      default:          return $main->choice();      break;
      case 'create':    return $create    ? $create->choice() : $main->choice();    break;
      case 'migration': return $migration ? $migration->choice() : $main->choice(); break;
      case 'clean':     return $clean     ? $clean->choice() : $main->choice();     break;
      case 'deploy':    return $deploy    ? $deploy->choice() : $main->choice();    break;
    }
  }
}