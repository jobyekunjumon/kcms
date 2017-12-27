<?php

class Backoffice_ErrorController extends Zend_Controller_Action
{

    public function errorAction()
    {
        $errors = $this->_getParam('error_handler');
        
        if (!$errors || !$errors instanceof ArrayObject) {
            $this->view->message = 'You have reached the error page';
            return;
        }
        
        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                // 404 error -- controller or action not found
                $this->getResponse()->setHttpResponseCode(404);
                $priority = Zend_Log::NOTICE;
                $this->view->message = 'Page not found';
                $this->view->errorType = 404;
                break;           
        }
        
        // Log exception, if logger available
        if ($log = $this->getLog()) {
            $log->log($this->view->message, $priority, $errors->exception);
            $log->log('Request Parameters', $priority, $errors->request->getParams());
        }
        
        // conditionally display exceptions
        if ($this->getInvokeArg('displayExceptions') == true) {
            $this->view->exception = $errors->exception;
        }
        
        $this->view->request   = $errors->request;
        
        // get assigned tasks
		if($this->user['utype'] == 4) {
			$modelTasks = new Application_Model_DbTable_Tasks();
			$countActiveUserTasks = 0;
			$sqlGetCountActiveTasks = 'SELECT count(*)  FROM `tasks` t WHERE t.`assigned_to` = '.$this->user['id_user'].' AND t.`task_status` = "active" ';
			$resCountActiveTasks = $modelTasks->getAll('',$sqlGetCountActiveTasks);
			if(isset($resCountActiveTasks[0]['count(*)'])) $countActiveUserTasks = $resCountActiveTasks[0]['count(*)'];
		
			$sqlGetActiveTasks = 'SELECT t.*, u.`name`, u.`email` FROM `tasks` t, `admin_users` u	
							WHERE t.`assigned_to` = u.`id_user` AND  t.`assigned_to` = '.$this->user['id_user'].'  
							AND t.`task_status` = "active" ORDER BY `id_task` DESC LIMIT 0,10';			
			$activeUserTasks = $modelTasks->getAll('',$sqlGetActiveTasks);
			
			if($activeUserTasks) $this->view->activeUserTasks = $activeUserTasks;
			if($countActiveUserTasks) $this->view->countActiveUserTasks = $countActiveUserTasks;
		}	
    }

    public function getLog()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        if (!$bootstrap->hasResource('Log')) {
            return false;
        }
        $log = $bootstrap->getResource('Log');
        return $log;
    }


}

