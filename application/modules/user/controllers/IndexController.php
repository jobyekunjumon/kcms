<?php
class User_IndexController extends Zend_Controller_Action {

  public function init() {

    $layout = $this->_helper->layout();
    $layout->setLayout('layout_user_dash');

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

    // get user sites
    $modelSites = new Application_Model_DbTable_Sites();
    $userSites = $modelSites->getAll(' WHERE `id_user` = '.$this->user['id_user']);

    if(isset($userSites) && $userSites) $this->view->userSites = $userSites;
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
