<?php
class User_EditSiteController extends Zend_Controller_Action {

  protected $siteSlug = '';

  public function init() {
    $layout = $this->_helper->layout();
    $layout->setLayout('layout_user_edit_site_frame');

    $auth = Zend_Auth::getInstance();
    if ($auth->hasIdentity()) {
      $this->user = (array) $auth->getIdentity();
      unset($this->user['salt']); unset($this->user['password']); unset($this->user['email_verification_key']);
      $this->view->user = $this->user;
    } else {
      $this->_redirect('/user/auth');
    }

    $this->utilities = new Application_Model_Utilities();
  }

  public function indexAction() {
    $request = $this->getRequest();
    $get = $request->getQuery();
    $showSite = true;

    // get site sulg and page slug
    $siteSlug = $this->utilities->getSiteSlug($get);
    $pageSlug = $this->utilities->getPageSlug($get);

    // validate parameters
    if(!isset($siteSlug) || !$siteSlug) $showSite = false;
    if(!isset($pageSlug) || !$pageSlug) $showSite = false;

    // fetch and authenticate site for editing
    $modelSites = new Application_Model_DbTable_Sites();
    $site = $modelSites->fetchAndAuthenticateSiteForEditing($siteSlug,$this->user);
    if(!isset($site) || !$site) $showSite = false;

    // fetch page
    if($site) {
      $modelPages = new Application_Model_DbTable_Pages();
      $page = $modelPages->getRowByCondition(' `page_slug` = "'.$pageSlug.'" AND `id_site` = '.$site['id_site']);
    }
    if(!isset($page) || !$page) $showSite = 0;

    if(!$showSite) {
      $message = $this->utilities->composeMessageHtml('Could not fetch site details.','error');
    }

    if(isset($message) && $message) $this->view->message = $message;
    if(isset($site) && $site) $this->view->site = $site;
    if(isset($showSite) && $showSite) $this->view->showSite = $showSite;
    if(isset($page) && $page) $this->view->page = $page;
  }

  public function showSiteAction() {
    $layout = $this->_helper->layout();
    $layout->setLayout('layout_user_edit_site');

    $request = $this->getRequest();
    $get = $request->getQuery();
    $showSite = true;

    // get site sulg and page slug
    $siteSlug = $this->utilities->getSiteSlug($get);
    $pageSlug = $this->utilities->getPageSlug($get);

    // validate parameters
    if(!isset($siteSlug) || !$siteSlug) $this->_redirect('/sites/error');
    if(!isset($pageSlug) || !$pageSlug) $this->_redirect('/sites/error');

    // fetch and authenticate site for editing
    $modelSites = new Application_Model_DbTable_Sites();
    $site = $modelSites->fetchAndAuthenticateSiteForEditing($siteSlug,$this->user);
    if(!$site) $this->_redirect('/sites/error');

    $modelPages = new Application_Model_DbTable_Pages();
    $page = $modelPages->getRowByCondition(' `page_slug` = "'.$pageSlug.'" AND `id_site` = '.$site['id_site']);
    if(!$page) $this->_redirect('/sites/error');

    // get theme
    $modelThemes = new Application_Model_DbTable_Themes();
    $theme = $modelThemes->getRowById($site['id_theme']);
    $theme['page_layout'] = $page['page_layout'];
    $theme['directory'] = APPLICATION_PATH.'/../themes/'.$theme['theme_slug'];
    $theme['layout_file'] = $theme['directory'].'/'.$theme['page_layout'].'.phtml';

    // get contents
    $modelContents = new Application_Model_DbTable_Contents();
    $contents = $modelContents->getAll(' WHERE `id_site` = '.$site['id_site'].' AND (`id_page` = '.$page['id_page'].' OR `id_page` = 0)');

    // get sliders
    $modelSliders = new Application_Model_DbTable_Sliders();
    $sliders = $modelSliders->getSliders($site['id_site'],$page['id_page'],count($contents));
    if($sliders) $contents = array_merge($contents,$sliders);

    // get menu
    $modelMenu = new Application_Model_DbTable_Menu();
    $menuBaseUrl = $this->view->baseUrl().'/user/edit-site/show-site';
    $menu = $modelMenu->getMenu($site,$page['id_page'],count($contents),$menuBaseUrl,$page['page_slug']);
    if($menu) $contents = array_merge($contents,$menu);

    // get media
    $modelMedia = new Application_Model_DbTable_Media();
    $media = $modelMedia->getMedia($site['id_site'],$page['id_page'],count($contents));
    if($media) $contents = array_merge($contents,$media);

    // get forms
    $modelForms = new Application_Model_DbTable_Forms();
    $forms = $modelForms->getForms($site['id_site'],$page['id_page'],count($contents));
    if($forms) $contents = array_merge($contents,$forms);

    // get maps
    $modelMaps = new Application_Model_DbTable_Maps();
    $mapEntries = $modelMaps->getAll(' WHERE `id_site` = '.$site['id_site'].' AND (`id_page` = '.$page['id_page'].' OR `id_page` = 0)');
    $maps = $modelMaps->getMaps($site,$page['id_page'],count($contents),$mapEntries);
    if($maps) $contents = array_merge($contents,$maps);

    // get all layouts of this theme
    $layoutFiles = glob($theme['directory'].$themeSlug."/*.phtml");

    // view assignments
    if(isset($site) && $site) $this->view->site = $site;
    if(isset($mapEntries) && $mapEntries) $this->view->maps = $mapEntries;
    if(isset($page) && $page) $this->view->page = $page;
    if(isset($theme) && $theme) $this->view->theme = $theme;
    if(isset($contents) && $contents) $this->view->contents = $contents;
    if(isset($layoutFiles) && $layoutFiles) $this->view->layoutFiles = $layoutFiles;
  }
  ////////////////////////////////////////////////////////////////////
  //////////////   HELPER FUNCTIONS //////////////////////////////////
  ////////////////////////////////////////////////////////////////////



}
