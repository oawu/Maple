<?php

use \CRUD\Table       as Table;
use \CRUD\Table\Order as Order;
use \CRUD\Form        as Form;
use \CRUD\Show        as Show;

class Admin extends AdminController {
  private $ignoreIds;
  
  public function __construct() {
    parent::__construct(\M\AdminRole::ROLE_ADMIN, \M\AdminRole::ROLE_ROOT);

    ifErrorTo('AdminAdminIndex');

    $this->ignoreIds = [1];

    $this->methedIn('edit', 'update', 'delete', 'show', function() {
      return $this->obj = \M\Admin::one('id = ? AND id NOT IN (?)', Router::param('id'), $this->ignoreIds);
    });

    $this->view->with('title', '管理員帳號')
               ->with('currentUrl', Url::router('AdminAdminIndex'));
  }

  public function index() {
    $table = Table::create('\M\Admin', ['include' => ['roles', 'logs'], 'where' => Where::create('id NOT IN(?)', $this->ignoreIds)])
                  ->setAddUrl(Url::router('AdminAdminAdd'));

    return $this->view->with('table', $table);
  }
  
  public function add() {
    $form = Form::create()
                ->setActionUrl(Url::router('AdminAdminCreate'))
                ->setBackUrl(Url::router('AdminAdminIndex'));

    return $this->view->with('form', $form);
  }
  
  public function create() {
    ifErrorTo('AdminAdminAdd');

    $params = Input::ValidPost(function($params) {
      Validator::must($params, 'name', '名稱')->isString(1, 190);
      Validator::must($params, 'account', '帳號')->isString(1, 190);
      Validator::must($params, 'password', '密碼')->isString(1, 190);
      Validator::optional($params, 'roles', '角色')->default([])->filter(array_keys(\M\AdminRole::ROLE));

      \M\Admin::one('account = ?', $params['account']) && error('帳號已重複！');
      $params['password'] = password_hash($params['password'], PASSWORD_DEFAULT);

      return $params;
    });

    $files = Input::ValidFile(function($files) {
      Validator::optional($files, 'avatar', '頭像')->isUpload()->formatFilter(['jpg', 'png', 'jpeg']);
      return $files;
    });

    transaction(function() use (&$params, &$files) {
      if (!$obj = \M\Admin::create($params))
        return false;
      
      if (!$obj->putFiles($files))
        return false;

      foreach ($params['roles'] as $role)
        if (!\M\AdminRole::create(['adminId' => $obj->id, 'role' => $role]))
          return false;

      return true;
    });

    return Url::refreshWithSuccessFlash(Url::router('AdminAdminIndex'), '新增成功！');
  }
  
  public function edit() {
    $form = Form::create($this->obj)
                ->setActionUrl(Url::router('AdminAdminUpdate', $this->obj))
                ->setBackUrl(Url::router('AdminAdminIndex'));
    
    return $this->view->with('form', $form);
  }
  
  public function update() {
    ifErrorTo('AdminAdminEdit', $this->obj);

    $params = Input::ValidPost(function($params) {
      Validator::must($params, 'name', '名稱')->isString(1, 190);
      Validator::must($params, 'account', '帳號')->isString(1, 190);
      Validator::optional($params, 'password', '密碼')->default(null)->isString(0, 190);
      Validator::optional($params, 'roles', '角色')->default([])->filter(array_keys(\M\AdminRole::ROLE));

      if ($params['password']) $params['password'] = password_hash($params['password'], PASSWORD_DEFAULT);
      else unset($params['password']);

      return $params;
    });

    $files = Input::ValidFile(function($files) {
      Validator::optional($files, 'avatar', '頭像')->isUpload()->formatFilter(['jpg', 'png', 'jpeg']);
      return $files;
    });

    transaction(function() use (&$params, &$files) {
      if (!($this->obj->setColumns($params) && $this->obj->save() && $this->obj->putFiles($files)))
        return false;

      $oris = array_column($this->obj->roles, 'role');
      $dels = array_diff($oris, $params['roles']);
      $adds = array_diff($params['roles'], $oris);

      foreach ($dels as $del)
        if ($role = \M\AdminRole::one('adminId = ? AND role = ?', $this->obj->id, $del))
          if (!$role->delete())
            return false;

      foreach ($adds as $add)
        if (!\M\AdminRole::create(['adminId' => $this->obj->id, 'role' => $add]))
          return false;
      
      return true;
    });
    
    return Url::refreshWithSuccessFlash(Url::router('AdminAdminIndex'), '修改成功！');
  }
  
  public function show() {
    $show = Show::create($this->obj)
                ->setBackUrl(Url::router('AdminAdminIndex'), '回列表');

    $table = Table::create('\M\AdminLog', ['where' => ['adminId = ?', $this->obj->id]]);

    return $this->view->with('show', $show)
                      ->with('table', $table);
  }
  
  public function delete() {
    ifErrorTo('AdminAdminIndex');
    
    transaction(function() {
      return $this->obj->delete();
    });

    return Url::refreshWithSuccessFlash(Url::router('AdminAdminIndex'), '刪除成功！');
  }
}
