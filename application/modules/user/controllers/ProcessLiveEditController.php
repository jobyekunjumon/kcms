<?php
class User_ProcessLiveEditController extends Zend_Controller_Action {

  protected $siteSlug = '';

  public function init() {
    $layout = $this->_helper->layout();
    $layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    $auth = Zend_Auth::getInstance();
    if ($auth->hasIdentity()) {
      $this->user = (array) $auth->getIdentity();
    } else {
      exit('Could not authenticate user.');
    }

    $this->utilities = new Application_Model_Utilities();
  }

  public function saveTextAction() {
    $request = $this->getRequest();
    $post = $request->getPost();

    $out = array();
    // validate input
    if(!isset($post['id_content']) || !$post['id_content'] || !isset($post['content'])) {
      $out['status'] = 0;
      $out['message'] = 'Something went wrong while saving your data. Please try again after refreshing the page.1';
      exit(json_encode($out));
    }

    $modelContents = new Application_Model_DbTable_Contents();
    // fetch content
    $content = $modelContents->getRowById($post['id_content']);
    if(!$content) {
      $out['status'] = 0;
      $out['message'] = 'Something went wrong while saving your data. Please try again after refreshing the page.2';
      exit(json_encode($out));
    }

    // fetch site entry and verify content owner
    $modelSites = new Application_Model_DbTable_Sites();
    $site = $modelSites->getRowByCondition(' `id_site` = '.$content['id_site'].' AND `id_user` = '.$this->user['id_user']);
    if(!$site) {
      $out['status'] = 0;
      $out['message'] = 'You are not allowed to change this content. Please login to edit the content.';
      exit(json_encode($out));
    }

    // update content
    $updateContent = array('content' => addslashes($post['content']));
    if($modelContents->updateData($updateContent,$post['id_content'])) {
      $out['status'] = 1;
      $out['message'] = 'Success';
      exit(json_encode($out));
    }

    $out['status'] = 0;
    $out['message'] = 'Something went wrong while saving your data. Please try again after refreshing the page.4';
    exit(json_encode($out));

  }
  ////////////////////////////////////////////////////////////////////
  //////////////   HELPER FUNCTIONS //////////////////////////////////
  ////////////////////////////////////////////////////////////////////


}
