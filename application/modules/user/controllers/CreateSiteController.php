<?php
class User_CreateSiteController extends Zend_Controller_Action {

  protected $siteSlug = '';

  public function init() {
    $layout = $this->_helper->layout();
    $layout->setLayout('layout');

    $auth = Zend_Auth::getInstance();
    if ($auth->hasIdentity()) {
      $this->user = (array) $auth->getIdentity();
      unset($this->user['salt']);
      unset($this->user['password']);
      unset($this->user['email_verification_key']);
      $this->view->user = $this->user;
    } else {
      $this->_helper->redirector('index', 'auth');
    }

    $this->utilities = new Application_Model_Utilities();
  }

  public function indexAction() {
  }

  public function chooseNameAction() {
    $request = $this->getRequest();
    if($request->isPost()) {
      $post = $request->getPost();
      $validationErrors = $this->_validateSubdomainSelection($post);
      if($validationErrors) {
        $frmData = $post;
        $message = $this->utilities->composeMessageHtml('You have some validation errors. Please fix them to continue.','error');
      } else {
        $modelSites = new Application_Model_DbTable_Sites();
        // create site
        $newSite = array('id_user' =>  $this->user['id_user'],
                          'id_theme' => 0,
                          'created_on' => date('Y-m-d H:i:s'),
                          'id_subscription' => 0,
                          'site_name' => trim($post['subdomain_name']),
                          'site_slug' => trim($post['subdomain_name']),
                          'site_description' => '',
                          'hosting_type' => 'sub_domain',
                          'subdomain_name' => trim($post['subdomain_name']),
                          'domain_name' => '',
                          'site_status' => 'active'
                         );
        if($idSite = $modelSites->insertData($newSite)) {

          // redirect to themes page
          $this->_redirect('/user/create-site/choose-theme?site='.trim($post['subdomain_name']));
        } else {
          $message = $this->utilities->composeMessageHtml('Something went wrong while creating your site. Please try again later.','error');
        }

      }
    }

    if(isset($message) && $message) $this->view->message = $message;
    if(isset($frmData) && $frmData) $this->view->frmData = $frmData;
    if(isset($validationErrors) && $validationErrors) $this->view->errors = $validationErrors;
  }

  public function chooseThemeAction() {
    $request = $this->getRequest();
    $get = $request->getQuery();
    $modelSites = new Application_Model_DbTable_Sites();
    if(!$modelSites->authenticateEditing($get,$this->user)) {
      $this->_redirect('/sites/error');
    }
    $this->siteSlug = $get['site'];
    // get all themes
    $modelThemes = new Application_Model_DbTable_Themes();
    $themes = $modelThemes->getAll(' WHERE `theme_status` = "available"');

    if($request->isPost()) {
      $post = $request->getPost();
      $validationErrors = $this->_validateValidateThemeSelection($post);
      if($validationErrors) {
        $frmData = $post;
        $message = $this->utilities->composeMessageHtml($validationErrors['id_theme'],'error');
      } else {
        // get theme
        $theme = $modelThemes->getRowById($post['id_theme']);
        // get site
        $site = $modelSites->getSite($this->siteSlug);

        // get theme site
        $themeSiteSlug = $theme['theme_slug'].'-demo-site';
        $themeSite = $modelSites->getSite($themeSiteSlug);

        // update site entry with theme id
        $updateSite = array('id_theme' => $theme['id_theme']);
        if($modelSites->updateData($updateSite,$site['id_site'])) {
          // fetch all pages from theme demo site and create demo pages and its contents
          $this->_iterateThemeSite($site['id_site'],$themeSite['id_site'],$theme['id_theme']);
          //$this->_redirect('http://'.$site['site_slug'].'.oosify.com');
          $this->_redirect('/user/edit-site?name='.$site['site_slug']);
        } else {
          $message = $this->utilities->composeMessageHtml('Something went wrong while updating your site. Please go back to your dash board and try again later.','error');
        }
      }
    }

    if(isset($message) && $message) $this->view->message = $message;
    if(isset($themes) && $themes) $this->view->themes = $themes;
  }

  public function checkSubdomainAvailabilityAction(){
    $layout = $this->_helper->layout();
    $layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    $modelSites = new Application_Model_DbTable_Sites();
    $request = $this->getRequest();
    if($request->isPost()) {
      $post = $request->getPost();
      if(isset($post['subdomain_name']) && $post['subdomain_name']) {
        if($modelSites->isUnique('subdomain_name',$post['subdomain_name'])) {
          exit('<span style="color:green">This subdomain available.</span>');
        } else {
          exit('<span style="color:red">This subdomain already taken.Please try another.</span>');
        }
      }
    }
  }

  ////////////////////////////////////////////////////////////////////
  //////////////   HELPER FUNCTIONS //////////////////////////////////
  ////////////////////////////////////////////////////////////////////

  public function _iterateThemeSite($idSite,$idThemeSite,$idTheme) {

    // get theme site pages
    $modelPages = new Application_Model_DbTable_Composer();
    $modelPages->setTableName('pages');
    $modelPages->setIdColumn('id_page');

    $themeSitePages = $modelPages->getAll(' WHERE `id_site` = '.$idThemeSite);
    if($themeSitePages) {
      foreach ($themeSitePages as $themePage) {
        $newPage = array('id_site' => $idSite,
                          'page_layout' => $themePage['page_layout'],
                          'page_title' => $themePage['page_title'],
                          'page_slug' => $themePage['page_slug'],
                          'keywords' => '',
                          'page_status' => 1
                         );
        @$modelPages->insertData($newPage);
      }
    }

    // get theme site contents
    $modelContents = new Application_Model_DbTable_Composer();
    $modelContents->setTableName('contents');
    $modelContents->setIdColumn('id_content');

    $themeSiteContents = $modelContents->getAll(' WHERE `id_site` = '.$idThemeSite);
    if($themeSiteContents) {
      foreach ($themeSiteContents as $themeContent) {
        $newContent = array('id_site' => $idSite,
                          'id_page' => 0,
                          'component_id' => $themeContent['component_id'],
                          'content_type' => $themePage['content_type'],
                          'content' => $themeContent['content']
                         );
        @$modelContents->insertData($newContent);
      }
    }

    // get theme site menus
    $modelMenu = new Application_Model_DbTable_Composer();
    $modelMenu->setTableName('menu');
    $modelMenu->setIdColumn('id_menu');
    $modelMenuItems = new Application_Model_DbTable_Menuitems();

    $themeMenus = $modelMenu->getAll(' WHERE `id_site` = '.$idThemeSite);
    if($themeMenus) {
      $sitePages = $modelPages->getAll(' WHERE `id_site` = '.$idSite);
      $sitePages = $this->utilities->flipKeys($sitePages,'theme_slug');
      foreach ($themeMenus as $themeMenu) {
        $newMenu = array('menu_title' => $themeMenu['component_id'],
                          'id_site' => $idSite,
                          'id_page' => 0,
                          'component_id' => $themeMenu['component_id'],
                          'menu_type' => $themeMenu['menu_type'],
                          'menu_status' => 1
                         );
        if($newMenuId = @$modelMenu->insertData($newMenu)) {
          // get all menu items of this menu
          $themeMenuItems = $modelMenuItems->getAll(' WHERE `id_menu` = '.$themeMenu['id_menu']);
          if($themeMenuItems) {
            foreach ($themeMenuItems as $themeMenuItem) {
              $menuItemPageId = isset($sitePages[$themeMenuItem['page_slug']])?$sitePages[$themeMenuItem['page_slug']]:0;
              $newMenuItem = array('id_parent_menu_item' => 0,
                                'id_menu' => $newMenuId,
                                'title' => $themeMenuItem['title'],
                                'menu_slug' => $themeMenuItem['menu_slug'],
                                'target' => $themeMenuItem['target'],
                                'link_type' => $themeMenuItem['link_type'],
                                'page_id' => $menuItemPageId,
                                'page_slug' => $themeMenuItem['page_slug'],
                                'internal_target_name' => $themeMenuItem['internal_target_name'],
                                'external_link' => $themeMenuItem['external_link'],
                                'menu_item_status' => $themeMenuItem['menu_item_status'],
                                'sort_order' => $themeMenuItem['sort_order']
                               );

                @$modelMenuItems->insertData($newMenuItem);
            }
          }
        }
      }
    }

    // get theme site media
    $modelSiteMedia = new Application_Model_DbTable_Composer();
    $modelSiteMedia->setTableName('site_media');
    $modelSiteMedia->setIdColumn('id_site_media');
    $themeSiteMedia = $modelSiteMedia->getAll(' WHERE `id_site` = '.$idThemeSite);
    if($themeSiteMedia) {
      foreach ($themeSiteMedia as $mediaEntry) {
        $newSiteMedia = array('id_site' => $idSite,
                          'id_page' => 0,
                          'component_id' => $mediaEntry['component_id'],
                          'id_media' => $mediaEntry['id_media'],
                          'alt_text' => $mediaEntry['alt_text'],
                          'media_size_width' => $mediaEntry['media_size_width'],
                          'media_size_height' => $mediaEntry['media_size_height'],
                          'thumbnail' => $mediaEntry['thumbnail'],
                          'site_media_status' => $mediaEntry['site_media_status']
                        ); //$this->utilities->debug($mediaEntry); $this->utilities->debug($newSiteMedia); echo '<hr>';
        @$modelSiteMedia->insertData($newSiteMedia);
      }
    }

    // get theme site sliders
    $modelSliders = new Application_Model_DbTable_Composer();
    $modelSliders->setTableName('sliders');
    $modelSliders->setIdColumn('id_slider');
    $modelSliderItems = new Application_Model_DbTable_Composer();
    $modelSliderItems->setTableName('slider_items');
    $modelSliderItems->setIdColumn('id_slider_item');

    $themeSliders = $modelSliders->getAll(' WHERE `id_site` = '.$idThemeSite);
    if($themeSliders) {
      foreach ($themeSliders as $themeSlider) {
        $newSlider = array('id_site' => $idSite,
                          'id_page' => 0,
                          'component_id' => $themeSlider['component_id'],
                          'slider_type' => $themeSlider['slider_type'],
                          'preffered_image_size' => $themeSlider['preffered_image_size'],
                          'show_pagination' => $themeSlider['show_pagination'],
                          'show_navigation' => $themeSlider['show_navigation'],
                          'show_item_description' => $themeSlider['show_item_description'],
                          'slider_status' => $themeSlider['slider_status'],
                        );
        if($newSliderId = @$modelSliders->insertData($newSlider)) {
          // get all menu items of this menu
          $themeSliderItems = $modelSliderItems->getAll(' WHERE `id_slider` = '.$themeSlider['id_slider']);

          if($themeSliderItems) {
            foreach ($themeSliderItems as $themeSliderItem) {
              $newSliderItem = array('id_slider' => $newSliderId,
                                'id_media' => $themeSliderItem['id_media'],
                                'image_url' => $themeSliderItem['image_url'],
                                'alt_text' => $themeSliderItem['alt_text'],
                                'item_data' => $themeSliderItem['item_data']
                               );

                @$modelSliderItems->insertData($newSliderItem);
            }
          }
        }
      }
    }

    // get theme site forms
    $modelForms = new Application_Model_DbTable_Composer();
    $modelForms->setTableName('forms');
    $modelForms->setIdColumn('id_form');
    $modelFormElements = new Application_Model_DbTable_Composer();
    $modelFormElements->setTableName('form_elements');
    $modelFormElements->setIdColumn('id_form_element');

    $themeForms = $modelForms->getAll(' WHERE `id_site` = '.$idThemeSite);
    if($themeForms) {
      foreach ($themeForms as $themeForm) {
        $newForm = array('form_name' => $themeForm['form_name'],
                          'form_slug' => $themeForm['form_slug'],
                          'id_site' => $idSite,
                          'id_page' => 0,
                          'component_id' => $themeForm['component_id'],
                          'form_type' => $themeForm['form_type'],
                          'data_handler' => $themeForm['data_handler'],
                          'form_status' => $themeForm['form_status']
                        );
        if($newFormId = @$modelForms->insertData($newForm)) {
          // get all menu items of this menu
          $themeFormItems = $modelFormElements->getAll(' WHERE `id_form` = '.$themeForm['id_form']);

          if($themeFormItems) {
            foreach ($themeFormItems as $themeFormItem) {
              $newFormItem = array('id_form' => $newFormId,
                                'element_type' => $themeFormItem['image_url'],
                                'element_name' => $themeFormItem['element_name'],
                                'place_holder' => $themeFormItem['place_holder'],
                                'validations' => $themeFormItem['validations'],
                                'element_class' => $themeFormItem['element_class'],
                                'default_value' => $themeFormItem['default_value'],
                                'prefill_values' => $themeFormItem['prefill_values'],
                                'element_status' => $themeFormItem['element_status']
                               );

                @$modelFormElements->insertData($newFormItem);
            }
          }
        }
      }
    }

    // get theme site forms
    $modelMaps = new Application_Model_DbTable_Maps();
    $themeMaps = $modelMaps->getAll(' WHERE `id_site` = '.$idThemeSite);
    if($themeMaps) {
      foreach ($themeMaps as  $map) {
        $newMap = array( 'id_site' => $idSite,
                          'id_page' => 0,
                          'component_id' => $map['component_id'],
                          'map_vendor' => $map['map_vendor'],
                          'latitude' => $map['latitude'],
                          'longitude' => $map['longitude'],
                          'zoom_level' => $map['zoom_level'],
                          'map_type' => $map['map_type']
                        );
        @$modelMaps->insertData($newMap);
      }
    }

    return true;

  }


  public function _validateValidateThemeSelection($post) {
    $errors = array();
    $modelThemes = new Application_Model_DbTable_Themes();
    if(!isset($post['id_theme']) || !$post['id_theme']) $errors['id_theme'] = 'Please select a theme.';
    else if(!$theme = $modelThemes->getRowByCondition(' `id_theme` = '.$post['id_theme'].' AND `theme_status` = "available"')) $errors['id_theme'] = 'This is not a valid theme.';

    return $errors;
  }

  public function  _validateSubdomainSelection($post) {
    $modelSites = new Application_Model_DbTable_Sites();
    $errors = array();
    if(!isset($post['subdomain_name']) || !$post['subdomain_name']) $errors['subdomain_name'] = 'Please enter a subdomain name.';
    else if(strpos(' ',trim($post['subdomain_name']))) $errors['subdomain_name'] = 'White spaces and special characters are not allowed in subdomain name.';
    else if(preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $post['subdomain_name'])) $errors['subdomain_name'] = 'White spaces and special characters are not allowed in subdomain name.';
    else if(!$modelSites->isUnique('subdomain_name',$post['subdomain_name'])) $errors['subdomain_name'] = 'This subdomain already taken.Please try another.';
    return $errors;
  }

}
