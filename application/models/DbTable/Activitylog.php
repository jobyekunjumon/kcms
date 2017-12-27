<?php
class Application_Model_DbTable_Activitylog extends Zend_Db_Table_Abstract
{
    protected $_name = 'admin_users_activity_log';
    protected $identityColumn = 'id_activity';

    function isUnique($field,$value , $extra='') {
	    $where = ' `'.$field.'` = "'.$value.'"';
		$where .= $extra;
		$row = $this->fetchRow($where);
        if($row){
            return false;
        }
        return true;
    }

    public function getAll($cond='',$query = '',$fields="*") {
		if($query) $sql = $query;
		else $sql = 'SELECT '.$fields.' FROM `'.$this->_name.'` '.$cond;
	    //get dbAdapter instance
        $dbAdapter = Zend_Db_Table::getDefaultAdapter();
        $res = $dbAdapter->fetchAll($sql);
        return $res;
   }

   public function execute($sql) {
	   if(!$sql) return false;
	   $dbAdapter = Zend_Db_Table::getDefaultAdapter();
	   $res = $dbAdapter->query($sql);
	   return $res;
   }

   public function getRowByCondition($order)  {
       $row = $this->fetchRow($order);
	   if ($row) {
		  $res = $row->toArray();
		  return $res;
	   }
	   return 0;
   }
   public function getRowById($id) {
        $row = $this->fetchRow($this->identityColumn.' = '.$id);
		if ($row) {
		  $res = $row->toArray();
		  return $res;
		}
		return 0;
   }
   public function insertData($data)  {
	   $res = $this->insert($data);
	   if($res) return $res;
	   return false;
   }

   public function getCountRecords($order='') {
      $sql = 'SELECT * FROM `'.$this->_name.'` '.$order;
	  //get dbAdapter instance
      $dbAdapter = Zend_Db_Table::getDefaultAdapter();
      $rows = $dbAdapter->fetchAll($sql);
	  if($rows) return(count($rows));
	  else return 0;
   }


   public function updateData($data,$id) {
     $where = $this->identityColumn.'='.$id;
	 if($this->update($data,$where))
	   return true;
	 else return false;
   }

   public function deleteData($id)  {
     $where = $this->identityColumn.'='.$id;
	 if($this->delete($where))
	   return true;
	 else return false;
   }

   public function getFieldValue($id,$field) {
      $row = $this->getRowById($id);
	  if(!$row) return '';
	  return $row[$field];
   }

   public function insertLogEntry($logText,$idUser,$activityData='',$connectionId='',$type='',$connectionParam='') {
	   $userIp = $_SERVER['REMOTE_ADDR'];
	   $userAgent = $_SERVER['HTTP_USER_AGENT'];

	   $newLogEntry = array( 'id_admin_user' => $idUser ,
							 'activity_date' => date('Y-m-d H:i:s') ,
							 'activity_description' => $logText ,
							 'activity_data' => $activityData,
							 'connection_id' => $connectionId ,
							 'activity_type' => $type ,
							 'connection_param' => $connectionParam,
               'user_ip' => $userIp ,
							 'user_agent' => $userAgent,
						   );

	  if($id = $this->insertData($newLogEntry)) {
		  return $id;
	  }
	  return false;
   }
}
