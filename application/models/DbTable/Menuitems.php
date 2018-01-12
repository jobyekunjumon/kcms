<?php
class Application_Model_DbTable_Menuitems extends Zend_Db_Table_Abstract
{
    protected $_name = 'menu_items';
    protected $identityColumn = 'id_menu_item';

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

   public function getMenuItemAnchor($menuItem,$siteSlug,$idMenuItem=0,$baseUrl='') {
     if(!$menuItem && $idMenuItem) $menuItem = $this->getRowById($idMenuItem);

     $out .= '';
     if($menuItem) {
       if($menuItem['link_type'] == "page_link" && $menuItem['page_slug'] ) $out .= ' <a target="'.$menuItem['target'].'" href="'.$baseUrl.'?name='.$siteSlug.'&page='.$menuItem['page_slug'].'">'.$menuItem['title'].'</a>';
       else if($menuItem['link_type'] == "internal_page_link" && $menuItem['internal_target_name'] ) $out .= ' <a target="'.$item['target'].'" href="#internal_target_name">'.$menuItem['title'].'</a>';
       else if($menuItem['link_type'] == "external" && $menuItem['external_link'] ) $out .= ' <a target="'.$menuItem['target'].'" href="'.$menuItem['external_link'].'">'.$menuItem['title'].'</a>';
       else $out .= ' <a target="'.$menuItem['target'].'" href="">'.$menuItem['title'].'</a>';
     }

     return $out;
   }

   public function getMenuItemLink($menuItem,$siteSlug,$idMenuItem=0,$baseUrl='') {
     if(!$menuItem && $idMenuItem) $menuItem = $this->getRowById($idMenuItem);

     $out .= '';
     if($menuItem) {
       if($menuItem['link_type'] == "page_link" && $menuItem['page_slug'] ) $out .= $baseUrl.'?name='.$siteSlug.'&page='.$menuItem['page_slug'];
       else if($menuItem['link_type'] == "internal_page_link" && $menuItem['internal_target_name'] ) $out .= '#internal_target_name';
       else if($menuItem['link_type'] == "external" && $menuItem['external_link'] ) $out .= $menuItem['external_link'];
     }

     return $out;
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

   public function getMenuItemsHierarchy($idParent=0,$condition='') {

    $sqlGetMenuItems = ' WHERE `id_parent_menu_item` = '.$idParent;
    if($condition) $sqlGetMenuItems .= ' AND '.$condition;
    $sqlGetMenuItems .= ' ORDER BY `sort_order` ASC, `id_menu_item` ASC' ;
		$menuItems = $this->getAll($sqlGetMenuItems);

		if($menuItems) {
			foreach($menuItems as $key=>$item) {
				$menuItems[$key]['sub_menu'] = $this->getMenuItemsHierarchy($item['id_menu_item'],$condition);
			}

			return $menuItems;

		} else return false;


   }

   public function getNextSortOrder($idMenu,$idParentMenuItem) {
     $sqlGetLatestSortOrder = 'SELECT MAX(`sort_order`) AS max_sort_order FROM `menu_items` WHERE `id_menu` = "'.$idMenu.'" AND `id_parent_menu_item` = '.$idParentMenuItem;
     $resLatestSortOrder = $this->getAll('',$sqlGetLatestSortOrder);
     if(isset($resLatestSortOrder[0]['max_sort_order']) && $resLatestSortOrder[0]['max_sort_order']) return $resLatestSortOrder[0]['max_sort_order']+1;
     else return 1;
   }

   public function getChildMenuItems($idParent) {
	   $menuItems = $this->getAll(' WHERE `id_parent_menu_item` = '.$idParent.' ORDER BY `id_menu_item` ');
	   return $categories;
   }

}
