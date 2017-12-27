<?php
class Application_Model_DbTable_Categories extends Zend_Db_Table_Abstract
{
    protected $_name = 'categories';
    protected $identityColumn = 'id_category';
    
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
      $sql = 'SELECT COUNT(*) AS CNT_RECORDS FROM `'.$this->_name.'` '.$order;
	  //get dbAdapter instance
      $dbAdapter = Zend_Db_Table::getDefaultAdapter();	
      $row = $dbAdapter->fetchRow($sql);	
	 
	  if($row) return($row['CNT_RECORDS']);
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
   
   public function getCatHierarchy($idParent=0) {
		
		$categories = $this->getAll(' WHERE `id_parent_category` = '.$idParent.' ORDER BY `category` ');
		
		if($categories) {
			foreach($categories as $key=>$cat) {
				$categories[$key]['sub_categories'] = $this->getCatHierarchy($cat['id_category']);
			}
			//echo '<pre>'; print_r($categories); echo '</pre><hr>';
			return $categories;
			
		} else return false;
		
		
   }
   
   public function getCatAnsestors($category) {
		
		$idParentCat = 0;
		if(is_array($category)) $idParentCat = $category['id_parent_category'];
		else $idParentCat = $category;
		
		@$categories = $this->getAll(' WHERE `id_category` = '.$idParentCat.' ORDER BY `category` ');

		if(isset($categories) && $categories) {
			foreach($categories as $key=>$cat) {
				$categories[$key]['parent_categories'] = $this->getCatAnsestors($cat['id_parent_category']);
			}
			
			return $categories;
			
		} else return false;
		
		
   }
      
   public function getChildCategories($idParent) {
	   $categories = $this->getAll(' WHERE `id_parent_category` = '.$idParent.' ORDER BY `category` ');
	   return $categories;
   }
   
   public function getCatByLevel($idParentCat = '' , $level = '' ) {	   		
		$categories = $this->getAll(' ORDER BY `category` ');
		
		$out = array();		
		if($categories) {				
			foreach($categories as $category) {
				$out[$category['category_level']][] = $category;				
			}
		}
		
		return $out;
   }  
   
   public function _getCategoriesCsv($categories) {		
		$out = '';
		if($categories) {
			foreach($categories as $cat) {
				$out .= $cat['id_category'].',';
				if(isset($cat['sub_categories']) && $cat['sub_categories']) $out .= $this->_getCategoriesCsv($cat['sub_categories']);
			}					
			return $out;			
		} else return false;
	}
	
    public function _getCategoryAnscestorsCsv($categories) {		
		$out = '';
		if($categories) {
			foreach($categories as $cat) {
				$out .= $cat['id_category'].',';
				if(isset($cat['parent_categories']) && $cat['parent_categories']) $out .= $this->_getCategoryAnscestorsCsv($cat['parent_categories']);
			}					
			return $out;			
		} else return false;
	}
	
   public function _flipKeys($res,$id) {
		if(!is_array($res) || !count($res)) return '';
		$out = array();
		foreach($res as $entry) {
			$out[$entry[$id]] = $entry;
		}
		return $out;
	}
	
	public function getSyncBoxesByCategory($idCat) {
		
		if(!$idCat || !is_numeric($idCat)) return 0;
		
		$category = $this->getRowById($idCat);
		
		if(!$category || !isset($category)) return 0;
		
		$categoryAnsestors = $this->getCatAnsestors($category);
		$categoryAncestorsCsv = $idCat.','.$this->_getCategoryAnscestorsCsv($categoryAnsestors);
		
		if (substr($categoryAncestorsCsv, -1) == ',')
                $categoryAncestorsCsv = substr($categoryAncestorsCsv, 0, (strlen($categoryAncestorsCsv) - 1));
        
        if(!$categoryAncestorsCsv) return 0;
        
       				
		$sqlGetdeptCatRln = 'SELECT DISTINCT `id_dept` FROM `dept_category_relationship` WHERE `id_category` IN ('.$categoryAncestorsCsv.') ';        
        $deptCatRln = $this->getAll('',$sqlGetdeptCatRln,'');        
        if(!$deptCatRln) return 0;                
        $deptsIds = '';$conj = '';
        foreach($deptCatRln as $dept) {
			$deptsIds .= $conj.$dept['id_dept']; 
			$conj = ',';
		}
		
		
		$sqlGetEdbDeptRln = 'SELECT DISTINCT `id_edubox` FROM `edubox_dept_relationship` WHERE `id_dept` IN ('.$deptsIds.') ';        
        $edbDeptRln = $this->getAll('',$sqlGetEdbDeptRln,'');
        
        if(!$edbDeptRln) return 0;        
        $edbIds = '';        
        foreach($edbDeptRln as $edb) {
			$edbIds .= '<'.$edb['id_edubox'].'>'; 			
		}
		
		if($edbIds) return $edbIds;
		
		return 0;
	}
}
