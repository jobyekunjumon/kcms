<?php
class Application_Model_DbTable_Menu extends Zend_Db_Table_Abstract
{
  protected $_name = 'menu';
  protected $identityColumn = 'id_menu';
  protected $view;

  public function getMenu($site,$idPage,$contentsCount='',$menuBaseUrl) {
    $this->view  = new Zend_View();
    $out = array();
    $menu = $this->getAll(' WHERE `id_site` = '.$site['id_site'].' AND `menu_status` = 1 AND (`id_page` = '.$idPage.' OR `id_page` = 0)');
    if($menu) {
      $modelMenuItems = new Application_Model_DbTable_Menuitems();

      foreach($menu as $menuEntry) {
        $menuContent = '';
        if($menuEntry['menu_type'] == "main_menu") {
          $menuItemsHirarchy = $modelMenuItems->getMenuitemsHierarchy(0,' `id_menu` = '.$menuEntry['id_menu'].' AND `menu_item_status` = 1');
          $menuContent = $this->_getMainMenuItemsRecursiveList($menuItemsHirarchy,$site['site_slug'],$menuBaseUrl,0);
        } else if($menuEntry['menu_type'] == "footer_menu") {
          $menuItems = $modelMenuItems->getAll(' WHERE `id_menu` = '.$menuEntry['id_menu'].' AND `menu_item_status` = 1');
          $menuContent = $this->_getFooterMenuList($menuItems,$site['site_slug'],$menuBaseUrl);
        }

        $out[$contentsCount]['component_id'] = $menuEntry['component_id'];
        $out[$contentsCount]['content_type'] = 'menu';
        $out[$contentsCount]['content'] = $menuContent;
        $out[$contentsCount++]['id_content'] = $menuEntry['id_menu'];
      }
    }

    return $out;
  }

  public function _getFooterMenuList($menUItems,$siteSlug,$menuBaseUrl) {
    $modelMenuItems = new Application_Model_DbTable_Menuitems();
    $out = '';
    if($menUItems) {
      $out .= '<ul class="footer-menu">';
        foreach ($menUItems as $item) {
          $out .= '<li class="'.$defaultActiveClass.' '.$addOnClass.'">'; $defaultActiveClass = '';
          $out .= $modelMenuItems->getMenuItemAnchor($item,$siteSlug,0,$menuBaseUrl);
          $out .= '</li>';
        }
      $out .= '</ul>';
    }
    return $out;
  }

  public function _getMainMenuItemsRecursiveList($menUItems,$siteSlug,$menuBaseUrl,$isDropdownMenu=0) {
    $modelMenuItems = new Application_Model_DbTable_Menuitems();
    $out = '';
    $mainItemClass = 'nav navbar-nav';
    if($isDropdownMenu)  {
      $mainItemClass = 'dropdown-menu';
      //$out .= '<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Dropdown <span class="caret"></span></a>';
    }

    $out .= '<ul class="'.$mainItemClass.'">';
    if($menUItems) {
      $defaultActiveClass = '';
      if(!$isDropdownMenu) $defaultActiveClass = 'active';
      $addOnClass = '';
      foreach($menUItems as $item) {
        if($item['sub_menu']) $addOnClass = 'dropdown'; $addOnLinkClass= 'dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"';

        $out .= '<li class="'.$defaultActiveClass.' '.$addOnClass.'">'; $defaultActiveClass = '';

        if($item['sub_menu']) $out .= '<a class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false" href="'.$this->view->baseUrl().'/sites?name='.$siteSlug.'&page='.$item['page_slug'].'">'.$item['title'].' <span class="caret"></span></a>';
        else {
          $out .= $modelMenuItems->getMenuItemAnchor($item,$siteSlug,0,$menuBaseUrl);
        }

        if(isset($item['sub_menu']) && $item['sub_menu']) $out .= $this->_getMainMenuItemsRecursiveList($item['sub_menu'],$siteSlug,1);

        $out .= '</li>';
      }
      $out .= '</ul>';
      return $out;

    } else return false;
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
