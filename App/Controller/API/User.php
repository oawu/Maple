<?php

class User extends Controller {
  
  public function create() {
    list(
      'me' => $me
    ) = Valid::check(Request::rawJson(), [
      'me' => Valid::id('Me'),
    ]);

    $me = \M\User::one($me);
    $me ?? error('Error 1');

    \M\User::where('owner', '=', $me->name)->one() && error('你已經挑選過囉！');

    $users = \M\User::where('owner', '=', '')->where('id', '!=', $me->id)->all();
    $users || error('發生錯誤了，請大家重新抽先吧！');

    shuffle($users);

    $to = array_shift($users);

    $to->owner = $me->name;
    $to->save();

    return [
      'id' => $to->id,
      'name' => $to->name,
      'tip' => $to->tip,
      'address' => $to->address,
    ];
  }

  public function index() {
    return array_map(function($user) {
      return [
        'id' => $user->id,
        'name' => $user->name,
      ];
    }, \M\User::where('owner', '=', '')->all());
  }
}
