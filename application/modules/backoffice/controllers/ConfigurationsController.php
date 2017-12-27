<?php

class Backoffice_ConfigurationsController extends Zend_Controller_Action {

  public function init() {
    /* Initialize action controller here */
    $layout = $this->_helper->layout();
    $layout->setLayout('layout_alte');

    $auth = Zend_Auth::getInstance();
    if ($auth->hasIdentity() && $auth->getIdentity()->utype == "super_admin") {
      $this->user = (array) $auth->getIdentity();
      unset($this->user['salt']);
      unset($this->user['password']);
      $this->view->user = $this->user;

    } else {
      $this->_helper->redirector('index', 'auth');
    }

    $this->utilities = new Application_Model_Utilities();
    $this->modelLog = new Application_Model_DbTable_Activitylog();

  }

public function addconfigAction() {
  $this->view->activeMenuItem = 'configurations';
  $this->view->pageHeading = 'Add Configurations';
  $breadcrumbs[] = array('link' => 'index', 'label' => 'Dashboard', 'icon' => 'fa fa-dashboard');
  $breadcrumbs[] = array('link'=>'','label'=>'Configurations','icon'=>'');
  $this->view->breadcrumbs = $breadcrumbs;

  $modelConfig = new Application_Model_DbTable_Configurations();
  // process query
  $request = $this->getRequest();
  $get = $request->getQuery();
  try{
    if($request->isPost()) {
      $post = $request->getPost();
      $frmData = $post;
      if (isset($post['id_to_edit']) && $post['id_to_edit']) {
        $idToEdit = $post['id_to_edit'];
      } else $idToEdit = '';

      $validationErrors = $this->_validateconfigCreation($post, $idToEdit);
      if ($validationErrors) {
        $message = $this->utilities->composeMessageHtml('You have some validation errors. Please fix them to continue.', 'error');
      } else {
        if(isset($post['id_to_edit']) && $post['id_to_edit']) {
          $updateconfig = array('configuration' => $post['configuration'],
          'value' => $post['value'],
          'comments' => $post['comments']
          );
          if($modelConfig->updateData($updateconfig,$post['id_to_edit'])) {
            // insert log entry
            $logText = 'Adminuser '.$this->user['name'].' ( User# '.$this->user['id_admin_user'].') edited a configuration data having configuration #'.$post['id_to_edit'];
            $this->modelLog->insertLogEntry($logText,$this->user['id_admin_user'],serialize($post),$post['id_to_edit'],'editconfiguration','id_configuration');
            $message = $this->utilities->composeMessageHtml('Configurations updated successfully','success');
          } else {
            $frmData = $post;
            $message = $this->utilities->composeMessageHtml('Something went wrong while updating the Configuration details. Please try again later.','error');
          }
        } else {
          $newconfig = array('configuration' => $post['configuration'],
          'value' => $post['value'],
          'comments' => $post['comments']
          );

          if ($confId = $modelConfig->insertData($newconfig)) {
            // insert log entry
            $logText = 'Adminuser '.$this->user['name'].' ( User# '.$this->user['id_admin_user'].') added a configuration configuration #'.$confId;
            $this->modelLog->insertLogEntry($logText,$this->user['id_admin_user'],serialize($post),$confId,'addconfiguration','id_configuration');
            $message = $this->utilities->composeMessageHtml('Configurations added successfully', 'success');
          } else {
            $message = $this->utilities->composeMessageHtml('Something went wrong while adding Configurations. Please try again later.', 'error');
          }
        }
      }

    }
    if(isset($get['edit']) && $get['edit'] ) {
      $configs = $modelConfig->getRowById($get['edit']);
      $frmData = $configs;
    }
  }catch(Exception $e){
    echo $e->getMessage();
  }
  if(isset($message) && $message) $this->view->message = $message;
  if(isset($frmData) && $frmData) $this->view->frmData = $frmData;
  if(isset($validationErrors) && $validationErrors) $this->view->errors = $validationErrors;
  if(isset($configs) && $configs) $this->view->edit = $configs;
}

public function indexAction() {
  $this->view->activeMenuItem = 'configurations';
  $this->view->pageHeading = 'Configurations';
  $breadcrumbs[] = array('link' => 'index', 'label' => 'Dashboard', 'icon' => 'fa fa-dashboard');
  $breadcrumbs[] = array('link'=>'','label'=>'Configurations','icon'=>'');
  $this->view->breadcrumbs = $breadcrumbs;


  $modelConfig = new Application_Model_DbTable_Configurations();
  // process query
  $request = $this->getRequest();
  $get = $request->getQuery();

  $sqlConfigs = "SELECT * from `configurations`";

  // pagination
  $pageLimit = 20;
  $start = 0;
  if (isset($get['page']))
  $page = intval($get['page']);
  $noRecords = count($modelConfig->getAll('', $sqlConfigs, ''));
  $tpages = ceil($noRecords / $pageLimit);
  if (!isset($page) || $page <= 0)
  $page = 1;
  $reload = $this->view->baseUrl() . '/backoffice/configurations/index?';

  $reload .= $this->utilities->getUrlParams($get, array('page', 'del', 'chs'));
  $pagination = $this->utilities->paginate_two($reload, $page, $tpages, 4);
  $start = ($page - 1) * $pageLimit;

  $sqlConfigs .= ' LIMIT ' . $start . ' , ' . $pageLimit;


  // get configurations
  $configs = $modelConfig->getAll('', $sqlConfigs, '');
  $urlParamStr = $this->utilities->getUrlParams($get, array('del', 'chs'));

  if(isset($message) && $message) $this->view->message = $message;
  if(isset($pagination) && $pagination) $this->view->pagination = $pagination;
  if(isset($configs) && $configs) $this->view->configs = $configs;

}

////////////////////////////////////////////////////////////////////
//////////////   HELPER FUNCTIONS //////////////////////////////////
////////////////////////////////////////////////////////////////////

 public function _validateconfigCreation($post,$idToEdit = '') {
		$errors = array();
    $modelConfig = new Application_Model_DbTable_Configurations();

		if($idToEdit =='') {
			if(!isset($post['configuration']) || !trim($post['configuration'])) $errors['configuration'] = 'Please enter Configuration';
      else if(!$modelConfig->isUnique('configuration',$post['configuration'])) $errors['configuration'] = 'This configuration name already exist.';
			if(!isset($post['value']) || !trim($post['value'])) $errors['value'] = 'Please enter value';
		} else {
      if(!isset($post['configuration']) || !trim($post['configuration'])) $errors['configuration'] = 'Please enter Configuration';
      else if(!$modelConfig->isUnique('configuration',$post['configuration'],' AND `id_configuration` != '.$idToEdit)) $errors['configuration'] = 'This configuration name already exist.';
			if(!isset($post['value']) || !trim($post['value'])) $errors['value'] = 'Please enter value';
    }
		return $errors;
	}

}
