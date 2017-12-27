<?php
class Backoffice_IndexController extends Zend_Controller_Action {

  public function init() {
  ini_set ( 'upload_max_filesize' , '100M' );
  ini_set ( 'post_max_size' , '100M' );
  $layout = $this->_helper->layout();
  $layout->setLayout('layout_alte');
  date_default_timezone_set("Asia/Kolkata");

  $auth = Zend_Auth::getInstance();
  if ($auth->hasIdentity()) {
  $this->user = (array) $auth->getIdentity();
  unset($this->user['salt']);
  unset($this->user['password']);
  $this->view->user = $this->user;
  } else {
  $this->_helper->redirector('index', 'auth');
  }

  $this->utilities = new Application_Model_Utilities();

  }

  public function indexAction() {
    $this->view->activeMenuItem = 'dashboard';
    $this->view->pageHeading = 'Dashboard';
    $breadcrumbs[] = array('link' => '', 'label' => 'Dashboard', 'icon' => 'fa fa-dashboard');
    $this->view->breadcrumbs = $breadcrumbs;
  }

	public function changepasswordAction() {

		$this->view->activeMenuItem = 'dashboard';
		$this->view->pageHeading = 'Change Password';
		$breadcrumbs[] = array('link' => 'index', 'label' => 'Dashboard', 'icon' => 'fa fa-dashboard');
		$breadcrumbs[] = array('link'=>'user','label'=>'Users Profile','icon'=>'fa fa-user');
		$breadcrumbs[] = array('link'=>'','label'=>'Change Password','icon'=>'');
		$this->view->breadcrumbs = $breadcrumbs;


		$request = $this->getRequest();
		$get = $request->getQuery();

		if(isset($get['id_user']) && $get['id_user']) {
			$modelUser = new Application_Model_DbTable_Users();
			$user = $modelUser->getRowById($get['id_user']);

			if(isset($user) && $user) {
				if($request->isPost()) {
					$post = $request->getPost();
					$idToEdit = $get['id_user'];
					$validationErrors = $this->_validateChangePassword($post);
					if($validationErrors) {
						$message = $this->utilities->composeMessageHtml('You have some validation errors. Please fix them to continue.','error');
					} else {
						if(isset($idToEdit) && $idToEdit) {
							$salt = $modelUser->getSalt(25);
							$passwordHash = $modelUser->_computePasswordHash($post['password'],$salt);
							$updateUser = array('password' => $passwordHash, 'salt' => $salt);
							if($modelUser->updateData($updateUser,$idToEdit)) {
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
			if(isset($this->user['id_user']) && $this->user['id_user']) {
				$modelUser = new Application_Model_DbTable_Adminusers();
				$user = $modelUser->getRowById($this->user['id_user']);
				if($request->isPost()) {
					$post = $request->getPost();
					$frmData = $post;
					$idToEdit = $this->user['id_user'];
					$validationErrors = $this->_validateChangePassword($post);
					if($validationErrors) {
						$message = $this->utilities->composeMessageHtml('You have some validation errors. Please fix them to continue.','error');
					} else {
						if(isset($idToEdit) && $idToEdit) {
							$salt = $modelUser->getSalt(25);
							$passwordHash = $modelUser->_computePasswordHash($post['password'],$salt);
							$updateUser = array('password' => $passwordHash, 'salt' => $salt);
							if($modelUser->updateData($updateUser,$idToEdit)) {
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

  ////////////////////////////////////////////////////////////////////
  //////////////   HELPER FYNCTIONS //////////////////////////////////
  ////////////////////////////////////////////////////////////////////

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
