<?php

class Application_Model_DbTable_Configurations extends Zend_Db_Table_Abstract
{
    protected $_name = 'configurations';
    protected $identityColumn = 'id_configuration';
    
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
     
   public function getRowByCondition($order)  {
       $row = $this->fetchRow($order);
	   if ($row) {		
		  $res = $row->toArray();
		  return $res;
	   }
	   return 0;
   }
   
    public function getConfig($config) {
	   if(!$config) return false;
	   $configuration = $this->getRowByCondition(' `configuration` = "'.$config.'" ');
	   if($configuration) return $configuration['value'];
	   
	   return false;
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
      $sql = 'SELECT count(*) FROM `'.$this->_name.'` '.$order;	  
	  //get dbAdapter instance
      $dbAdapter = Zend_Db_Table::getDefaultAdapter();	
      $rows = $dbAdapter->fetchAll($sql);	  
	  if($rows[0]['count(*)']) return(count($rows[0]['count(*)']));
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
   
   public function execute($sql) {
	   if(!$sql) return false;
	   $dbAdapter = Zend_Db_Table::getDefaultAdapter();
	   $res = $dbAdapter->query($sql);
	   return $res;
   }
      
   
}
