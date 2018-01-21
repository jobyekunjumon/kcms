<?php
class Application_Model_DbTable_Media extends Zend_Db_Table_Abstract
{
    protected $_name = 'media';
    protected $identityColumn = 'id_media';

function __construct() {
  $this->utilities = new Application_Model_Utilities();
  $this->view = new Zend_View();
}
function getMedia($idSite,$idPage,$contentsCount='') {
  $out = array();
  $sqlGetSiteMedia = 'SELECT sm.`component_id`, sm.`alt_text` as altered_alt_text, m.`id_media`, m.`file_name`, m.`file_type`,
                             m.`file_extension`,m.`file_directory`, m.`file_url`, m.`caption`, m.`alt_text`,sm.`id_site_media`, sm.`media_size_width`, sm.`media_size_height`,sm.`thumbnail`
                             FROM `site_media` sm, `media` m
                             WHERE m.`id_media` = sm.`id_media` AND sm.`id_site` = '.$idSite.' AND (sm.`id_page` = '.$idPage.' OR sm.`id_page` = 0)
                             AND sm.`site_media_status` = 1 AND m.`media_status` =1';
  $siteMedia = $this->getAll('',$sqlGetSiteMedia);
  if($siteMedia) {
    foreach ($siteMedia as $media) {
      $mediaContent = '';
      if($media) {
        $out[$contentsCount]['component_id'] = $media['component_id'];
        $out[$contentsCount]['content_type'] = 'image';
        $out[$contentsCount]['content'] = $this->_getImage($media);
        $out[$contentsCount++]['id_content'] = $media['id_site_media'];
      }
    }
  }

  return $out;
}

function _getImage($media,$outputType='image',$maxHeight='',$maxWidth='') {

  $serverUrl = $this->utilities->getServerUrl();
  $defImageUrl = $serverUrl.'/'.$this->view->baseUrl().'/images/noimage.jpg';

  $modelMedia = new Application_Model_DbTable_Media();

  if(!$media) {
    if($outputType == 'image') exit('<img src="'.$defImageUrl.'" >');
    else exit($defImageUrl);
  }

  $mediaUrl = $this->utilities->getMediaUrl($media,1,$media['thumbnail']);

  // if($media['media_size_width'] || $media['media_size_height'] ) {
  //   $mediaUrl = $serverUrl.'/'.$this->view->baseUrl().'/mds/imageurl?file='.$mediaUrl.'&width='.$width.'&height='.$height;
  //   if(isset($maxHeight) && $maxWidth) {
  //     $mediaUrl = $serverUrl.'/news/backoffice/media/imageurl?file='.$mediaUrl.'&maxw='.$maxWidth.'&maxh='.$maxHeight;
  //   }
  // } else if(isset($maxHeight) && $maxWidth) {
  //   $mediaUrl = $serverUrl.'/news/backoffice/media/imageurl?file='.$mediaUrl.'&maxw='.$maxWidth.'&maxh='.$maxHeight;
  // }
  if($outputType == 'url') return $mediaUrl;

  if($media['media_size_width'] || $media['media_size_height'] ) {
    $mediaSizeWidth = (isset($media['media_size_width']) && $media['media_size_width'])?$media['media_size_width']:'';
    $mediaSizeHeight = (isset($media['media_size_height']) && $media['media_size_height'])?$media['media_size_height']:'';
    $out = '<img src="'.$mediaUrl.'"';
      $out .= ' width="'.$mediaSizeWidth.'" height="'.$mediaSizeHeight.'" ';
      if(isset($media['altered_alt_text'])) $out .= 'alt="'.$media['altered_alt_text'].'"';
      else if(isset($media['alt_text'])) $out .= 'alt="'.$media['alt_text'].'"';
      else if(isset($media['caption'])) $out .= 'alt="'.$media['caption'].'"';
      else  $out .= 'alt="Image '.$media['id_media'].'"';
    $out .=  ' />';
    return $out;
  }

  // create image tag
  $out = '<img src="'.$mediaUrl.'"';
    if(isset($media['altered_alt_text'])) $out .= 'alt="'.$media['altered_alt_text'].'"';
    else if(isset($media['alt_text'])) $out .= 'alt="'.$media['alt_text'].'"';
    else if(isset($media['caption'])) $out .= 'alt="'.$media['caption'].'"';
    else  $out .= 'alt="Image '.$media['id_media'].'"';
  $out .=  ' />';
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
