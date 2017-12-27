<?php

class Application_Model_DbTable_Adminusers extends Zend_Db_Table_Abstract
{
    protected $_name = 'admin_users';
    protected $identityColumn = 'id_admin_user';

	public function _computePasswordHash($password,$salt) {
		return hash('sha256', $password.$salt);
	}

	function crypto_rand_secure($min, $max) {
        $range = $max - $min;
        if ($range < 0)
            return $min; // not so random...
        $log = log($range, 2);
        $bytes = (int) ($log / 8) + 1; // length in bytes
        $bits = (int) $log + 1; // length in bits
        $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        } while ($rnd >= $range);
        return $min + $rnd;
    }

	function getSalt($length = 32) {
        $token = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet.= "0123456789";
        for ($i = 0; $i < $length; $i++) {
            $token .= $codeAlphabet[$this->crypto_rand_secure(0, strlen($codeAlphabet))];
        }
        return $token;
    }

	function getPasswordSalt($email) {
	$row = $this->getRowByCondition(' `email` = "'.$email.'"');

	if($row) return $row['salt'];
	return '';
    }

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

   public function execute($sql) {
	   if(!$sql) return false;
	   $dbAdapter = Zend_Db_Table::getDefaultAdapter();
	   $res = $dbAdapter->query($sql);
	   return $res;
   }

}
