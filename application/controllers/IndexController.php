<?php

class IndexController extends Zend_Controller_Action {

  public function init() {
    /* Initialize action controller here */
    $layout = $this->_helper->layout();
    $layout->setLayout('layout');
    date_default_timezone_set("Asia/Kolkata");

  }
  public function indexAction(){

  }

}
?>
