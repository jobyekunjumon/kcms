<?php
class Application_Model_DbTable_Sliders extends Zend_Db_Table_Abstract
{
  protected $_name = 'sliders';
  protected $identityColumn = 'id_slider';

  function getSliders($idSite,$idPage,$contentsCount='') {
    $out = array();
    $sliders = $this->getAll(' WHERE `id_site` = '.$idSite.' AND (`id_page` = '.$idPage.' OR `id_page` = 0)');
    // generate slider contents
    if($sliders) {
      $modelSliderItems = new Application_Model_DbTable_Composer();
      $modelSliderItems->setTableName('slider_items');
      $modelSliderItems->setIdColumn('id_slider_item');

      foreach($sliders as $slider) {
        $sliderContent = '';
        $sliderItems = $modelSliderItems->getAll(' WHERE `id_slider` = '.$slider['id_slider']);

        if($sliderItems) {
          $sliderPagination = '';
          $sliderItemsContent = '';
          $defaultItemClass = 'active';
          $sliderItemCount = 0;
          foreach($sliderItems as $sliderItem) {
            // show or hide pagination
            if($slider['show_pagination']) $sliderPagination .= '<li data-target="#carousel_'.$slider['id_slider'].'" data-slide-to="'.$sliderItemCount++.'" class="'.$defaultItemClass.'"></li>';

            $sliderItemContent .= '<div class="item '.$defaultItemClass.'">'; $defaultItemClass = '';
              $sliderItemContent .= '<img src="'.$sliderItem['image_url'].'" alt="'.$sliderItem['alt_text'].'">';
              if($slider['show_item_description']) {
                $sliderItemContent .= '<div class="slider-data">';
                  $sliderItemContent .= $sliderItem['item_data'];
                $sliderItemContent .= '</div>';
              }
            $sliderItemContent .= '</div>';
          }

          $sliderContent = '<div id="carousel_'.$slider['id_slider'].'" class="carousel slide" data-ride="carousel">';

            $sliderContent .= '<ol class="carousel-indicators">';
              $sliderContent .= $sliderPagination;
            $sliderContent .= '</ol>';

            $sliderContent .= '<div class="carousel-inner">';
              $sliderContent .= $sliderItemContent;
            $sliderContent .= '</div>';
            // show or hide navigation
            if($slider['show_navigation']) {
              $sliderContent .= '<a class="left carousel-control" href="#carousel_'.$slider['id_slider'].'" data-slide="prev">';
                $sliderContent .= '<span class="glyphicon glyphicon-chevron-left"></span>';
                $sliderContent .= '<span class="sr-only">Previous</span>';
              $sliderContent .= '</a>';
              $sliderContent .= '<a class="right carousel-control" href="#carousel_'.$slider['id_slider'].'" data-slide="next">';
                $sliderContent .= '<span class="glyphicon glyphicon-chevron-right"></span>';
                $sliderContent .= '<span class="sr-only">Next</span>';
              $sliderContent .= '</a>';
            }

          $sliderContent .= '</div>';
        }


        $out[$contentsCount]['component_id'] = $slider['component_id'];
        $out[$contentsCount]['content_type'] = 'slider';
        $out[$contentsCount]['content'] = $sliderContent;
        $out[$contentsCount]['id_content'] = $slider['id_slider'];
        $contentsCount++;
      }
    }

    return $out;
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

  public function getSite($siteSlug) {
    if(!$siteSlug) return false;
    $site = $this->getRowByCondition(' `site_slug` = "'.addslashes(trim($siteSlug)).'" ');
    return $site;
  }
  public function authenticateEditing($get,$user) {
    if(!isset($get['site']) || !$get['site']) return false;

    if(!isset($user['id_user']) || !$user['id_user']) return false;

    $site = $this->getRowByCondition(' `site_slug` = "'.addslashes(trim($get['site'])).'" AND `id_user` = '.$user['id_user']);
    if(!$site) return false;

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
      $sql = 'SELECT COUNT(*)  FROM `'.$this->_name.'` '.$order;
	  //get dbAdapter instance
      $dbAdapter = Zend_Db_Table::getDefaultAdapter();
      $rows = $dbAdapter->fetchAll($sql);
	  if(isset($rows[0]['COUNT(*)'])) return $rows[0]['COUNT(*)'];
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


}
