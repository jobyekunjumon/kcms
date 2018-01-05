<?php
class Backoffice_ThemesController extends Zend_Controller_Action {

  public function init() {

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

  public function indexAction() {
    $this->view->activeMenuItem = 'dashboard';
    $this->view->pageHeading = 'Dashboard';
    $breadcrumbs[] = array('link' => '', 'label' => 'Dashboard', 'icon' => 'fa fa-dashboard');
    $this->view->breadcrumbs = $breadcrumbs;
  }

  public function createcategoryAction() {

    $this->view->activeMenuItem = 'themes';
    $this->view->pageHeading = 'Add Category';
    $this->view->pageDescription = 'Create Theme Categories';
    $breadcrumbs[0] = array('title' => 'Dashboard', 'link' => $this->view->baseUrl().'/backoffice', 'status' => '' );
    $breadcrumbs[1] = array('title' => 'Themes', 'link' => $this->view->baseUrl().'/backoffice/themes', 'status' => '' );
    $breadcrumbs[2] = array('title' => 'Add Category', 'link' => '', 'status' => 'active' );
    $this->view->breadcrumbs = $breadcrumbs;

    $modelCat = new Application_Model_DbTable_Categories();

    $request = $this->getRequest();
    $get = $request->getQuery();
    $selectedCatId = '';

    if($request->isPost()) {
      $post = $request->getPost();
      $idToEdit = '';

      if(isset($post['id_to_edit']) && $post['id_to_edit']) $idToEdit = $post['id_to_edit'];

      $validationErrors = $this->_validateCategoryCreation($post,$idToEdit);

      if($validationErrors) {
        $message = $this->utilities->composeMessageHtml('You have some validation errors. Please fix them to continue','error');
        $frmData = $post;
        $selectedCatId = $post['id_parent_category'];
      } else {
        if(isset($post['id_to_edit']) && $post['id_to_edit']) {
          // calculate level
          $categoryLevel = 0;
          if($post['id_parent_category']) {
            $parentCategory = $modelCat->getRowById($post['id_parent_category']);
            if($parentCategory) $categoryLevel = $parentCategory['category_level']+1;
          }
          $updateCat = array('id_parent_category'=>$post['id_parent_category'],'category'=>trim($post['category']),'description'=>$post['description'],'category_level'=>$categoryLevel);

          if($modelCat->updateData($updateCat,$post['id_to_edit'])) {
            // insert log entry
            $logText = 'Adminuser '.$this->user['name'].' ( User# '.$this->user['id_user'].') updated category '.trim($post['category']).' details having category# '.$post['id_to_edit'];
            $this->modelLog->insertLogEntry($logText,$this->user['id_admin_user'],serialize($post),$post['id_to_edit'],'editcategory','id_category');

            $message = $this->utilities->composeMessageHtml('Category updated successfully','success');
          } else $message = $this->utilities->composeMessageHtml('Something went wrong while updating category. Please try again later.','error');

        } else {
          // calculate level
          $categoryLevel = 0;
          if($post['id_parent_category']) {
            $parentCategory = $modelCat->getRowById($post['id_parent_category']);
            if($parentCategory) $categoryLevel = $parentCategory['category_level']+1;
          }
          $newCat = array('id_parent_category'=>$post['id_parent_category'],'category'=>trim($post['category']),'description'=>$post['description'],'category_level'=>$categoryLevel);

          if($catId = $modelCat->insertData($newCat)) {
            // insert log entry
            $logText = 'Adminuser '.$this->user['name'].' ( User# '.$this->user['id_user'].') created a category having category# '.$catId;
            $this->modelLog->insertLogEntry($logText,$this->user['id_admin_user'],serialize($post),$catId,'addcategory','id_category');

            $message = $this->utilities->composeMessageHtml('Category created successfully','success');
          } else $message = $this->utilities->composeMessageHtml('Something went wrong while creating category. Please try again later.','error');
        }
      }
    }

    if(isset($get['edit']) && $get['edit']) {
      $edit = $modelCat->getRowById($get['edit']);
      if($edit) {
        $frmData = $edit;
        $selectedCatId = $edit['id_parent_category'];
      }
    }

    $categoryHierarchy = $modelCat->getCatHierarchy(0);
    $categoryselectEntries = $this->_getCategorySelectEntries($categoryHierarchy,$selectedCatId);

    if(isset($message) && $message) $this->view->message = $message;
    if(isset($frmData) && $frmData) $this->view->frmData = $frmData;
    if(isset($validationErrors) && $validationErrors) $this->view->errors = $validationErrors;
    if(isset($edit) && $edit) $this->view->edit = $edit;
    if(isset($categoryselectEntries) && $categoryselectEntries) $this->view->categoryselectEntries = $categoryselectEntries;

  }

  public function categoriesAction() {
    $this->view->activeMenuItem = 'themes';
    $this->view->pageHeading = 'Categories';
    $this->view->pageDescription = 'Manage Categories';
    $breadcrumbs[0] = array('title' => 'Dashboard', 'link' => $this->view->baseUrl().'/backoffice', 'status' => '' );
    $breadcrumbs[1] = array('title' => 'Categories', 'link' => '', 'status' => '' );
    $this->view->breadcrumbs = $breadcrumbs;

    $modelCat = new Application_Model_DbTable_Categories();

    $categoryHierarchy = $modelCat->getCatHierarchy(0);
    if($categoryHierarchy) $categoryRecursiveList = $this->_getCategoryRecursiveList($categoryHierarchy );

    if(isset($message) && $message) $this->view->message = $message;
    if(isset($categoryHierarchy) && $categoryHierarchy) $this->view->categoryHierarchy = $categoryHierarchy;
    if(isset($categoryRecursiveList) && $categoryRecursiveList) $this->view->categoryRecursiveList = $categoryRecursiveList;
  }

  public function createAction() {
    $this->view->activeMenuItem = 'themes';
    $this->view->pageHeading = 'Upload Theme';
    $this->view->pageDescription = 'Upload Theme';
    $breadcrumbs[0] = array('title' => 'Dashboard', 'link' => $this->view->baseUrl().'/backoffice', 'status' => '' );
    $breadcrumbs[1] = array('title' => 'Categories', 'link' => $this->view->baseUrl().'/backoffice/themes', 'status' => '' );
    $breadcrumbs[2] = array('title' => 'Add Theme', 'link' => '', 'status' => 'active' );
    $this->view->breadcrumbs = $breadcrumbs;

    $request = $this->getRequest();

    $modelTheme = new Application_Model_DbTable_Themes();
    $modelSites = new Application_Model_DbTable_Sites();
    $modelCat = new Application_Model_DbTable_Categories();
    $categoryHierarchy = $modelCat->getCatHierarchy(0);
    $categoryselectEntries = $this->_getCategorySelectEntries($categoryHierarchy,$selectedCatId);

    if($request->getPost()) {
      $post = $request->getPost();
      $validationErrors = $this->_validateThemeCreation($post);
      if($validationErrors) {
        $message = $this->utilities->composeMessageHtml('You have some valiodation errors. Please fix them to continue.','error');
        $frmData = $post;
      } else {
        $themeSlug = $post['theme_slug'];
        $file = $_FILES['theme_file'];

        // move uploaded zip file to themes directory
        $targetDir = $_SERVER['DOCUMENT_ROOT'].$this->view->baseUrl().'/themes/';
        $targetPath = $this->view->baseUrl().'/themes/';
        if (!is_dir($targetDir)) {
          mkdir($targetDir, 0777, true);
        }
        chmod($targetDir, 0777);
        $documentName = $file['name'];
        $filename = $targetDir.$documentName;
        $fileUrl = $targetPath.$documentName;
        if(file_exists($filename)) {
          exit('This theme file already exist in the server. Please check with the server admin and upload again later.');
        }
        if(move_uploaded_file($file['tmp_name'],$filename)) {
          chmod($filename, 0777);

          // unzip theme
          $this->_unzipFile($filename,$targetDir);

          // insert theme entry
          $newTheme = array('created_by' => $this->user['id_admin_user'],
                            'theme_name' => addslashes($post['theme_name']),
                            'theme_slug' => addslashes($post['theme_slug']),
                            'id_category' => $post['id_category'],
                            'description' => addslashes($post['description']),
                            'tags' => addslashes($post['tags']),
                            'created_on' => date('Y-m-d H:i:s'),
                            'theme_price' => 0,
                            'is_exclusive' => 0,
                            'theme_status' => 'draft'
                           );
          if($themeId = $modelTheme->insertData($newTheme)) {
            $message = $this->utilities->composeMessageHtml('Theme created successfully. Processing the rest of the actions.','success');
            // create site
            $newSite = array('id_user' => 0,
                              'id_theme' => $themeId,
                              'created_on' => date('Y-m-d H:i:s'),
                              'id_subscription' => 0,
                              'site_name' => $newTheme['theme_name'].' Demo Site',
                              'site_slug' => $newTheme['theme_slug'].'-demo-site',
                              'site_description' => '',
                              'hosting_type' => 'sub_domain',
                              'subdomain_name' => $newTheme['theme_slug'].'-demo-site',
                              'domain_name' => '',
                              'site_status' => 'active'
                             );
            if($siteId = $modelSites->insertData($newSite)) {
              $message = $this->utilities->composeMessageHtml('Site created successfully. Processing the rest of the actions.','success');
              // idfentify the layout files and find out default component ids
              $layoutFiles = glob($targetDir.$themeSlug."/*.phtml");
              if($layoutFiles) {
                $modelMenuItems = new Application_Model_DbTable_Menuitems();
                $modelMenu = new Application_Model_DbTable_Composer();
                $modelMenu->setTableName('menu');
                $modelMenu->setIdColumn('id_menu');
                $modelPages = new Application_Model_DbTable_Composer();
                $modelPages->setTableName('pages');
                $modelPages->setIdColumn('id_page');

                // add default menu
                /*
                $newMenu = array('menu_title' => 'Main Menu',
                                  'id_site' => $siteId,
                                  'id_page' => 0,
                                  'component_id' => '@menu;',
                                  'menu_type' => 'main_menu',
                                  'menu_status' => 1
                                 );
                @$defaultMenuId = $modelMenu->insertData($newMenu);
                */

                foreach($layoutFiles as $layoutFile) {
                  // find the file name
                  $fileNameComponents = explode('/',$layoutFile);
                  $layoutFile = end($fileNameComponents);
                  $layoutFileName = str_replace('.phtml','',$layoutFile);
                  $layoutFilePath =  APPLICATION_PATH.'/../themes/'.$themeSlug.'/'.$layoutFile;

                  // add a default page entry
                  $newPage = array('id_site' => $siteId,
                                    'page_layout' => $layoutFileName,
                                    'page_title' => ucwords(str_replace('_',' ',$layoutFileName)),
                                    'page_slug' => $layoutFileName,
                                    'keywords' => 'Demo Site of ',
                                    'page_status' => 1
                                   );
                  @$pageId = $modelPages->insertData($newPage);

                  // insert menu menu items
                  /*
                  $newMenuItem = array('id_parent_menu_item' => 0,
                                    'id_menu' => $defaultMenuId,
                                    'title' => $newPage['page_title'],
                                    'menu_slug' => $layoutFileName,
                                    'target' => '',
                                    'link_type' => 'page_link',
                                    'page_id' => $pageId,
                                    'page_slug' => $layoutFileName,
                                    'external_link' => '',
                                    'menu_item_status' => 1,
                                   );
                  @$modelMenuItems->insertData($newMenuItem);
                  */

                  // find the place holders inside each file
                  // read file
                  $layoutFileContents = file_get_contents($layoutFilePath);
                  if(!$layoutFileContents) exit('Something went wrong while fetching theme data. Please try again later.');

                  // insert all theme components to table
                  preg_match_all('/@(.*);/', $layoutFileContents, $themeComponents);
                	if (isset($themeComponents[0]) && $themeComponents[0]) {
                    $sqlInsertThemeComponents = 'INSERT INTO `default_theme_components` (`id_theme`, `component_id`, `component_type`, `page_layout`) ';
                    $conj = ' VALUES ( ';
                		foreach ($themeComponents[0] as $themeComponent) {
                      $sqlInsertThemeComponents .= $conj.' '.$themeId.', "'.$themeComponent.'", "general", "'.$layoutFileName.'"';
                      $conj = ' ), ( ';
                		}
                    $sqlInsertThemeComponents .= ' );';
                    @$modelTheme->execute($sqlInsertThemeComponents);
                	}
                }
              } else {
                $message = $this->utilities->composeMessageHtml('Could not find any layout files.Installation failed.','error');
              }
            } else {
              $message = $this->utilities->composeMessageHtml('Something went wrong while adding site entry. Rolling back updates. Try again later.','error');
              // delet uploaded files
              unlink($filename);
              unlink($targetDir.$themeSlug);
              $modelTheme->deleteData($themeId);
            }

          } else {
            $message = $this->utilities->composeMessageHtml('Something went wrong while adding theme entry. Rolling back updates. Try again later.','error');
            // delete uploaded files
            unlink($filename);
            unlink($targetDir.$themeSlug);
          }
        }

      }
    }

    if(isset($message) && $message) $this->view->message = $message;
    if(isset($validationErrors) && $validationErrors) $this->view->errors = $validationErrors;
    if(isset($frmData) && $frmData) $this->view->frmData = $frmData;
    if(isset($categoryselectEntries) && $categoryselectEntries) $this->view->categoryselectEntries = $categoryselectEntries;
  }

  public function processThemeSiteEditAction() {
    $layout = $this->_helper->layout();
    $layout->setLayout('layout_alte');

    $request = $this->getRequest();
    $get = $request->getQuery();

    if(!isset($get['name']) || !isset($get['name'])) {
      exit('Could not find site name.');
    }

    $siteSlug = $get['name'];
    $modelSites = new Application_Model_DbTable_Composer();
    $modelSites->setTableName('sites');
    $modelSites->setIdColumn('id_site');


    $site = $modelSites->getRowByCondition(' `site_slug` = "'.$siteSlug.'"');
    if(!$site) exit('Could not fetch site details.');
    if(!$this->_authenticateSite($site)) exit('Could not authenticate site.');  // check whether this an active site  // check subscriptions and site status

    // get requested page
    $modelPages = new Application_Model_DbTable_Composer();
    $modelPages->setTableName('pages');
    $modelPages->setIdColumn('id_page');

    $pageSlug = 'home';
    if(isset($get['page']) && $get['page']) $pageSlug = $get['page'];

    $page = $modelPages->getRowByCondition(' `id_site` = '.$site['id_site'].' AND `page_slug` = "'.$pageSlug.'" AND `page_status` = 1');
    if(!$page) exit('Could not fetch page details.');

    // get all pages
    $pages = $modelPages->getAll(' WHERE `id_site` = '.$site['id_site'].' AND `page_status` = 1 ');

    // get theme
    $modelTheme = new Application_Model_DbTable_Composer();
    $modelTheme->setTableName('themes');
    $modelTheme->setIdColumn('id_theme');
    $theme = $modelTheme->getRowById($site['id_theme']);
    $theme['page_layout'] = $page['page_layout'];
    $theme['directory'] = APPLICATION_PATH.'/../themes/'.$theme['theme_slug'];
    $theme['layout_file'] = $theme['directory'].'/'.$theme['page_layout'].'.phtml';

    //// process content updates
    if($request->isPost()) {
      $post = $request->getPost();

      if($post['action'] == "add"  ) {
        if(!isset($post['component_id']) || !$post['component_id'] ) exit('Component ID not found. Please go back and try again.');

        $componentType = 'text';
        if(isset($post['component_type']) && $post['component_type']) $componentType = $post['component_type'];

        if($componentType == 'text' || $componentType == 'html') {
          if(!isset($post['content_input']) || !$post['content_input'] ) exit('Component data not found. Please go back and try again.');
          $modelContents = new Application_Model_DbTable_Composer();
          $modelContents->setTableName('contents');
          $modelContents->setIdColumn('id_content');

          $idPage = 0;
          if(isset($post['page_specific']) && $post['page_specific']) $idPage = $page['id_page'];

          $newContent = array('id_site' => $site['id_site'],
                            'id_page' => $idPage,
                            'component_id' => $post['component_id'].';',
                            'content_type' =>$componentType,
                            'content' => addslashes($post['content_input'])
                           );
          if($modelContents->insertData($newContent)) {
            $this->_redirect('/backoffice/themes/edit-theme-site?name='.$site['site_slug'].'&page='.$page['page_slug']);
          } else {
            exit('Something went wrong while adding content. Please go back and try again.');
          }
        } else if($componentType == 'menu') {
          // validate input
          $validationErrors = $this->_validateMenuCreation($post);
          if($validationErrors) {
            echo 'You have some validation errors. Please fix the following errors. Go back, make the changes and submit again.';
            foreach($validationErrors as $error) {
              echo '<br>*'.$error;
            }
            exit;
          } else {
            // add menu
            $modelMenuItems = new Application_Model_DbTable_Menuitems();
            $modelMenu = new Application_Model_DbTable_Composer();
            $modelMenu->setTableName('menu');
            $modelMenu->setIdColumn('id_menu');

            $idPage = 0;
            if(isset($post['page_specific']) && $post['page_specific']) $idPage = $page['id_page'];
            $newMenu = array('menu_title' => $post['menu_title'],
                              'id_site' => $site['id_site'],
                              'id_page' => $idPage,
                              'component_id' => $post['component_id'].';',
                              'menu_type' => $post['menu_type'],
                              'menu_status' => 1
                             );
            if($defaultMenuId = $modelMenu->insertData($newMenu)) {
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
                                'id_menu' => $defaultMenuId,
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
              if($modelMenuItems->insertData($newMenuItem)) {
                $this->_redirect('/backoffice/themes/edit-theme-site?name='.$site['site_slug'].'&page='.$page['page_slug']);
              } else {
                // roll back changes
                $modelMenu->deleteData($defaultMenuId);
                exit('Something went wrong while adding menu items. Rolling back changes. Please try again later.');
              }
            } else {
              exit('Something went wrong while creating menu. Please try again later.');
            }
          }

        } else if($componentType == 'image') {
          $modelSiteMedia = new Application_Model_DbTable_Composer();
          $modelSiteMedia->setTableName('site_media');
          $modelSiteMedia->setIdColumn('id_site_media');
          //$this->utilities->debug($post); exit;
          if(isset($post['featured_images'][0]) && $post['featured_images'][0]) {
            $altText = ''; $mediaSizeWidth = ''; $mediaSizeHeight = ''; $mediaThumbnail = '';$idPage = 0;
            if(isset($post['page_specific']) && $post['page_specific']) $idPage = $page['id_page'];
            if(isset($post['alt_text']) && $post['alt_text']) $altText = $post['alt_text'];
            if(isset($post['media_size_width']) && $post['media_size_width']) $mediaSizeWidth = $post['media_size_width'];
            if(isset($post['media_size_height']) && $post['media_size_height']) $mediaSizeHeight = $post['media_size_height'];
            if(isset($post['thumbnail']) && $post['thumbnail'] != 'custom') $mediaThumbnail = $post['thumbnail'];

            $newSiteMedia = array('id_site' => $site['id_site'],
                                'id_page' => $idPage,
                                'component_id' => $post['component_id'].';',
                                'id_media' => $post['featured_images'][0],
                                'alt_text' => $altText,
                                'media_size_width' => $mediaSizeWidth,
                                'media_size_height' => $mediaSizeHeight,
                                'thumbnail' => $mediaThumbnail,
                                'site_media_status' => 1
                               );
             if($modelSiteMedia->insertData($newSiteMedia)) {
               $this->_redirect('/backoffice/themes/edit-theme-site?name='.$site['site_slug'].'&page='.$page['page_slug']);
             } else {
               exit('Something went wrong while adding media item. Rolling back changes. Please try again later.');
             }
          } else {
            exit('No images selected. Please go back and try again.');
          }
        }
      }
    }
  }

  public function editThemeSiteAction() {
    $layout = $this->_helper->layout();
    $layout->setLayout('layout_cms_edit_theme_site');

    $request = $this->getRequest();
    $get = $request->getQuery();

    if(!isset($get['name']) || !isset($get['name'])) {
      exit('Could not find site name.');
    }

    $siteSlug = $get['name'];
    $modelSites = new Application_Model_DbTable_Composer();
    $modelSites->setTableName('sites');
    $modelSites->setIdColumn('id_site');


    $site = $modelSites->getRowByCondition(' `site_slug` = "'.$siteSlug.'"');
    if(!$site) exit('Could not fetch site details.');
    if(!$this->_authenticateSite($site)) exit('Could not authenticate site.');  // check whether this an active site  // check subscriptions and site status

    // get requested page
    $modelPages = new Application_Model_DbTable_Composer();
    $modelPages->setTableName('pages');
    $modelPages->setIdColumn('id_page');

    $pageSlug = 'home';
    if(isset($get['page']) && $get['page']) $pageSlug = $get['page'];

    $page = $modelPages->getRowByCondition(' `id_site` = '.$site['id_site'].' AND `page_slug` = "'.$pageSlug.'" AND `page_status` = 1');
    if(!$page) exit('Could not fetch page details.');

    // get all pages
    $pages = $modelPages->getAll(' WHERE `id_site` = '.$site['id_site'].' AND `page_status` = 1 ');

    // get theme
    $modelTheme = new Application_Model_DbTable_Composer();
    $modelTheme->setTableName('themes');
    $modelTheme->setIdColumn('id_theme');
    $theme = $modelTheme->getRowById($site['id_theme']);
    $theme['page_layout'] = $page['page_layout'];
    $theme['directory'] = APPLICATION_PATH.'/../themes/'.$theme['theme_slug'];
    $theme['layout_file'] = $theme['directory'].'/'.$theme['page_layout'].'.phtml';

    ///////////////////////////////////////////////////////////////////////////////////
    //                          GET  CONTENTS                                        //
    ///////////////////////////////////////////////////////////////////////////////////

    $modelContents = new Application_Model_DbTable_Composer();
    $modelContents->setTableName('contents');
    $modelContents->setIdColumn('id_content');
    $contents = $modelContents->getAll(' WHERE `id_site` = '.$site['id_site'].' AND (`id_page` = '.$page['id_page'].' OR `id_page` = 0)');

    // get sliders
    $modelSliders = new Application_Model_DbTable_Composer();
    $modelSliders->setTableName('sliders');
    $modelSliders->setIdColumn('id_slider');
    $sliders = $modelSliders->getAll(' WHERE `id_site` = '.$site['id_site'].' AND (`id_page` = '.$page['id_page'].' OR `id_page` = 0)');
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

        $contentsCount = count($contents);
        $contents[$contentsCount]['component_id'] = $slider['component_id'];
        $contents[$contentsCount]['component_type'] = 'slider';
        $contents[$contentsCount]['content'] = $sliderContent;
      }
    }

    // get menus
    $modelMenu = new Application_Model_DbTable_Composer();
    $modelMenu->setTableName('menu');
    $modelMenu->setIdColumn('id_menu');
    $menu = $modelMenu->getAll(' WHERE `id_site` = '.$site['id_site'].' AND `menu_status` = 1 AND (`id_page` = '.$page['id_page'].' OR `id_page` = 0)');
    if($menu) {
      $modelMenuItems = new Application_Model_DbTable_Menuitems();

      foreach($menu as $menuEntry) {
        $menuContent = '';
        $menuItemsHirarchy = $modelMenuItems->getMenuitemsHierarchy(0,' `id_menu` = '.$menuEntry['id_menu'].' AND `menu_item_status` = 1');
        if($menuEntry['menu_type'] == "main_menu") {
            $menuContent = $this->_getMainMenuItemsRecursiveList($menuItemsHirarchy,$site['site_slug'],0);
        } else if($menuEntry['menu_type'] == "footer_menu") {
          $menuContent = $this->_getFooterMenuList($menuItemsHirarchy,$site['site_slug']);
        }

        $contentsCount = count($contents);
        $contents[$contentsCount]['component_id'] = $menuEntry['component_id'];
        $contents[$contentsCount]['component_type'] = 'menu';
        $contents[$contentsCount]['content'] = $menuContent;
      }

    }

    // get media
    $modelMedia = new Application_Model_DbTable_Media();
    $sqlGetSiteMedia = 'SELECT sm.`component_id`, sm.`alt_text` as altered_alt_text, m.`id_media`, m.`file_name`, m.`file_type`,
                               m.`file_extension`,m.`file_directory`, m.`file_url`, m.`caption`, m.`alt_text`, sm.`media_size_width`, sm.`media_size_height`,sm.`thumbnail`
                               FROM `site_media` sm, `media` m
                               WHERE m.`id_media` = sm.`id_media` AND sm.`id_site` = '.$site['id_site'].' AND (sm.`id_page` = '.$page['id_page'].' OR sm.`id_page` = 0)
                               AND sm.`site_media_status` = 1 AND m.`media_status` =1';
    $siteMedia = $modelMedia->getAll('',$sqlGetSiteMedia);
    if($siteMedia) {
      foreach ($siteMedia as $media) {
        $mediaContent = '';
        if($media) {
          $contentsCount = count($contents);
          $contents[$contentsCount]['component_id'] = $media['component_id'];
          $contents[$contentsCount]['component_type'] = 'image';
          $contents[$contentsCount]['content'] = $this->_getImage($media);
        }
      }
    }

    // get forms
    $modelForms = new Application_Model_DbTable_Composer();
    $modelForms->setTableName('forms');
    $modelForms->setIdColumn('id_form');
    $forms = $modelForms->getAll(' WHERE `id_site` = '.$site['id_site'].' AND `form_status` = 1 AND (`id_page` = '.$page['id_page'].' OR `id_page` = 0)');
    if($forms) {
      foreach($forms as $form) {
        $contentsCount = count($contents);
        $contents[$contentsCount]['component_id'] = $form['component_id'];
        $contents[$contentsCount]['component_type'] = 'form';
        $contents[$contentsCount]['content'] = $this->_getForm($form,'post','');
      }
    }

    //$this->utilities->debug($site);$this->utilities->debug($page);$this->utilities->debug($contents); exit();

    if(isset($site) && $site) $this->view->site = $site;
    if(isset($page) && $page) $this->view->page = $page;
    if(isset($pages) && $pages) $this->view->pages = $pages;
    if(isset($theme) && $theme) $this->view->theme = $theme;
    if(isset($contents) && $contents) $this->view->contents = $contents;
  }

  public function getPagesSelectEntryAction() {
    $layout = $this->_helper->layout();
    $layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    $request = $this->getRequest();
    $out = '';
    if($request->isPost()) {
      $post = $request->getPost();
      if($post['id_site']) {
        $modelPages = new Application_Model_DbTable_Composer();
        $modelPages->setTableName('pages');
        $modelPages->setIdColumn('id_page');
        $pages = $modelPages->getAll(' WHERE `id_site` = '.$post['id_site'].' AND `page_status` = 1 ');

        if($pages) {
          foreach($pages as $page) {
            $out .= '<option value="'.$page['id_page'].'"';
            if(isset($post['selected_page']) && $post['selected_page'] == $page['id_page'] ) $out .= ' selected="selected"';
            $out .= '>'.$page['page_title'].'</option>';
          }
        }
      }
    }

    exit($out);
  }

  ////////////////////////////////////////////////////////////////////
  //////////////   HELPER FYNCTIONS //////////////////////////////////
  ////////////////////////////////////////////////////////////////////

  public function _validateMenuCreation($post) {
    $errors = array();
    if(!isset($post['menu_title']) || !$post['menu_title']) $errors['menu_title'] = 'Enter menu title.';
    if(!isset($post['menu_type']) || !$post['menu_type']) $errors['menu_type'] = 'Please select a menu type.';
    if(!isset($post['title']) || !$post['title']) $errors['title'] = 'Please enter a menu item title.';
    if(!isset($post['link_type']) || !$post['link_type']) $errors['link_type'] = 'Please select a link type.';
    if(!isset($post['link_type_attribute']) || !$post['link_type_attribute']) $errors['link_type_attribute'] = 'Please enter link item target.';
    return $errors;
  }

  function _getForm($form,$method,$action) {
    $out = '';
    $modelFormElements = new Application_Model_DbTable_Composer();
    $modelFormElements->setTableName('form_elements');
    $modelFormElements->setIdColumn('id_form_element');
    $formElements = $modelFormElements->getAll(' WHERE `id_form` = "'.$form['id_form'].'" AND `element_status` = 1');
    if($formElements) {
      $out = '<form role="form" method="'.$method.'" action="'.$action.'" id="'.$form['id_form'].'">';
      $out .= '<input type="hidden" name="id_form" value="'.$form['id_form'].'" />';
      foreach($formElements as $formElement) {
        $elementHtml = '';
        switch ($formElement['element_type']) {
          case 'text':
              $elementHtml .= '<div class="form-group">';
                $elementHtml .= '<label>'.$formElement['element_name'].'</label>';
                $elementHtml .= '<input class="form-control '.$formElement['element_class'].'" type="text" name="form_element_'.$formElement['id_form_element'].'" id="form_element_'.$formElement['id_form_element'].'" ';
                if($formElement['placeholder']) $elementHtml .= 'placeholder="'.$formElement['placeholder'].'"';
                if($formElement['default_value']) $elementHtml .= 'value="'.$formElement['default_value'].'"';
                $elementHtml .= '/>';
              $elementHtml .= '</div>';
            break;
            case 'text_area':
                $elementHtml .= '<div class="form-group">';
                  $elementHtml .= '<label>'.$formElement['element_name'].'</label>';
                  $elementHtml .= '<textarea class="form-control '.$formElement['element_class'].'" name="form_element_'.$formElement['id_form_element'].'" id="form_element_'.$formElement['id_form_element'].'" ';
                  $elementHtml .= 'placeholder="'.$formElement['placeholder'].'" >';
                  if($formElement['default_value']) $elementHtml .= $formElement['default_value'];
                  $elementHtml .= '</textarea>';
                $elementHtml .= '</div>';
              break;
              case 'submit':
                  $elementClass = 'btn-default';
                  if($formElement['element_class']) $elementClass = $formElement['element_class'];
                  $elementHtml .= '<div class="form-group">';
                    $elementHtml .= '<input type="submit" class="btn '.$elementClass.'" name="form_element_'.$formElement['id_form_element'].'" id="form_element_'.$formElement['id_form_element'].'" ';
                    if($formElement['default_value']) $elementHtml .= 'value="'.$formElement['default_value'].'"';
                    $elementHtml .= '/>';
                  $elementHtml .= '</div>';
                break;
                case 'button':
                    $elementClass = 'btn-default';
                    if($formElement['element_class']) $elementClass = $formElement['element_class'];
                    $elementHtml .= '<div class="form-group">';
                      $elementHtml .= '<input type="button" class="btn '.$elementClass.'" name="form_element_'.$formElement['id_form_element'].'" id="form_element_'.$formElement['id_form_element'].'" ';
                      if($formElement['default_value']) $elementHtml .= 'value="'.$formElement['default_value'].'"';
                      $elementHtml .= '/>';
                    $elementHtml .= '</div>';
                  break;
          default:
            # code...
            break;
        }

        $out .= $elementHtml;
      }
      $out .= '</form>';
    }

    return $out;
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

  public function _getFooterMenuList($menUItems,$siteSlug) {
    $out = '';
    if($menUItems) {
      $out .= '<ul class="footer-menu">';
        foreach ($menUItems as $item) {
          $out .= '<li class="'.$defaultActiveClass.' '.$addOnClass.'">'; $defaultActiveClass = '';
          if($item['link_type'] == "page_link" && $item['page_slug'] ) $out .= ' <a target="'.$item['target'].'" href="'.$this->view->baseUrl().'/sites?name='.$siteSlug.'&page='.$item['page_slug'].'">'.$item['title'].'</a>';
          else $out .= ' <a target="'.$item['target'].'" href="'.$item['external_link'].'">'.$item['title'].'</a>';
          $out .= '</li>';
        }
      $out .= '</ul>';
    }
    return $out;
  }

  public function _getMainMenuItemsRecursiveList($menUItems,$siteSlug,$isDropdownMenu=0) {
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
          if($item['link_type'] == "page_link" && $item['page_slug'] ) $out .= ' <a target="'.$item['target'].'" href="'.$this->view->baseUrl().'/sites?name='.$siteSlug.'&page='.$item['page_slug'].'">'.$item['title'].'</a>';
          else $out .= ' <a target="'.$item['target'].'" href="'.$item['external_link'].'">'.$item['title'].'</a>';
        }

        if(isset($item['sub_menu']) && $item['sub_menu']) $out .= $this->_getMainMenuItemsRecursiveList($item['sub_menu'],$siteSlug,1);

        $out .= '</li>';
      }
      $out .= '</ul>';
      return $out;

    } else return false;
  }

  public function _authenticateSite($site) {
    return true;
  }

  function _unzipFile($file, $destination){
      // create object
      $zip = new ZipArchive() ;
      // open archive
      if ($zip->open($file) !== TRUE) {
          return false;
      }
      // extract contents to destination directory
      $zip->extractTo($destination);
      // close archive
      $zip->close();

      return true;
  }

  public function _validateThemeCreation($post) {
    $modelTheme = new Application_Model_DbTable_Themes();
    $errors = array();
    if(!isset($post['theme_name']) || !$post['theme_name']) $errors['theme_name'] = "Please enter theme name";
    if(!isset($post['theme_slug']) || !$post['theme_slug']) $errors['theme_slug'] = "Please enter theme identifier";
    else if(!$modelTheme->isUnique('theme_slug',$post['theme_slug'])) $errors['theme_slug'] = "This theme identifier already exist.";
    if(!isset($post['id_category']) || !$post['id_category']) $errors['id_category'] = "Please select a category.";

    $allowedExts = array("zip");
    if(!isset($_FILES["theme_file"]) || !$_FILES["theme_file"]) {
      $errors['theme_file'] = 'Please choose a file to upload.';
		} if(!file_exists($_FILES["theme_file"]["tmp_name"])) {
  		$errors['theme_file'] = 'Please choose a file to upload.';
		} else {
			$exts = explode(".", $_FILES["theme_file"]["name"]);
			$extension = end($exts);
			if(!in_array($extension,$allowedExts)) {
				$errors['theme_file'] = 'This is not a valid file extension. You can upload only files with extension .zip';
			} else if($exts[0] != $post['theme_slug'] ) {
        $errors['theme_file'] = 'Name of the compressed file should be same as theme identifier.';
      }
		}


    return $errors;
  }

  public function _getCategoryRecursiveList($categories) {
    $labelClasses = array(0 => '',1=>'badge badge-warning',2=>'badge badge-success',3=>'badge badge-danger',4=>'badge badge-info');
    $out = '<ul>';
    if($categories) {
      foreach($categories as $cat) {
        $labelClass = isset($labelClasses[$cat['category_level']])?$labelClasses[$cat['category_level']]:'';
        $out .= '<li>';
        $out .= '<span class="'.$labelClass.'"><i class="icon-minus-sign"></i> '.$cat['category'];
        $out .= ' <a href="'.$this->view->baseUrl().'/backoffice/themes/createcategory?edit='.$cat['id_category'].'">Edit</a>';
        $out .= ' | <a href="'.$this->view->baseUrl().'/backoffice/themes/categories?delete='.$cat['id_category'].'">Delete</a>';
        $out .= '</span>';
        if(isset($cat['sub_categories']) && $cat['sub_categories']) $out .= $this->_getCategoryRecursiveList($cat['sub_categories']);
        $out .= '</li>';
      }
      $out .= '</ul>';
      return $out;

    } else return false;
  }

public function _getCategoryRecursiveCheckList($categories,$selectedCatId='') {
  $labelClasses = array(0 => '',1=>'badge badge-warning',2=>'badge badge-success',3=>'badge badge-danger',4=>'badge badge-info');
  $out = '<ul class="category_rec_list">';
  if($categories) {
    foreach($categories as $cat) {
      $labelClass = isset($labelClasses[$cat['category_level']])?$labelClasses[$cat['category_level']]:'';
      $out .= '<li>';
        $out .= '<input type="checkbox" name="category['.$cat['id_category'].']" id="category_'.$cat['id_category'].'" value="'.$cat['id_category'].'" class="crc_checkbox" ';
          if(is_array($selectedCatId) && in_array($cat['id_category'],$selectedCatId)) $out .= 'checked="checked"';
            else if($selectedCatId == $cat['id_category'] ) $out .= 'checked="checked"';
        $out .='/> &nbsp;';

        //$out .= '<span class="'.$labelClass.'">';
        $out .= $cat['category'];
        //$out .= '</span>';
        if(isset($cat['sub_categories']) && $cat['sub_categories']) $out .= $this->_getCategoryRecursiveCheckList($cat['sub_categories'],$selectedCatId);
      $out .= '</li>';
    }

    $out .= '</ul>';
    return $out;

  } else return false;
}

  public function _getCategorySelectEntries($categories,$selectedCatId='') {
    $out = '';
    if($categories) {
      foreach($categories as $cat) {
        $out .= '<option value="'.$cat['id_category'].'" ';
        if(is_array($selectedCatId) && in_array($cat['id_category'],$selectedCatId)) $out .= 'selected="selected"';
        else if($selectedCatId == $cat['id_category'] ) $out .= 'selected="selected"';
        $out .= '>';
        // add space for sub categories
        if($cat['category_level']) {
          for($i=$cat['category_level'];$i>0;$i--) $out .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        }
        $out .= $cat['category'];
        $out .= '</option>';

        if(isset($cat['sub_categories']) && $cat['sub_categories']) $out .= $this->_getCategorySelectEntries($cat['sub_categories'],$selectedCatId);
      }

      return $out;
    } else return false;
  }

  public function _validateCategoryCreation($post,$edit = '') {
    $modelCat = new Application_Model_DbTable_Categories();
    $errors = array();

    if(!isset($post['category']) || !trim($post['category'])) $errors['category'] = ' * Please enter a category name';
    else  {
      $parentCategory = (isset($post['id_parent_category']) && $post['id_parent_category'])?$post['id_parent_category']:0;
      if(!$edit) {
        if(!$modelCat->isUnique('category',trim($post['category']),' AND `id_parent_category` = '.$parentCategory)) {
          $errors['category'] = ' * This category name already exists for this parent category';
        }
      } else {
        if(!$modelCat->isUnique('category',trim($post['category']),' AND `id_category` != '.$edit.' AND `id_parent_category` = '.$parentCategory)) {
          $errors['category'] = ' * This category name already exists for this parent category';
        }
      }
    }

    return $errors;
  }


}
