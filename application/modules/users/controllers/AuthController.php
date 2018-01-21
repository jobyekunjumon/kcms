<?php

class User_AuthController extends Zend_Controller_Action
{
  public function init()
  {
    $layout = $this->_helper->layout();
    $layout->setLayout('layout_auth_user');
  }

  public function indexAction()
  {
    $request = $this->getRequest();
    if($request->isPost()) {
      $post = $request->getPost();
      $validationErrors = $this->getInputErrors($post);
      if($validationErrors) {
        $message = '<div class="alert alert-error"> You have some errors <br>';
        foreach($validationErrors as $error) {
        $message .= $error;
        }
        $message .= '</div>';
      } else {
        $modelUsers = new Application_Model_DbTable_Adminusers();
        $email = $post['username'];
        $passwordSalt = $modelUsers->getPasswordSalt($email);
        $passwordHash = $modelUsers->_computePasswordHash($post['password'], $passwordSalt);

        if($idUser = $this->_processLogin($email,$passwordHash)) {
          // update login date
          $dataUpdateUser = array('last_login'=> date('Y-m-d H:i:s'));
          $modelUsers->updateData($dataUpdateUser,$idUser);
          $urlUserDash = array('controller'=>'index', 'action'=>'index');
          $this->_helper->redirector->gotoRoute($urlUserDash);
        } else {
          $message = '<div class="alert alert-error">Login failed. Please ensure that the username and password you entered are correct.</div>';
        }
      }
    }

    if(isset($message) && $message) $this->view->message = $message;
  }

  protected function _processLogin($email,$password) {

    $adapter = $this->_getAuthAdapter();
    $adapter->setIdentity($email);
    $adapter->setCredential($password);

    $auth = Zend_Auth::getInstance();
    $result = $auth->authenticate($adapter);
    if ($result->isValid()) {
      $user = $adapter->getResultRowObject();
      $auth->getStorage()->write($user);
      return $auth->getIdentity()->id_admin_user;
    }
    return false;
  }

  protected function _getAuthAdapter()
  {

    $dbAdapter = Zend_Db_Table::getDefaultAdapter();
    $authAdapter = new Zend_Auth_Adapter_DbTable($dbAdapter);

    $authAdapter->setTableName('admin_users')
                ->setIdentityColumn('email')
                ->setCredentialColumn('password');
    $authAdapter->getDbSelect()->where('admin_user_status = 1 AND `utype` = "super_admin"');

    return $authAdapter;
  }

  public function logoutAction() {
    Zend_Auth::getInstance()->clearIdentity();
    $urlUserAuth = array('controller'=>'auth', 'action'=>'index');
    $this->_helper->redirector->gotoRoute($urlUserAuth);
  }

  public function getInputErrors($post) {
    $errors = array();
    if(!isset($post['username']) || !trim($post['username'])) {
      $errors['username'] = ' * Please enetr a username. <br>';
    }
    if(!isset($post['password']) || !trim($post['password'])) {
      $errors['password'] = ' * Please enetr a password. <br>';
    }
    if(count($errors)) return $errors;
    return false;
  }

}
