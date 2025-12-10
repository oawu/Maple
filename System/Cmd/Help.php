<?php

namespace Cmd;

final class Help {
  public static function create(?string $feature = null): Result {
    switch ($feature) {
      case 'Init':
        return Result::help(Template::read('Help/Init'));
      case 'Create':
        return Result::help(Template::read('Help/Create'));
      case 'CreateModel':
        return Result::help(Template::read('Help/CreateModel'));
      case 'CreateMigration':
        return Result::help(Template::read('Help/CreateMigration'));
      case 'Migration':
        return Result::help(Template::read('Help/Migration'));
      case 'Clean':
        return Result::help(Template::read('Help/Clean'));
    }

    return Result::help(Template::read('Help'));
  }
}
