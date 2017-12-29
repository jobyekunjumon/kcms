<?php

class SitesController extends Zend_Controller_Action {

  public function init() {
    /* Initialize action controller here */
    $layout = $this->_helper->layout();
    $layout->setLayout('layout_cms');

    $this->utilities = new Application_Model_Utilities();
    $this->modelLog = new Application_Model_DbTable_Activitylog();
  }

  public function indexAction() {

    $request = $this->getRequest();
    $get = $request->getQuery();

    if(!isset($get['name']) || !isset($get['name'])) {
      $this->_redirect('/sites/error');
    }
    if(!isset($get['page']) || !isset($get['page'])) {
      $this->_redirect('/sites/error');
    }

    $siteSlug = $get['name'];
    $modelSites = new Application_Model_DbTable_Composer();
    $modelSites->setTableName('sites');
    $modelSites->setIdColumn('id_site');
    // check whether this an active site
    // check subscriptions and site status
    $site = $modelSites->getRowByCondition(' `site_slug` = "'.$siteSlug.'"');
    if(!$site) $this->_redirect('/sites/error');

    if(!$this->_authenticateSite($site)) $this->_redirect('/sites/error');

    // get requested page
    $modelPages = new Application_Model_DbTable_Composer();
    $modelPages->setTableName('pages');
    $modelPages->setIdColumn('id_page');

    $pageSlug = 'home';
    if(isset($get['page']) && $get['page']) $pageSlug = $get['page'];

    $page = $modelPages->getRowByCondition(' `id_site` = '.$site['id_site'].' AND `page_slug` = "'.$pageSlug.'" AND `page_status` = 1');
    if(!$page) $this->_redirect('/sites/error');

    // get theme
    $modelTheme = new Application_Model_DbTable_Composer();
    $modelTheme->setTableName('themes');
    $modelTheme->setIdColumn('id_theme');
    $theme = $modelTheme->getRowById($site['id_theme']);
    $theme['page_layout'] = $page['page_layout'];
    $theme['directory'] = APPLICATION_PATH.'/../themes/'.$theme['theme_slug'];
    $theme['layout_file'] = $theme['directory'].'/'.$theme['page_layout'].'.phtml';


    // get contents
    $modelContents = new Application_Model_DbTable_Composer();
    $modelContents->setTableName('contents');
    $modelContents->setIdColumn('id_content');
    $contents = $modelContents->getAll(' WHERE `id_site` = '.$site['id_site'].' AND `id_page` = '.$page['id_page']);

    // get other contents
    // get sliders
    $modelSliders = new Application_Model_DbTable_Composer();
    $modelSliders->setTableName('sliders');
    $modelSliders->setIdColumn('id_slider');
    $sliders = $modelSliders->getAll(' WHERE `id_site` = '.$site['id_site'].' AND (`id_page` = '.$page['id_page'].' OR `id_page` = 0)');
    // generate slider contents
    //$PageSlider = array();
    if($sliders) {
      $modelSliderItems = new Application_Model_DbTable_Composer();
      $modelSliderItems->setTableName('slider_items');
      $modelSliderItems->setIdColumn('id_slider_item');

      foreach($sliders as $slider) {
        $sliderContent = '';
        $sliderItems = $modelSliderItems->getAll(' WHERE `id_slider` = '.$slider['id_slider']);

        if($sliderItems) {
          $sliderPagination = '';
          $sliderItemContent = '';

          foreach($sliderItems as $sliderItem) {
            $sliderItemContent .= '<div class="item active">';
              $sliderItemContent .= '<img src="'.$sliderItem['image_url'].'" alt="'.$sliderItem['alt_text'].'">';
              if($slider['show_item_description']) {
                $sliderItemContent .= '<div class="slider-data">';
                  $sliderItemContent .= $sliderItem['item_data'];
                $sliderItemContent .= '</div>';
              }
            $sliderItemContent .= '</div>';
          }

          $sliderContent = '<div id="carousel_'.$slider['id_slider'].'" class="carousel slide" data-ride="carousel">';
            $sliderContent .= '<div class="carousel-inner">';
              $sliderContent .= $sliderItemContent;
            $sliderContent .= '</div>';
          $sliderContent .= '</div>';
        }

        $contentsCount = count($contents);
        $contents[$contentsCount]['component_id'] = $slider['component_id'];
        $contents[$contentsCount]['component_type'] = 'slider';
        $contents[$contentsCount]['content'] = $sliderContent;
      }
    }


    //$this->utilities->debug($site);$this->utilities->debug($page);$this->utilities->debug($contents); exit();


    if(isset($site) && $site) $this->view->site = $site;
    if(isset($page) && $page) $this->view->page = $page;
    if(isset($theme) && $theme) $this->view->theme = $theme;
    if(isset($contents) && $contents) $this->view->contents = $contents;
  }

  public function errorAction() {
    $layout = $this->_helper->layout();
    $layout->setLayout('layout_cms_error');
  }

  public function _authenticateSite($site) {
    if($site['site_status'] != "active") return false;
    if($site['id_user'] == 0) return true;
  }

}
?>
