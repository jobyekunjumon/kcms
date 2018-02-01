<?php
class User_ProcessLiveEditController extends Zend_Controller_Action {

  protected $siteSlug = '';

  public function init() {
    $layout = $this->_helper->layout();
    $layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    $auth = Zend_Auth::getInstance();
    if ($auth->hasIdentity()) {
      $this->user = (array) $auth->getIdentity();
    } else {
      exit('Could not authenticate user.');
    }

    $this->utilities = new Application_Model_Utilities();
  }

  public function saveTextAction() {
    $request = $this->getRequest();
    $post = $request->getPost();

    $out = array();
    // validate input
    if(!isset($post['id_content']) || !$post['id_content'] || !isset($post['content'])) {
      $out['status'] = 0;
      $out['message'] = 'Something went wrong while saving your data. Please try again after refreshing the page.';
      exit(json_encode($out));
    }

    $modelContents = new Application_Model_DbTable_Contents();
    // fetch content
    $content = $modelContents->getRowById($post['id_content']);
    if(!$content) {
      $out['status'] = 0;
      $out['message'] = 'Something went wrong while saving your data. Please try again after refreshing the page.';
      exit(json_encode($out));
    }

    // fetch site entry and verify content owner
    $modelSites = new Application_Model_DbTable_Sites();
    $site = $modelSites->getRowByCondition(' `id_site` = '.$content['id_site'].' AND `id_user` = '.$this->user['id_user']);
    if(!$site) {
      $out['status'] = 0;
      $out['message'] = 'You are not allowed to change this content. Please login to edit the content.';
      exit(json_encode($out));
    }

    // update content
    $updateContent = array('content' => addslashes($post['content']));
    if($modelContents->updateData($updateContent,$post['id_content'])) {
      $out['status'] = 1;
      $out['message'] = 'Success';
      exit(json_encode($out));
    }

    $out['status'] = 0;
    $out['message'] = 'Something went wrong while saving your data. Please try again after refreshing the page.';
    exit(json_encode($out));

  }

  public function updateSiteMediaAction() {
    $request = $this->getRequest();
    $post = $request->getPost();

    $out = array();
    // validate input
    if(!isset($post['id_content']) || !$post['id_content'] || !isset($post['id_media']) || !$post['id_media']) {
      $out['status'] = 0;
      $out['message'] = 'Something went wrong while saving your data. Please try again after refreshing the page.';
      exit(json_encode($out));
    }

    $modelSiteMedia = new Application_Model_DbTable_Sitemedia();
    // fetch content
    $content = $modelSiteMedia->getRowById($post['id_content']);
    if(!$content) {
      $out['status'] = 0;
      $out['message'] = 'Something went wrong while saving your data. Please try again after refreshing the page.';
      exit(json_encode($out));
    }

    // fetch site entry and verify content owner
    $modelSites = new Application_Model_DbTable_Sites();
    $site = $modelSites->getRowByCondition(' `id_site` = '.$content['id_site'].' AND `id_user` = '.$this->user['id_user']);
    if(!$site) {
      $out['status'] = 0;
      $out['message'] = 'You are not allowed to change this content. Please login to edit the content.';
      exit(json_encode($out));
    }

    // update content
    $updateContent = array('id_media' => addslashes($post['id_media']));
    if(isset($post['alt_text'])) $updateContent['alt_text'] = $post['alt_text'];
    if(isset($post['thumbnail']) && $post['thumbnail'] != "custom") $updateContent['thumbnail'] = $post['thumbnail'];
    else {
       $updateContent['thumbnail'] = '';
       if(isset($post['media_size_width']) && $post['media_size_width']) $updateContent['media_size_width'] = $post['media_size_width'];
       if(isset($post['media_size_height']) && $post['media_size_height'] ) $updateContent['media_size_height'] = $post['media_size_height'];
    }


    if($modelSiteMedia->updateData($updateContent,$post['id_content'])) {
      $modelMedia = new Application_Model_DbTable_Media();
      $media = $modelMedia->getRowById($post['id_media']);
      $content = $modelSiteMedia->getRowById($post['id_content']);
      $media = array_merge($media,$content);
      $image = $modelMedia->_getImage($media,'image');
      $out['status'] = 1;
      $out['message'] = 'Success';
      $out['updated_content'] = $image;
      exit(json_encode($out));
    }

  }

  public function getSliderImagesAction() {
    $request = $this->getRequest();
    $post = $request->getPost();

    $out = array();
    // validate input
    if(!isset($post['id_slider']) || !$post['id_slider']) {
      $out['status'] = 0;
      $out['message'] = 'Something went wrong while saving your data. Please try again after refreshing the page.';
      exit(json_encode($out));
    }

    $modelSliders = new Application_Model_DbTable_Sliders();
    // fetch content
    $slider = $modelSliders->getRowById($post['id_slider']);
    if(!$slider) {
      $out['status'] = 0;
      $out['message'] = 'Something went wrong while saving your data. Please try again after refreshing the page.';
      exit(json_encode($out));
    }

    // fetch site entry and verify content owner
    $modelSites = new Application_Model_DbTable_Sites();
    $site = $modelSites->getRowByCondition(' `id_site` = '.$slider['id_site'].' AND `id_user` = '.$this->user['id_user']);
    if(!$site) {
      $out['status'] = 0;
      $out['message'] = 'You are not allowed to view this content. Please login to edit the content.';
      exit(json_encode($out));
    }

    // get slider images
    $modelSliderItems = new Application_Model_DbTable_Slideritems();
    $sliderItems = $modelSliderItems->getAll(' WHERE `id_slider` = '.$slider['id_slider']);

    if($sliderItems) {
      $modelMedia = new Application_Model_DbTable_Media();
      $out['status'] = 1;
      $out['content'] = '';

      foreach($sliderItems as $item) {
        // get media
        $media = $modelMedia->getRowById($item['id_media']);
        $mediaUrl = $this->utilities->getMediaUrl($media,1,'small');
      	$out['content'] .= '<div id="featured_img_box_'.$item['id_media'].'" class="row"  style="border:1px dashed #2d2d2d; margin:5px 0; padding:2px;" >';
          $out['content'] .= '<div class="col-sm-4" >';
            $out['content'] .= '<img class="featured_image" id="featured_image_'.$item['id_media'].'" src="'.$mediaUrl.'" style="max-width:100%" >';
      			$out['content'] .= '<br><a style="cursor:pointer" class="remove_featured text-danger" id="remove_featured_'.$item['id_media'].'" ><i class="fa fa-times" ></i> Remove</a>';
      			$out['content'] .= '<input type="hidden" value="'.$item['id_media'].'" id="featured_images_'.$item['id_media'].'" name="featured_images[]">';
          $out['content'] .= '</div>';
          $out['content'] .= '<div class="col-sm-8" >';

            $out['content'] .= '<div class="form-group">';
              $out['content'] .= '<label class="">Alt Text</label>';
              $out['content'] .= '<input name="alt_text[]" class="form-control" value="'.$item['alt_text'].'" id="alt_text_'.$item['id_media'].'" placeholder="Alt Text" type="text">';
            $out['content'] .= '</div>';

            $out['content'] .= '<div class="form-group">';
              $out['content'] .= '<label class="">Slider Caption</label>';
              $out['content'] .= '<textarea name="item_data[]" class="form-control item_data"  id="item_data_'.$item['id_media'].'" placeholder="Slider caption" >'.$item['item_data'].'</textarea>';
            $out['content'] .= '</div>';

          $out['content'] .= '</div>';
        $out['content'] .= '</div>'; // row
      }
      exit(json_encode($out));
    }

    $out['status'] = 0;
    $out['slider_items'] = 'No images found for this slider.';
    exit(json_encode($out));
  }

  public function updateSliderAction() {
    $request = $this->getRequest();
    $post = $request->getPost();

    $out = array();
    // validate input
    if(!isset($post['id_slider']) || !$post['id_slider']) {
      $out['status'] = 0;
      $out['message'] = 'Something went wrong while saving your data. Please try again after refreshing the page.';
      exit(json_encode($out));
    }

    if(!isset($post['featured_images']) || !$post['featured_images']) {
      $out['status'] = 0;
      $out['message'] = 'Please select at least one image to add to slider.';
      exit(json_encode($out));
    }

    $modelSliders = new Application_Model_DbTable_Sliders();
    // fetch content
    $slider = $modelSliders->getRowById($post['id_slider']);
    if(!$slider) {
      $out['status'] = 0;
      $out['message'] = 'Something went wrong while saving your data. Please try again after refreshing the page.';
      exit(json_encode($out));
    }

    // fetch site entry and verify content owner
    $modelSites = new Application_Model_DbTable_Sites();
    $site = $modelSites->getRowByCondition(' `id_site` = '.$slider['id_site'].' AND `id_user` = '.$this->user['id_user']);
    if(!$site) {
      $out['status'] = 0;
      $out['message'] = 'You are not allowed to view this content. Please login to edit the content.';
      exit(json_encode($out));
    }

    // update slider
    $showPagination = 0; $showNavigation = 0; $showItemDescription = 0;
    if(isset($post['show_pagination']) && $post['show_pagination']) $showPagination = 1;
    if(isset($post['show_navigation']) && $post['show_navigation']) $showNavigation = 1;
    if(isset($post['show_item_description']) && $post['show_item_description']) $showItemDescription = 1;
    $updateSlider = array( 'show_pagination' => $showPagination,
                        'show_navigation' => $showNavigation,
                        'show_item_description' => $showItemDescription
                       );
    @$modelSliders->updateData($updateSlider,$slider['id_slider']);

    // delete existing slider items and then add new slider item
    $modelSliderItems = new Application_Model_DbTable_Slideritems();
    $sqlDeleteSliderItems = 'DELETE FROM `slider_items` WHERE `id_slider` = '.$slider['id_slider'];
    if(!$modelSliderItems->execute($sqlDeleteSliderItems)) {
      $out['status'] = 0;
      $out['message'] = 'Something went wrong while saving your data. Please try again after refreshing the page.';
      exit(json_encode($out));
    }

    $modelMedia = new Application_Model_DbTable_Media();
    $i = 0;
    foreach($post['featured_images'] as $key=>$mediaId) {
      // get media
      $media = $modelMedia->getRowById($mediaId);
      $imageUrl = $this->_getImage($media,'url');
      $altText = (isset($post['alt_text'][$i]) && $post['alt_text'][$i])?addslashes($post['alt_text'][$i]):'';
      $caption = (isset($post['item_data'][$i]) && $post['item_data'][$i])?addslashes($post['item_data'][$i++]):'';

      $newSliderItem = array('id_slider' => $slider['id_slider'],
                          'id_media' => $mediaId,
                          'image_url' => $imageUrl,
                          'alt_text' => $altText,
                          'item_data' => $caption
                         );
      @$modelSliderItems->insertData($newSliderItem);
    }
    $out['status'] = 1;
    $out['message'] = 'Success';
    $out['site'] = $post['site'];
    $out['page'] = $post['page'];
    exit(json_encode($out));
  }

  public function getMenuItemsAction() {
    $request = $this->getRequest();
    $post = $request->getPost();

    $out = '';
    // validate input
    if(!isset($post['id_menu']) || !$post['id_menu']) {
      $out['status'] = 0;
      $out['message'] = 'Something went wrong while saving your data. Please try again after refreshing the page.';
      exit(json_encode($out));
    }

    $modelMenu = new Application_Model_DbTable_Menu();
    // fetch content
    $menu = $modelMenu->getRowById($post['id_menu']);
    if(!$menu) {
      $out['status'] = 0;
      $out['message'] = 'Something went wrong while saving your data. Please try again after refreshing the page.';
      exit(json_encode($out));
    }

    // fetch site entry and verify content owner
    $modelSites = new Application_Model_DbTable_Sites();
    $site = $modelSites->getRowByCondition(' `id_site` = '.$menu['id_site'].' AND `id_user` = '.$this->user['id_user']);
    if(!$site) {
      $out['status'] = 0;
      $out['message'] = 'You are not allowed to view this content. Please login to edit the content.';
      exit(json_encode($out));
    }


    $modelMenuItems = new Application_Model_DbTable_Menuitems();
    $menuItemsHirarchy = $modelMenuItems->getMenuitemsHierarchy(0,' `id_menu` = '.$menu['id_menu'].' AND `menu_item_status` = 1');
    $menuContent = $this->_getMainMenuItemsNestableRecursiveList($menuItemsHirarchy,$site['site_slug'],0,1);

    $out .= '<div class="col col-md-8 col-sm-8" >';
      $out .= '<div class="dd" id="nestable">';
        $out .= $menuContent;
      $out .= '</div>';
      $out .= '<input type="hidden" name="menu_order" id="nestable-output" />';

    $out .= '</div>'; // col

    $out .= '<div class="col col-md-4 col-sm-4" id="menu_item_details_cont" style="border-left:2px solid #ccc;">';
    $out .= '</div>'; // col

    exit($out);
  }

  public function getEditMenuFormAction() {
    $request = $this->getRequest();
    if(!$request->isPost()) exit('Could not find any data.');
    $post = $request->getPost();
    if(!isset($post['id_site']) || !$post['id_site']) exit('Could not fetch site details.');

    $modelSites = new Application_Model_DbTable_Composer();
    $modelSites->setTableName('sites');
    $modelSites->setIdColumn('id_site');
    $site = $modelSites->getRowByCondition(' `id_site` = "'.$post['id_site'].'" AND `id_user` = '.$this->user['id_user']);
    if(!$site) exit('Could not fetch site details.');

    $menuItem = array();
    $modelMenuItems = new Application_Model_DbTable_Menuitems();
    if(isset($post['id_menu_item']) && $post['id_menu_item']) $menuItem = $modelMenuItems->getRowById($post['id_menu_item']);
    if(!$menuItem) {
      $menuItem['id_menu_item'] = ''; $menuItem['title'] = '';$menuItem['target'] = '';
      $menuItem['link_type'] = ''; $menuItem['internal_target_name'] = '';$menuItem['external_link'] = '';
      $menuItem['page_id'] = '';
    }

    // process add or edit menu item
    if(isset($post['action']) && $post['action'] == "add_menu_item") {
      $modelPages = new Application_Model_DbTable_Composer();
      $modelPages->setTableName('pages');
      $modelPages->setIdColumn('id_page');

      if(isset($post['id_to_edit']) && $post['id_to_edit'] && $menuItem) {
        $menuItemPageId = 0;
        $menuItemPageSlug = '';
        $menuItemExternalLink = '';
        $menuItemInternalTargetName = '';
        if($post['link_type'] == 'page_link') {
          $selectedPage = $modelPages->getRowById($post['link_type_attribute']);
          if($selectedPage) {
            $menuItemPageSlug = $selectedPage['page_slug'];
            $menuItemPageId = $selectedPage['id_page'];
          }
        } else if($post['link_type'] == 'internal_page_link') {
          $menuItemInternalTargetName = $post['link_type_attribute'];
        } else if($post['link_type'] == 'external') {
          $menuItemExternalLink = $post['link_type_attribute'];
        }
        // insert default menu item
        $updateMenuItem = array(
                          'id_menu' => $post['id_menu'],
                          'title' => $post['title'],
                          'menu_slug' => strtolower(str_replace(' ','_',$post['title'])),
                          'target' => $post['target'],
                          'link_type' => $post['link_type'],
                          'page_id' => $menuItemPageId,
                          'page_slug' => $menuItemPageSlug,
                          'internal_target_name' => $menuItemInternalTargetName,
                          'external_link' => $menuItemExternalLink,
                          'menu_item_status' => 1,
                         );

        if($modelMenuItems->updateData($updateMenuItem,$post['id_to_edit'])) {
          $menuItem = $modelMenuItems->getRowById($post['id_to_edit']);
        }
      } else {
        $menuItemPageId = 0;
        $menuItemPageSlug = '';
        $menuItemExternalLink = '';
        $menuItemInternalTargetName = '';
        if($post['link_type'] == 'page_link') {
          $selectedPage = $modelPages->getRowById($post['link_type_attribute']);
          if($selectedPage) {
            $menuItemPageSlug = $selectedPage['page_slug'];
            $menuItemPageId = $selectedPage['id_page'];
          }
        } else if($post['link_type'] == 'internal_page_link') {
          $menuItemInternalTargetName = $post['link_type_attribute'];
        } else if($post['link_type'] == 'external') {
          $menuItemExternalLink = $post['link_type_attribute'];
        }
        // insert default menu item
        $newMenuItem = array('id_parent_menu_item' => 0,
                          'id_menu' => $post['id_menu'],
                          'title' => $post['title'],
                          'menu_slug' => strtolower(str_replace(' ','_',$post['title'])),
                          'target' => $post['target'],
                          'link_type' => $post['link_type'],
                          'page_id' => $menuItemPageId,
                          'page_slug' => $menuItemPageSlug,
                          'internal_target_name' => $menuItemInternalTargetName,
                          'external_link' => $menuItemExternalLink,
                          'menu_item_status' => 1,
                          'sort_order' => $modelMenuItems->getNextSortOrder($post['id_menu'],0),
                         );
        if($newMenuItemId = $modelMenuItems->insertData($newMenuItem)){
          $menuItem = $modelMenuItems->getRowById($newMenuItemId);
        }
      }
    }

    // if id_menu_item preset, get form as edit form, else as new item form
    if($menuItem['id_menu_item']) {
      $menuBaseUrl = $this->view->baseUrl().'/user/edit-site/show-site';
      $pageLink = $modelMenuItems->getMenuItemLink($menuItem,$site['site_slug'],0,$menuBaseUrl);
      $out = '<h4>Edit menu item <a href="'.$pageLink.'" class="btn bg-aqua btn-xs pull-right" target="_blank"><i class="fa fa-link"></i> View page</a></h4>';
    } else {
      $out = '<h4>Add new menu item </h4>';
    }
    $out .= '<input type="hidden" name="id_to_edit" id="id_to_edit" value="'.$menuItem['id_menu_item'].'" />';
    $out .= '<div class="form-group">';
      $out .= '<label>Title</label>';
      $out .= '<input name="title" id="title" class="form-control" placeholder="Title" type="text" value="'.$menuItem['title'].'">';
    $out .= '</div>';

    $out .= '<div class="form-group">';
      $out .= '<label>Open </label>';
      $out .= '<select class="form-control" name="target" id="target">';
        $out .= '<option value="" ';
          if($menuItem['target'] == "") $out .= 'selected="selected"';
        $out .= '>In same window</option>';

        $out .= '<option value="_blank" ';
          if($menuItem['target'] == "_blank") $out .= 'selected="selected"';
        $out .= '>In new window</option>';
      $out .= '</select>';
    $out .= '</div>';

    $out .= '<div class="form-group">';
      $out .= '<label>Link Type</label>';
      $out .= '<select class="form-control link_type_specifier_input" name="link_type" id="link_type-'.$menuItem['id_menu_item'].'">';
        $out .= '<option value=""';
          if($menuItem['link_type'] == "") $out .= 'selected="selected"';
        $out .= '>Select type</option>';

        // $out .= '<option value="internal_page_link"';
        //   if($menuItem['link_type'] == "internal_page_link") $out .= 'selected="selected"';
        // $out .= '>Link to same page</option>';

        $out .= '<option value="page_link"';
          if($menuItem['link_type'] == "page_link") $out .= 'selected="selected"';
        $out .= '>Link to another page</option>';

        $out .= '<option value="external"';
          if($menuItem['link_type'] == "external") $out .= 'selected="selected"';
        $out .= '>External link</option>';
      $out .= '</select>';
    $out .= '</div>';

    $out .= '<div class="" id="menu_item_attribute_container">';
      if($menuItem['link_type'] == "internal_page_link") {
        $out .= '<div class="form-group">';
          $out .= '<label>Target Id</label>';
          $out .= '<input name="link_type_attribute" id="link_type_attribute" class="form-control" type="text" value="'.$menuItem['internal_target_name'].'">';
        $out .= '</div>';
      } else if($menuItem['link_type'] == "external") {
        $out .= '<div class="form-group">';
          $out .= '<label>External Link</label>';
          $out .= '<input name="link_type_attribute" id="link_type_attribute" class="form-control" type="text" value="'.$menuItem['external_link'].'">';
        $out .= '</div>';
      } else if($menuItem['link_type'] == "page_link") {
        $modelPages = new Application_Model_DbTable_Composer();
        $modelPages->setTableName('pages');
        $modelPages->setIdColumn('id_page');
        $pages = $modelPages->getAll(' WHERE `id_site` = "'.$site['id_site'].'" AND `page_status` = 1 ');

        $out .= '<div class="form-group">';
          $out .= '<label>Page</label>';
          $out .= '<select name="link_type_attribute" id="link_type_attribute" class="form-control">';
            if($pages) {
              foreach($pages as $page) {
                $out .= '<option value="'.$page['id_page'].'"';
                if(isset($menuItem['page_id']) && $menuItem['page_id'] == $page['id_page'] ) $out .= ' selected="selected"';
                $out .= '>'.$page['page_title'].'</option>';
              }
            }
          $out .= '</select>';
        $out .= '</div>';
      }
    $out .= '</div>';
    if(isset($post['form_specifier']) && $post['form_specifier'] == 'external_popup') {
      $out .= '';
    } else {
      $out .= '<div class="form-group">';
        $out .= '<button type="button" id="btn_save_menu_item_changes" class="btn btn-success btn-sm" ><i class="fa fa-save" ></i> Save Chanages</button>';
      $out .= '</div>';
    }

    exit($out);
  }

  public function getLinktypeSpecifierInputAction() {

    $request = $this->getRequest();
    if(!$request->isPost()) exit('Could not find any data.');
    $post = $request->getPost();
    if(!isset($post['id_site']) || !$post['id_site']) exit('Could not fetch site details.');

    $modelSites = new Application_Model_DbTable_Composer();
    $modelSites->setTableName('sites');
    $modelSites->setIdColumn('id_site');
    $site = $modelSites->getRowByCondition(' `id_site` = "'.$post['id_site'].'" AND `id_user` = '.$this->user['id_user']);
    if(!$site) exit('Could not fetch site details.');

    if($post['id_menu_item']) {
      $modelMenuItems = new Application_Model_DbTable_Menuitems();
      $menuItem = $modelMenuItems->getRowById($post['id_menu_item']);
      if(!$menuItem) exit('Could not fetch menu item details.');
    }

    $linkType = (isset($post['link_type']) && $post['link_type']) ? $post['link_type'] : '';

    if($linkType == "internal_page_link") {
      $attributeValue = (isset($menuItem['internal_target_name']) && $menuItem['internal_target_name']) ? $menuItem['internal_target_name'] : '';
      $out .= '<div class="form-group">';
        $out .= '<label>Target Id</label>';
        $out .= '<input name="link_type_attribute" id="link_type_attribute" class="form-control" type="text" value="'.$attributeValue.'">';
      $out .= '</div>';
    } else if($linkType == "external") {
      $attributeValue = (isset($menuItem['external_link']) && $menuItem['external_link']) ? $menuItem['external_link'] : '';
      $out .= '<div class="form-group">';
        $out .= '<label>External Link</label>';
        $out .= '<input name="link_type_attribute" id="link_type_attribute" class="form-control" type="text" value="'.$attributeValue.'">';
      $out .= '</div>';
    } else if($linkType == "page_link") {
      $modelPages = new Application_Model_DbTable_Composer();
      $modelPages->setTableName('pages');
      $modelPages->setIdColumn('id_page');
      $pages = $modelPages->getAll(' WHERE `id_site` = "'.$site['id_site'].'" AND `page_status` = 1 ');

      $out .= '<div class="form-group">';
        $out .= '<label>Page</label>';
        $out .= '<select name="link_type_attribute" id="link_type_attribute" class="form-control">';
          if($pages) {
            foreach($pages as $page) {
              $out .= '<option value="'.$page['id_page'].'"';
              if(isset($menuItem['page_id']) && $menuItem['page_id'] == $page['id_page'] ) $out .= ' selected="selected"';
              $out .= '>'.$page['page_title'].'</option>';
            }
          }
        $out .= '</select>';
      $out .= '</div>';
    } else {
      $out .= '<div class="form-group">';
        $out .= '<label>Link</label>';
        $out .= '<input name="link_type_attribute" id="link_type_attribute" class="form-control" type="text" value="">';
      $out .= '</div>';
    }

    exit($out);
  }

  public function updateMenuOrderAction() {

    $request = $this->getRequest();
    if(!$request->isPost()) exit('Could not find any data.');
    $post = $request->getPost();
    if(!isset($post['id_site']) || !$post['id_site']) exit('Could not fetch site details.');
    if(!isset($post['id_menu']) || !$post['id_menu']) exit('Could not fetch menu details.');
    if(!isset($post['menu_order']) || !$post['menu_order']) exit('Could not find menu order.');

    $modelSites = new Application_Model_DbTable_Composer();
    $modelSites->setTableName('sites');
    $modelSites->setIdColumn('id_site');
    $site = $modelSites->getRowByCondition(' `id_site` = "'.$post['id_site'].'" AND `id_user` = '.$this->user['id_user']);
    if(!$site) exit('Could not fetch site details.');

    $menuOrder = $post['menu_order'];
    $menuOrder = (array)json_decode($menuOrder);
    if($this->_updateMenuOrder($menuOrder,$post['id_menu'],1)) exit('1');
    exit(0);
  }

  public function deleteMenuItemAction() {

    $request = $this->getRequest();
    if(!$request->isPost()) exit('Could not find any data.');
    $post = $request->getPost();
    if(!isset($post['id_site']) || !$post['id_site']) exit('Could not fetch site details.');
    if(!isset($post['id_menu_item']) || !$post['id_menu_item']) exit('Could not fetch menu item details.');

    $modelSites = new Application_Model_DbTable_Composer();
    $modelSites->setTableName('sites');
    $modelSites->setIdColumn('id_site');
    $site = $modelSites->getRowByCondition(' `id_site` = "'.$post['id_site'].'" AND `id_user` = '.$this->user['id_user']);
    if(!$site) exit('Could not fetch site details.');

    $modelMenuItems = new Application_Model_DbTable_Menuitems();
    if($modelMenuItems->deleteData($post['id_menu_item'])) exit('1');
    exit('0');
  }

  public function addPageAction() {
    $out = array();

    $request = $this->getRequest();
    if(!$request->isPost()) {
      $out['status'] = 0;
      $out['message'] = '<span style="color:red;">Could not find any input data.</div>';
      exit(json_encode($out));
    }
    $post = $request->getPost();
    if(!isset($post['id_site']) || !$post['id_site']) {
      $out['status'] = 0;
      $out['message'] = '<span style="color:red;">&nbsp;&nbsp;<i class="fa fa-times"></i> Could not fetch site details.</span>';
      exit(json_encode($out));
    }
    if(!isset($post['id_menu']) || !$post['id_menu']) {
      $out['status'] = 0;
      $out['message'] = '<span style="color:red;">&nbsp;&nbsp;<i class="fa fa-times"></i> Could not fetch menu details.</span>';
      exit(json_encode($out));
    }
    if(!isset($post['page_title']) || !$post['page_title']) {
      $out['status'] = 0;
      $out['message'] = '<span style="color:red;">&nbsp;&nbsp;<i class="fa fa-times"></i> Please enter a page title.</span>';
      exit(json_encode($out));
    }
    if(!isset($post['page_layout']) || !$post['page_layout']) {
      $out['status'] = 0;
      $out['message'] = '<span style="color:red;">&nbsp;&nbsp;<i class="fa fa-times"></i> Please select a page layout.</span>';
      exit(json_encode($out));
    }

    $modelSites = new Application_Model_DbTable_Composer();
    $modelSites->setTableName('sites');
    $modelSites->setIdColumn('id_site');
    $site = $modelSites->getRowByCondition(' `id_site` = "'.$post['id_site'].'" AND `id_user` = '.$this->user['id_user']);
    if(!$site) {
      $out['status'] = 0;
      $out['message'] = '<span style="color:red;">&nbsp;&nbsp;<i class="fa fa-times"></i> Could not fetch site details.</span>';
      exit(json_encode($out));
    }

    $modelPages = new Application_Model_DbTable_Composer();
    $modelPages->setTableName('pages');
    $modelPages->setIdColumn('id_page');
    // generate page form_slug
    $pageSlug = strtolower(str_replace(' ','-',$post['page_title']));
    if(!$modelPages->isUnique('page_slug',$pageSlug)) {
      $iAlt = 1;
      while(!$modelPages->isUnique('page_slug',$pageSlug)) {
        $pageSlug = $pageSlug.'-'.$iAlt;
        $iAlt++;
      }
    }

    $newPage = array('id_site' => $site['id_site'],
                      'page_layout' => $post['page_layout'],
                      'page_title' => addslashes($post['page_title']),
                      'page_slug' => $pageSlug,
                      'keywords' => addslashes($post['keywords']),
                      'page_status' => 1
                     );
    if($pageId = $modelPages->insertData($newPage)) {
      // add menu item
      $modelMenuItems = new Application_Model_DbTable_Menuitems();
      $newMenuItem = array('id_parent_menu_item' => 0,
                        'id_menu' => $post['id_menu'],
                        'title' => $post['page_title'],
                        'menu_slug' => strtolower(str_replace(' ','_',$post['page_title'])),
                        'target' => '',
                        'link_type' => 'page_link',
                        'page_id' => $pageId,
                        'page_slug' => $pageSlug,
                        'internal_target_name' => '',
                        'external_link' => '',
                        'menu_item_status' => 1,
                        'sort_order' => $modelMenuItems->getNextSortOrder($post['id_menu'],0),
                       );
      if($modelMenuItems->insertData($newMenuItem)) {
          $out['status'] = 1;
          $out['message'] = '<span style="color:green;">&nbsp;&nbsp;<i class="fa fa-times"></i> Page created succesfully.</span>';
          exit(json_encode($out));
      } else {
        $out['status'] = 0;
        $out['message'] = '<span style="color:yellow;">&nbsp;&nbsp;<i class="fa fa-times"></i> Page created, but could not create menu item.</span>';
        exit(json_encode($out));
      }
    } else {
      $out['status'] = 0;
      $out['message'] = '<span style="color:yellow;">&nbsp;&nbsp;<i class="fa fa-times"></i> Could not create page.</span>';
      exit(json_encode($out));
    }
  }

  ////////////////////////////////////////////////////////////////////
  //////////////   HELPER FUNCTIONS //////////////////////////////////
  ////////////////////////////////////////////////////////////////////
  public function _updateMenuOrder($menuOrder,$idMenu,$sortOrder=1,$parentMenuItem=0) {
    $modelMenuItems = new Application_Model_DbTable_Menuitems();
    if($menuOrder) {
      foreach($menuOrder as $menuItem) {
        $updateMenuItem = array('sort_order' => $sortOrder++);
        $updateMenuItem['id_parent_menu_item'] = $parentMenuItem;
        $modelMenuItems->updateData($updateMenuItem,$menuItem->id);
        if(isset($menuItem->children) && $menuItem->children) {
          $this->_updateMenuOrder($menuItem->children,$idMenu,1,$menuItem->id);
        }
      }
    }

    return true;
  }

  public function _getMainMenuItemsNestableRecursiveList($menUItems,$siteSlug,$isDropdownMenu=0) {
    $out = '';
    $mainItemClass = 'dd-list';
    $mainItemId = ($isFirstItem == 1)?'nestable':'';

    $out .= '<ol class="'.$mainItemClass.'">';
    if($menUItems) {
      $defaultActiveClass = '';
      $defaultItemClass = 'dd-item dd3-item';
      $addOnClass = '';

      foreach($menUItems as $item) {
        $out .= '<li class="'.$defaultItemClass.' '.$addOnClass.'" data-id="'.$item['id_menu_item'].'">';
          $out .= '<div class="dd-handle dd3-handle"></div>';
          $out .= '<div class="dd3-content">'.$item['title'];
            $out .= '<div class="btn-group menu-item-edit-control">';
  						$out .=	'<button id="btn_view_menu_item-'.$item['id_menu_item'].'" type="button" class="btn_view_menu_item btn bg-blue btn-xs"><i class="fa fa-edit"></i> edit</button>';
              $out .=	'<button id="btn_delete_menu_item-'.$item['id_menu_item'].'" type="button" class="btn_delete_menu_item btn bg-red btn-xs"><i class="fa fa-trash"></i> delete</button>';
            $out .=	'</div>';
          $out .= '</div>';
          if(isset($item['sub_menu']) && $item['sub_menu']) $out .= $this->_getMainMenuItemsNestableRecursiveList($item['sub_menu'],$siteSlug,1);

        $out .= '</li>';
      }
      $out .= '</ol>';
      return $out;

    } else return false;
  }

  function _getImage($media,$outputType='image',$maxHeight='',$maxWidth='') {

    $serverUrl = $this->utilities->getServerUrl();
    $defImageUrl = $serverUrl.'/'.$this->view->baseUrl().'/images/noimage.jpg';

    $modelMedia = new Application_Model_DbTable_Media();
    $get = $this->getRequest()->getQuery();

    if(!$media) {
      if($outputType == 'image') exit('<img src="'.$defImageUrl.'" >');
      else exit($defImageUrl);
    }

    $mediaUrl = $this->utilities->getMediaUrl($media,1,$media['thumbnail']);

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

}
