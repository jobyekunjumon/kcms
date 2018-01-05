<?php
require_once(APPLICATION_PATH . '/../library/WideImage/WideImage.php');
class Backoffice_MediaController extends Zend_Controller_Action {

  public function init() {
    /* Initialize action controller here */
    $layout = $this->_helper->layout();
    $layout->setLayout('layout_alte');

    $auth = Zend_Auth::getInstance();
    if ($auth->hasIdentity()) {
      $this->user = (array) $auth->getIdentity();
      unset($this->user['salt']);
      unset($this->user['password']);
      $this->view->user = $this->user;
    } else {
      $this->_helper->redirector('index', 'auth');
    }

    $this->utilities = new Application_Model_Utilities();
    $this->modelLog = new Application_Model_DbTable_Activitylog();
  }

  public function asyncGetMediaLibraryAction() { 
    $layout = $this->_helper->layout();
    $layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    $modelMedia = new Application_Model_DbTable_Media();

    $request = $this->getRequest();
    $post = $request->getPost();

    $serachCondition = '';
    $conj = ' WHERE ';
    if(isset($post['file_name']) && $post['file_name']) {
      $searchCondition .= $conj.' `file_name` LIKE "%'.$post['file_name'].'%"';
      $conj = ' AND ';
    }
    if(isset($post['date_from']) && $post['date_from']) {
      $searchCondition .= $conj.' `uploaded_on` >= "'.$post['date_from'].' 00:00:00"';
      $conj = ' AND ';
    }
    if(isset($post['date_to']) && $post['date_to']) {
      $searchCondition .= $conj.' `uploaded_on` <= "'.$post['date_to'].' 23:59:59"';
      $conj = ' AND ';
    }

    $start = 0; $pageLimit = 500;

    //compose query
    $sqlGetMedia = 'SELECT * FROM `media` ';
    $sqlGetMedia .= $searchCondition;
    $sqlGetMedia .= ' ORDER BY `uploaded_on` DESC LIMIT '.$start.', '.$pageLimit;

    $medias = $modelMedia->getAll('',$sqlGetMedia);

    if($medias) {
      $out = '';
      foreach($medias as $media) {
        $out .= '<div class="col-sm-2">';
        $out .= '<div class="hpanel">';
        $out .= '<div class="panel-body" style="padding:0; height:100px;">';
        $out .= '<img class="media_image" id="media_'.$media['id_media'].'" src="'.$media['file_directory'].'/'.$media['file_name'].'-smallsq.'.$media['file_extension'].'" width="100%" />';
        $out .= '</div>'; // panel body

        $out .= '</div>'; // hpanel
        $out .= '</div>'; // col
      }

      $out .= '<div style="clear:both;"></div>';
    } else {
      $out = $this->composeMessageHtml('No library entries found.','warning');
    }

    echo $out;
  }

  public function asyncGetMediaAction() {
    $layout = $this->_helper->layout();
    $layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    $modelMedia = new Application_Model_DbTable_Media();

    $request = $this->getRequest();
    $post = $request->getPost();

    if(isset($post['id_media']) && $post['id_media']) {
      $media = $modelMedia->getRowById($post['id_media']);
      if($media) {
        $ack['status'] = 'OK';
        $ack['data']['media'] = $media;
        exit(json_encode($ack));
      }
    }
    $ack['status'] = 'OK';
    $ack['data']['message'] = 'Could not fetch media details';
    exit(json_encode($ack));
  }

  public function asyncUpdateMediaAction() {
    $layout = $this->_helper->layout();
    $layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    $modelMedia = new Application_Model_DbTable_Media();

    $request = $this->getRequest();
    $post = $request->getPost();

    if(isset($post['id_media']) && $post['id_media']) {
      $media = $modelMedia->getRowById($post['id_media']);
      if($media && isset($post['alt_text']) && isset($post['caption'])) {
        if(($media['alt_text'] != $post['alt_text']) || $media['caption'] != $post['caption']) {
          $updateMedia = array('caption' => addslashes($post['caption']),'alt_text' => addslashes($post['alt_text']));
          if($modelMedia->updateData($updateMedia,$media['id_media'])) {
            $ack['status'] = 'OK';
            $ack['data']['media'] = $media;
            exit(json_encode($ack));
          } else {
            $ack['status'] = 'OK';
            $ack['data']['message'] = 'Something went wrong while updating media.';
            exit(json_encode($ack));
          }
        }

        $ack['status'] = 'OK';
        $ack['data']['media'] = $media;
        exit(json_encode($ack));
      }
    }


    $ack['status'] = 'OK';
    $ack['data']['message'] = 'Could not fetch media details';
    exit(json_encode($ack));
  }

  public function indexAction() {
    $this->view->activeMenuItem = 'media';
    $this->view->pageHeading = 'Media Library';
    $this->view->pageDescription = 'Manage Media';
    $breadcrumbs[0] = array('title' => 'Dashboard', 'link' => $this->view->baseUrl().'/backoffice', 'status' => '' );
    $breadcrumbs[1] = array('title' => 'Media Library', 'link' => '', 'status' => 'active' );
    $this->view->breadcrumbs = $breadcrumbs;

    $modelMedia = new Application_Model_DbTable_Media();

    $request = $this->getRequest();
    $get = $request->getQuery();


    if(isset($_FILES['media_file']) && $_FILES['media_file']) {
      $file = $_FILES['media_file'];

      $allowedExts = array('jpg','jpeg','JPG','JPEG','png','PNG','gif','GIF');
      $exts = explode(".", $file["name"]);
      $extension = end($exts);

      if (!in_array($extension, $allowedExts)) {
        $message = $this->utilities->composeMessageHtml('Invalid file extension. Could not upload file.','error');
      } else {
        $targetDir = $_SERVER['DOCUMENT_ROOT'].$this->view->baseUrl().'/uploads/'.date('Y').'/'.date('m').'/'.date('d').'/';
        $targetPath = $this->view->baseUrl().'/uploads/'.date('Y').'/'.date('m').'/'.date('d').'/';

        if (!is_dir($targetDir)) {
          mkdir($targetDir, 0777, true);
        }
        chmod($targetDir, 0777);

        $documentName = $file['name'];
        $fileTitle = basename($documentName,'.'.$extension);

        $filename = $targetDir.$documentName;
        $fileUrl = $targetPath.$documentName;

        if(file_exists($filename)) {
          $i = 1; $doRename = true;
          while($doRename) {
            $newTitle = $fileTitle.'('.$i++.')';
            $documentName = $newTitle.'.'.$extension;
            $filename = $targetDir.$documentName;
            if(!file_exists($filename)) {
            $doRename = false;
            }
          }

          $fileTitle = $newTitle;
        }

        // upload file
        if(move_uploaded_file($file['tmp_name'],$filename)) {
          chmod($filename, 0777);
          // resize images
          $wideImage = WideImage::load($filename);

          $imageTiny = $wideImage->resizeDown(75, 75, 'inside');
          $imageTinySquare = $wideImage->resizeDown(75, 75, 'fill');
          $imageSmall = $wideImage->resizeDown(150, 150, 'inside');
          $imageSmallSquare = $wideImage->resizeDown(150, 150, 'fill');
          $imageMedium = $wideImage->resizeDown(250, null, 'inside');

          $imageTiny->saveToFile($targetDir . $fileTitle.'-tiny.'.$extension);
          $imageTinySquare->saveToFile($targetDir . $fileTitle.'-tinysq.'.$extension);
          $imageSmall->saveToFile($targetDir . $fileTitle.'-small.'.$extension);
          $imageSmallSquare->saveToFile($targetDir . $fileTitle.'-smallsq.'.$extension);
          $imageMedium->saveToFile($targetDir . $fileTitle.'-medium.'.$extension);

          chmod($targetDir . '/'.$fileTitle.'-tiny.'.$extension, 0777);
          chmod($targetDir . '/'.$fileTitle.'-small.'.$extension , 0777);
          chmod($targetDir . '/'.$fileTitle.'-medium.'.$extension , 0777);

          // insert media
          $newMedia = array('media_type' => 'image','uploaded_on' => date('Y-m-d H:i:s'),
          'uploaded_by' => $this->user['id_admin_user'],'file_name' => $fileTitle,
          'file_type' => $file['type'],'file_extension' => $extension,
          'file_size' => $file['size'],'file_directory' => $targetPath,
          'file_url' => $fileUrl,'media_status' => 1,
          );

          if($modelMedia->insert($newMedia)) {
            $message = $this->utilities->composeMessageHtml('Media file uploaded successfully.','success');
          } else {
            $message = $this->utilities->composeMessageHtml('Something went wrong while registering uploaded media.','error');
          }
        } else {
          $message = $this->utilities->composeMessageHtml('Something went wrong while uploading media.','error');
        }

      }

    }


    $serachCondition = '';
    $conj = ' WHERE ';
    if(isset($get['file_name']) && $get['file_name']) {
      $searchCondition .= $conj.' `file_name` LIKE "%'.$get['file_name'].'%"';
      $conj = ' AND ';
    }
    if(isset($get['date_from']) && $get['date_from']) {
      $searchCondition .= $conj.' `uploaded_on` >= "'.$get['date_from'].' 00:00:00"';
      $conj = ' AND ';
    }
    if(isset($get['date_to']) && $get['date_to']) {
      $searchCondition .= $conj.' `uploaded_on` <= "'.$get['date_to'].' 23:59:59"';
      $conj = ' AND ';
    }

    // pagination
    $pageLimit = 18;
    if(isset($get['limit']) && $get['limit'] && is_numeric($get['limit']) ) $pageLimit = $get['limit'];
    $start = 0;
    $noRecords = 0;
    $resCountRecords = $modelMedia->getAll('','SELECT count(*) AS no_records FROM `media` '.$searchCondition);
    if(isset($resCountRecords[0]['no_records'])) $noRecords = $resCountRecords[0]['no_records'];
    if (isset($get['page'])){
      $page = intval($get['page']);
      $slNo = ($page-1)*$pageLimit +1;
    } else $slNo = 1;
    $tpages = ceil($noRecords / $pageLimit);
    if (!isset($page) || $page <= 0) $page = 1;
    $reload = $this->view->baseUrl() . '/backoffice/media/index?';
    $reload .= $this->utilities->getUrlParams($get, array('page'));
    $pagination = $this->utilities->paginate_two($reload, $page, $tpages, 2);
    $start = ($page - 1) * $pageLimit;


    //compose query
    $sqlGetMedia = 'SELECT * FROM `media` ';
    $sqlGetMedia .= $searchCondition;
    $sqlGetMedia .= ' ORDER BY `uploaded_on` DESC LIMIT '.$start.', '.$pageLimit;

    $medias = $modelMedia->getAll('',$sqlGetMedia);

    $urlParamStr = $this->utilities->getUrlParams($get, array('del', 'page'));

    if(isset($pagination) && $pagination) $this->view->pagination = $pagination;
    if(isset($message) && $message) $this->view->message = $message;
    if(isset($medias) && $medias) $this->view->medias = $medias;
    if(isset($get) && $get) $this->view->frmData = $get;
    if(isset($urlParamStr) && $urlParamStr) $this->view->urlParamStr = $urlParamStr;

  }


  public function editAction() {
    $this->view->activeMenuItem = 'media';
    $this->view->pageHeading = 'Media Library - Edit Media';
    $this->view->pageDescription = 'Edit Media';
    $breadcrumbs[0] = array('title' => 'Dashboard', 'link' => $this->view->baseUrl().'/backoffice', 'status' => '' );
    $breadcrumbs[1] = array('title' => 'Media Library', 'link' =>  $this->view->baseUrl().'/backoffice/media', 'status' => '' );
    $breadcrumbs[1] = array('title' => 'Edit Media', 'link' => '', 'status' => 'active' );
    $this->view->breadcrumbs = $breadcrumbs;

    $modelMedia = new Application_Model_DbTable_Media();

    $request = $this->getRequest();
    $get = $request->getQuery();

    if(isset($get['id_media']) && $get['id_media']) {
      $media = $modelMedia->getRowById($get['id_media']);
    }

    if(!$media) {
      $message = $this->utilities->composeMessageHtml('Could not fetch media details. Please try again later.','error');
    } else {
      $frmData = $media;
      if($request->isPost()) {
        $post = $request->getPost();
        $updateMedia = array('caption' => addslashes($post['caption']),
        'alt_text' => addslashes($post['alt_text']),
        'description' => addslashes($post['description']),
        );
        if($modelMedia->update($updateMedia,$media['id_media'])) {
          $message = $this->utilities->composeMessageHtml('Media updated successfully.','success');
          // REFETCH MEDIA TO REFLECT THE CHANGES IN THE FORM
          $frmData = $modelMedia->getRowById($media['id_media']);
        } else {
          $message = $this->utilities->composeMessageHtml('Something went wrong while updating media.','error');
        }
      }
    }

    if(isset($message) && $message) $this->view->message = $message;
    if(isset($media) && $media) $this->view->media = $media;
    if(isset($frmData) && $frmData) $this->view->frmData = $frmData;
    $this->view->serverUrl = $this->utilities->getServerUrl();
  }

  public function deleteAction() {
    $this->view->activeMenuItem = 'media';
    $this->view->pageHeading = 'Media Library - Delete Media';
    $this->view->pageDescription = 'Edit Media';
    $breadcrumbs[0] = array('title' => 'Dashboard', 'link' => $this->view->baseUrl().'/backoffice', 'status' => '' );
    $breadcrumbs[1] = array('title' => 'Media Library', 'link' =>  $this->view->baseUrl().'/backoffice/media', 'status' => '' );
    $breadcrumbs[1] = array('title' => 'Delete Media', 'link' => '', 'status' => 'active' );
    $this->view->breadcrumbs = $breadcrumbs;

    $modelMedia = new Application_Model_DbTable_Media();

    $request = $this->getRequest();
    $get = $request->getQuery();

    if(isset($get['id_media']) && $get['id_media']) {
      $media = $modelMedia->getRowById($get['id_media']);
    }

    if(!$media) {
      $message = $this->utilities->composeMessageHtml('Could not fetch media details. Please try again later.','error');
    } else {
      $tsUploaded = strtotime($media['uploaded_on']);
      $directory = '/uploads/'.date('Y',$tsUploaded).'/'.date('m',$tsUploaded).'/'.date('d',$tsUploaded).'/';
      // delete files
      $fileUrl = getCwd().$directory.$media['file_name'].'.'.$media['file_extension'];
      $thumbUrlTiny = getCwd().$directory.$media['file_name'].'-tiny.'.$media['file_extension'];
      $thumbUrlTinySq = getCwd().$directory.$media['file_name'].'-tinysq.'.$media['file_extension'];
      $thumbUrlSmall = getCwd().$directory.$media['file_name'].'-small.'.$media['file_extension'];
      $thumbUrlSmallSq = getCwd().$directory.$media['file_name'].'-smallsq.'.$media['file_extension'];
      $thumbUrlMedium = getCwd().$directory.$media['file_name'].'-medium.'.$media['file_extension'];

      // delete db entry
      if($modelMedia->deleteData($media['id_media'])) {
        unlink($fileUrl);
        unlink($thumbUrlTiny);unlink($thumbUrlTinySq);
        unlink($thumbUrlSmall);unlink($thumbUrlSmallSq);
        unlink($thumbUrlMedium);

        $this->_redirect('/backoffice/media');
      } else {
        $message = $this->utilities->composeMessageHtml('Something went wrong while deleting media. Please try again later.','error');
      }
    }
  }

  function getImageAction() {
    $layout = $this->_helper->layout();
    $layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    $serverUrl = $this->utilities->getServerUrl();
    $defImageUrl = $serverUrl.'/'.$this->view->baseUrl().'/images/noimage.jpg';

    $modelMedia = new Application_Model_DbTable_Media();
    $get = $this->getRequest()->getQuery();

    $outputType  = 'image'; $height=''; $width='';
    if(isset($get['type']) && $get['type'] == 'url') $outputType = 'url';
    if(isset($get['height']) && $get['height']) $height = $get['height'];
    if(isset($get['width']) && $get['width']) $width = $get['width'];
    if(isset($get['maxh']) && $get['maxh']) $maxHeight = $get['maxh'];
    if(isset($get['maxw']) && $get['maxw']) $maxWidth = $get['maxw'];

    if(isset($get['id_media']) && $get['id_media']) {
      $media = $modelMedia->getRowById($get['id_media']);
    }

    if(!$media) {
      if($outputType == 'image') exit('<img src="'.$defImageUrl.'" >');
      else exit($defImageUrl);
    }

    $mediaUrl = $this->utilities->getMediaUrl($media,1,'');

    $thumbUrl = $serverUrl.'/news/backoffice/media/imageurl?file='.$mediaUrl.'&width='.$width.'&height='.$height;
    if(isset($maxHeight) && $maxWidth) {
      $thumbUrl = $serverUrl.'/news/backoffice/media/imageurl?file='.$mediaUrl.'&maxw='.$maxWidth.'&maxh='.$maxHeight;
    }

    echo '<img src="'.$thumbUrl.'" >';
  }

  function imageurlAction() {
    $layout = $this->_helper->layout();
    $layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    $get = $this->getRequest()->getQuery();

    $sImagePath = $get["file"];

    $iThumbnailWidth = (int)$get['width'];
    $iThumbnailHeight = (int)$get['height'];
    $iMaxWidth = (int)$get["maxw"];
    $iMaxHeight = (int)$get["maxh"];

    if ($iMaxWidth && $iMaxHeight) $sType = 'scale';
    else if ($iThumbnailWidth && $iThumbnailHeight) $sType = 'exact';

    $img = NULL;

    $sExtComponents = explode('.', $sImagePath);
    $sExts = end($sExtComponents);
    $sExtension = strtolower($sExts);
    if ($sExtension == 'jpg' || $sExtension == 'jpeg') {
      $img = @imagecreatefromjpeg($sImagePath) or die("Cannot create new JPEG image");
    } else if ($sExtension == 'png') {
      $img = @imagecreatefrompng($sImagePath) or die("Cannot create new PNG image");
    } else if ($sExtension == 'gif') {
      $img = @imagecreatefromgif($sImagePath) or die("Cannot create new GIF image");
    }

    if ($img) {
      $iOrigWidth = imagesx($img);
      $iOrigHeight = imagesy($img);

      if ($sType == 'scale') {

        // Get scale ratio

        $fScale = min($iMaxWidth/$iOrigWidth,
        $iMaxHeight/$iOrigHeight);

        if ($fScale < 1) {

        $iNewWidth = floor($fScale*$iOrigWidth);
        $iNewHeight = floor($fScale*$iOrigHeight);

        $tmpimg = imagecreatetruecolor($iNewWidth,
         $iNewHeight);

        imagecopyresampled($tmpimg, $img, 0, 0, 0, 0,
        $iNewWidth, $iNewHeight, $iOrigWidth, $iOrigHeight);

        imagedestroy($img);
        $img = $tmpimg;
        }

      } else if ($sType == "exact") {

        $fScale = max($iThumbnailWidth/$iOrigWidth,
        $iThumbnailHeight/$iOrigHeight);

        if ($fScale < 1) {
          $iNewWidth = floor($fScale*$iOrigWidth);
          $iNewHeight = floor($fScale*$iOrigHeight);

          $tmpimg = imagecreatetruecolor($iNewWidth,
          $iNewHeight);
          $tmp2img = imagecreatetruecolor($iThumbnailWidth,
          $iThumbnailHeight);

          imagecopyresampled($tmpimg, $img, 0, 0, 0, 0,
          $iNewWidth, $iNewHeight, $iOrigWidth, $iOrigHeight);

          if ($iNewWidth == $iThumbnailWidth) {

            $yAxis = ($iNewHeight/2)-
            ($iThumbnailHeight/2);
            $xAxis = 0;

          } else if ($iNewHeight == $iThumbnailHeight)  {
            $yAxis = 0;
            $xAxis = ($iNewWidth/2)-
            ($iThumbnailWidth/2);
          }

          imagecopyresampled($tmp2img, $tmpimg, 0, 0,
                              $xAxis, $yAxis,
                              $iThumbnailWidth,
                              $iThumbnailHeight,
                              $iThumbnailWidth,
                              $iThumbnailHeight);

          imagedestroy($img);
          imagedestroy($tmpimg);
          $img = $tmp2img;
        }

      }

      header("Content-type: image/jpeg");
      imagejpeg($img);

    }
  }

  //************ HELPER FUNCTIONS ****************//

  function _compress($source, $destination, $quality) {
    $info = getimagesize($source);
    if ($info['mime'] == 'image/jpeg') $image = imagecreatefromjpeg($source);
    elseif ($info['mime'] == 'image/gif') $image = imagecreatefromgif($source);
    elseif ($info['mime'] == 'image/png') $image = imagecreatefrompng($source);
    if(imagejpeg($image, $destination, $quality)) return $destination;

    return false;
  }

  function _generateThumbnail($image, $width, $height) {
    if($image[0] != "/") { // Decide where to look for the image if a full path is not given
      if(!isset($_SERVER["HTTP_REFERER"])) { // Try to find image if accessed directly from this script in a browser
        $image = $_SERVER["DOCUMENT_ROOT"].implode("/", (explode('/', $_SERVER["PHP_SELF"], -1)))."/".$image;
      } else {
        $image = implode("/", (explode('/', $_SERVER["HTTP_REFERER"], -1)))."/".$image;
      }
    } else {
      $image = $_SERVER["DOCUMENT_ROOT"].$image;
    }

    $image_properties = getimagesize($image);
    $image_width = $image_properties[0];
    $image_height = $image_properties[1];
    $image_ratio = $image_width / $image_height;
    $type = $image_properties["mime"];

    if(!$width && !$height) {
      $width = $image_width;
      $height = $image_height;
    }
    if(!$width) {
      $width = round($height * $image_ratio);
    }
    if(!$height) {
      $height = round($width / $image_ratio);
    }

    if($type == "image/jpeg") {
      header('Content-type: image/jpeg');
      $thumb = imagecreatefromjpeg($image);
    } elseif($type == "image/png") {
      header('Content-type: image/png');
      $thumb = imagecreatefrompng($image);
    } else {
      return false;
    }

    $temp_image = imagecreatetruecolor($width, $height);
    imagecopyresampled($temp_image, $thumb, 0, 0, 0, 0, $width, $height, $image_width, $image_height);
    $thumbnail = imagecreatetruecolor($width, $height);
    imagecopyresampled($thumbnail, $temp_image, 0, 0, 0, 0, $width, $height, $width, $height);

    if($type == "image/jpeg") {
      imagejpeg($thumbnail);
    } else {
      imagepng($thumbnail);
    }

    imagedestroy($temp_image);
    imagedestroy($thumbnail);
  }

}
