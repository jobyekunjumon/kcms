<?php

class SignupController extends Zend_Controller_Action {

  public function init() {
    /* Initialize action controller here */
    $layout = $this->_helper->layout();
    $layout->setLayout('layout');
    $this->utilities = new Application_Model_Utilities();
  }

  public function indexAction(){
    $request = $this->getRequest();

    if($request->isPost()) {
      $post = $request->getPost();
      $validationErrors = $this->_validateSignUp($post);
      if($validationErrors) {
        $frmData = $post;
        $message = $this->utilities->composeMessageHtml('You have some validation errors. Please fix them to continue.','error');
      } else {
        $modelUsers = new Application_Model_DbTable_Users();
        // create password salt
        $salt = $modelUsers->getSalt(25);
        // create password
        $passwordHash = $modelUsers->_computePasswordHash($post['password'],$salt);
        // create email verification key
        $emailVerificationCode = $modelUsers->getSalt(25);
        // create user
        $newUser = array( 'name' => addslashes($post['name']),
                          'email' => addslashes($post['email']),
                          'password' => $passwordHash,
                          'salt' => $salt,
                          'date_created' => date('Y-m-d H:i:s'),
                          'last_login' => date('Y-m-d H:i:s'),
                          'email_verification_key' => $emailVerificationCode,
                          'user_status' => 'active',
                          );
        if($idUser = $modelUsers->insertData($newUser)) {
          $newUser['id_user'] = $idUser;
          $mailer = new Application_Model_Mailer();
          // send welcome mail
          $mailer->sendWelcomeMail($newUser);
          // send email activation link
          $varificationUrl = $this->utilities->getServerUrl().'/'.$this->view->baseUrl().'/signup/verfiy?key='.$idUser.'&hash='.$newUser['email_verification_key'];
          $mailer->sendEmailVerificationMail($newUser,$verificationUrl);
          // write to session storage
          unset($newUser['password']);
          unset($newUser['salt']);
          unset($newUser['email_verification_key']);
          $auth = Zend_Auth::getInstance();
          $auth->getStorage()->write($newUser);
          // redirect to domain selection page
          $this->_redirect('/user/create-site/choose-name');
        }
      }
    }

    if(isset($message) && $message) $this->view->message = $message;
    if(isset($frmData) && $frmData) $this->view->frmData = $frmData;
    if(isset($validationErrors) && $validationErrors) $this->view->errors = $validationErrors;
  }

  ///////////////////////////////////////////////////////////////////////////
  ///////////////////////////  HELPER FUNCTIONS /////////////////////////////
  //////////////////////////////////////////////////////////////////////////

  public function _validateSignUp($post) {
    $modelUsers = new Application_Model_DbTable_Users();
    $errors = array();

    if(!isset($post['name']) || !trim($post['name'])) $errors['name'] = 'Please enter a name of user';
    if(!isset($post['password']) || !trim($post['password'])) $errors['password'] = 'Please enter a password';
    if(!isset($post['email']) || !trim($post['email'])) $errors['email'] = 'Please enter an email address';
    else if(isset($post['email']) && !filter_var($post['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Please enter a valid email address';
    else if(!$modelUsers->isUnique('email',$post['email'])) $errors['email'] = 'This email id has been already taken.';

    return $errors;
  }

}
?>
