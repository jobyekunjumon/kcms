<?php
class  Backoffice_UsersController extends Zend_Controller_Action {
  public function init() {
    /* Initialize action controller here */
    $layout = $this->_helper->layout();
    $layout->setLayout('layout_alte');
    $auth = Zend_Auth::getInstance();
    if ($auth->hasIdentity() && $auth->getIdentity()->utype == 'super_admin') {
      $this->user = (array) $auth->getIdentity();
      if(!isset($this->user['id_admin_user'])) $this->_helper->redirector('index', 'auth');
      unset($this->user['salt']);
      unset($this->user['password']);
      $this->view->user = $this->user;
    } else {
      $this->_helper->redirector('index', 'auth');
    }

    $this->utilities = new Application_Model_Utilities();
    $this->modelLog = new Application_Model_DbTable_Activitylog();
  }

public function indexAction() {

  $this->view->activeMenuItem = 'users';
  $this->view->pageHeading = 'All Users';
  $breadcrumbs[] = array('link' => 'index', 'label' => 'Dashboard', 'icon' => 'fa fa-dashboard');
  $breadcrumbs[] = array('link'=>'','label'=>'Users','icon'=>'');
  $this->view->breadcrumbs = $breadcrumbs;


  $modelUser = new Application_Model_DbTable_Adminusers();
  // process query
  $request = $this->getRequest();
  $get = $request->getQuery();

  $sqlUsers = "SELECT * FROM `admin_users` WHERE `id_admin_user` != 1 ";

  // pagination
  $pageLimit = 20;
  $start = 0;
  if (isset($get['page']))
  $page = intval($get['page']);
  $noRecords = count($modelUser->getAll('', $sqlUsers, ''));
  $tpages = ceil($noRecords / $pageLimit);
  if (!isset($page) || $page <= 0)
  $page = 1;
  $reload = $this->view->baseUrl() . '/backoffice/users/index?';

  $reload .= $this->utilities->getUrlParams($get, array('page', 'del', 'chs'));
  $pagination = $this->utilities->paginate_two($reload, $page, $tpages, 4);
  $start = ($page - 1) * $pageLimit;

  $sqlUsers .= ' LIMIT ' . $start . ' , ' . $pageLimit;

  // get users
  $users = $modelUser->getAll('', $sqlUsers, '');
  $urlParamStr = $this->utilities->getUrlParams($get, array('del', 'chs'));

  if(isset($message) && $message) $this->view->message = $message;
  if(isset($pagination) && $pagination) $this->view->pagination = $pagination;
  if(isset($users) && $users) $this->view->users = $users;

}

public function adduserAction() {
  $this->view->activeMenuItem = 'users';
  $this->view->pageHeading = 'Users';
  $breadcrumbs[] = array('link' => 'index', 'label' => 'Dashboard', 'icon' => 'fa fa-dashboard');
  $breadcrumbs[] = array('link'=>'users/index','label'=>'All Users','icon'=>'fa fa-user');
  $breadcrumbs[] = array('link'=>'','label'=>'Users','icon'=>'');
  $this->view->breadcrumbs = $breadcrumbs;


  $modelUser = new Application_Model_DbTable_Adminusers();
  // process query
  $request = $this->getRequest();
  $get = $request->getQuery();

  if($request->isPost()) {
    $post = $request->getPost();
    $frmData = $post;

    if(isset($post['id_to_edit']) && $post['id_to_edit']) {
      $idToEdit = $post['id_to_edit'];
    }
    else $idToEdit = '';

    $validationErrors = $this->_validateUserCreation($post,$idToEdit);

    if($validationErrors) {
      $message = $this->_composeMessageHtml('You have some validation errors. Please fix them to continue.','error');
    } else {
      if(isset($post['id_to_edit']) && $post['id_to_edit']) {
        $updateUser = array('name' => $post['name'],
        'email' => $post['email'],
        'utype' => $post['utype'],
        'admin_user_status' => $post['admin_user_status']
        );

        if($modelUser->updateData($updateUser,$post['id_to_edit'])) {
          // insert log entry
          $logText = 'Adminuser '.$this->user['name'].' ( User# '.$this->user['id_admin_user'].') edited a user data having user# '.$post['id_to_edit'];
          $this->modelLog->insertLogEntry($logText,$this->user['id_admin_user'],serialize($post),$post['id_to_edit'],'editadminuser','id_admin_user');
          $message = $this->utilities->composeMessageHtml('User updated successfully','success');
        } else {
          $frmData = $post;
          $message = $this->utilities->composeMessageHtml('Something went wrong while updating the user details. Please try again later.','error');
        }
      } else {
        $salt = $modelUser->getSalt(25);
        $passwordHash = $modelUser->_computePasswordHash($post['password'],$salt);

        $newUser = array('name' => $post['name'],
          'email' => $post['email'],
          'password' => $passwordHash,
          'salt' => $salt,
          'date_created' => date('Y-m-d H:i:s'),
          'last_login' => date('Y-m-d H:i:s'),
          'utype' => $post['utype'],
          'admin_user_status' => 1,
          );

        if($userId = $modelUser->insertData($newUser)) {
          // insert log entry
          $logText = 'User '.$this->user['name'].' ( User# '.$this->user['id_admin_user'].') added a user user# '.$userId;
          $this->modelLog->insertLogEntry($logText,$this->user['id_admin_user'],serialize($post),$userId,'adduser','','Backoffice activity','');
          $message = $this->utilities->composeMessageHtml('User added successfully','success');
        } else {
          $message = $this->utilities->composeMessageHtml('Something went wrong while adding user. Please try again later.','error');
        }
      }
    }
  }

  if(isset($get['edit']) && $get['edit']) {
    $user = $modelUser->getRowById($get['edit']);
    $frmData = $user;
  }

  if(isset($message) && $message) $this->view->message = $message;
  if(isset($frmData) && $frmData) $this->view->frmData = $frmData;
  if(isset($validationErrors) && $validationErrors) $this->view->errors = $validationErrors;
  if(isset($user) && $user) $this->view->edit = $user;
}

public function viewuserAction() {
  $this->view->activeMenuItem = 'users';
  $this->view->pageHeading = 'User Details';
  $breadcrumbs[] = array('link' => 'index', 'label' => 'Dashboard', 'icon' => 'fa fa-dashboard');
  $breadcrumbs[] = array('link'=>'users/index','label'=>'Users','icon'=>'fa fa-user');
  $breadcrumbs[] = array('link'=>'','label'=>'User','icon'=>'');
  $this->view->breadcrumbs = $breadcrumbs;

  $modelUser = new Application_Model_DbTable_Adminusers();
  $request = $this->getRequest();
  $get = $request->getQuery();
  if(isset($get['id']) && $get['id']){
    $users = $modelUser->getRowById($get['id']);
    if(isset($users['managed_by']) && ($users['managed_by'])){
      $manager = $modelUser->getRowById($users['managed_by']);
    }
  }
  if(isset($users) && $users) $this->view->users = $users;
  if(isset($manager) && $manager) $this->view->manager = $manager;
}

public function deleteuserAction() {
  // initialise necessary models
  $modelUser = new Application_Model_DbTable_Adminusers();

  $request = $this->getRequest();
  $get = $request->getQuery();

  if(isset($get['del']) && $get['del']){
    if($modelUser->deleteData($get['del']))	{
      // insert log entry
      $logText = 'User '.$this->user['name'].' ( User# '.$this->user['id_admin_user'].') deleted a user user# '.$get['del'];
      $this->modelLog->insertLogEntry($logText,$this->user['id_admin_user'],serialize($get),$get['del'],'deleteuser','','Backoffice activity','');
      $this->_redirect('/backoffice/users/index');
    }
  }
}

public function changepasswordAction() {
  $this->view->activeMenuItem = 'users';
  $this->view->pageHeading = 'Change Password';
  $breadcrumbs[] = array('link' => 'index', 'label' => 'Dashboard', 'icon' => 'fa fa-dashboard');
  $breadcrumbs[] = array('link'=>'user','label'=>'Users Profile','icon'=>'fa fa-user');
  $breadcrumbs[] = array('link'=>'','label'=>'Change Password','icon'=>'');
  $this->view->breadcrumbs = $breadcrumbs;


  $request = $this->getRequest();
  $get = $request->getQuery();

  if(isset($get['id_admin_user']) && $get['id_admin_user']) {
    $modelUser = new Application_Model_DbTable_Adminusers();
    $user = $modelUser->getRowById($get['id_admin_user']);

    if(isset($user) && $user) {
      if($request->isPost()) {
        $post = $request->getPost();
        $idToEdit = $get['id_admin_user'];
        $validationErrors = $this->_validateChangePassword($post);
        if($validationErrors) {
          $message = $this->utilities->composeMessageHtml('You have some validation errors. Please fix them to continue.','error');
        } else {
          if(isset($idToEdit) && $idToEdit) {
            $salt = $modelUser->getSalt(25);
            $passwordHash = $modelUser->_computePasswordHash($post['password'],$salt);
            $updateUser = array('password' => $passwordHash, 'salt' => $salt);
            if($modelUser->updateData($updateUser,$idToEdit)) {
              $logText = 'Adminuser '.$this->user['name'].' ( User# '.$this->user['id_admin_user'].') has changed password of user user# '.$idToEdit;
              $this->modelLog->insertLogEntry($logText,$this->user['id_admin_user'],serialize($post),$idToEdit,'changepassword','id_admin_user');
              $message = $this->utilities->composeMessageHtml('Password updated successfully','success');
            } else {
              $frmData = $post;
              $message = $this->utilities->composeMessageHtml('Something went wrong while updating the password. Please try again later.','error');
            }
          }
        }
      }
    }
  } else {
    if(isset($this->user['id_admin_user']) && $this->user['id_admin_user']) {
      $modelUser = new Application_Model_DbTable_Adminusers();
      $user = $modelUser->getRowById($this->user['id_admin_user']);
      if($request->isPost()) {
        $post = $request->getPost();
        $frmData = $post;
        $idToEdit = $this->user['id_admin_user'];
        $validationErrors = $this->_validateChangePassword($post);
        if($validationErrors) {
          $message = $this->utilities->composeMessageHtml('You have some validation errors. Please fix them to continue.','error');
        } else {
          if(isset($idToEdit) && $idToEdit) {
            $salt = $modelUser->getSalt(25);
            $passwordHash = $modelUser->_computePasswordHash($post['password'],$salt);
            $updateUser = array('password' => $passwordHash, 'salt' => $salt);
            if($modelUser->updateData($updateUser,$idToEdit)) {
              $logText = 'Adminuser '.$this->user['name'].' ( User# '.$this->user['id_admin_user'].') has changed password of user user# '.$idToEdit;
              $this->modelLog->insertLogEntry($logText,$this->user['id_admin_user'],serialize($post),$idToEdit,'changepassword','id_admin_user');
              $message = $this->utilities->composeMessageHtml('Password updated successfully','success');
            } else {
              $frmData = $post;
              $message = $this->utilities->composeMessageHtml('Something went wrong while updating the password. Please try again later.','error');
            }
          }
        }
      }
    }
  }

  if(isset($message) && $message) $this->view->message = $message;
  if(isset($user) && $user) $this->view->user = $user;
  if(isset($validationErrors) && $validationErrors) $this->view->errors = $validationErrors;
  if(isset($frmData) && $frmData) $this->view->frmData = $frmData;
}

public function _validateUserCreation($post,$idToEdit = '') {
		$errors = array();
		$modelUser = new Application_Model_DbTable_Adminusers();

		if($idToEdit ==''){
			if(!isset($post['name']) || !trim($post['name'])) $errors['name'] = 'Please enter a name of user';
			if(!isset($post['password']) || !trim($post['password'])) $errors['password'] = 'Please enter a password';
			else if(isset($post['password']) && isset($post['cpassword']) &&  $post['password'] != $post['cpassword'] )
			{
				$errors['cpassword'] = 'Password mismatch';
			}
			if(!isset($post['email']) || !trim($post['email'])) $errors['email'] = 'Please enter an email address';
			else if(isset($post['email']) && !filter_var($post['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Please enter a valid email address';
			else if(!$modelUser->isUnique('email',$post['email'])) $errors['email'] = 'This email id has been already taken.';
			if(!isset($post['utype']) || !trim($post['utype'])) $errors['utype'] = 'Please select a user type';
		}
		else if($idToEdit){
			$userToEdit = $modelUser->getRowById($idToEdit);
			if(!isset($post['name']) || !trim($post['name'])) $errors['name'] = 'Please enter a name of user';

			if(!isset($post['email']) || !trim($post['email'])) $errors['email'] = 'Please enter an email address';
			else if(isset($post['email']) && !filter_var($post['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Please enter a valid email address';
			else if(!$modelUser->isUnique('email',$post['email'],' AND `id_admin_user` != '.$userToEdit['id_admin_user'])) $errors['email'] = 'This email id has been already taken.';
			if(!isset($post['utype']) || !trim($post['utype'])) $errors['utype'] = 'Please select a user type';
			if(!isset($post['admin_user_status']) || ($post['admin_user_status'] == '')) $errors['admin_user_status'] = 'Please select a status';
		}

		return $errors;
	}

	public function _validateChangePassword($post) {
		$errors = array();

        if (!isset($post['password']) || !trim($post['password'])) {
            $errors['password'] = 'Please enter new password.';
        } else if ($post['password'] != $post['newpassword']) {
            $errors['newpassword'] = 'Password mismatch.';
        }
		return $errors;
	}

}
