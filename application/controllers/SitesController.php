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


    $site = $modelSites->getRowByCondition(' `site_slug` = "'.$siteSlug.'"');
    if(!$site) $this->_redirect('/sites/error');
    if(!$this->_authenticateSite($site)) $this->_redirect('/sites/error');  // check whether this an active site  // check subscriptions and site status

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
    $contents = $modelContents->getAll(' WHERE `id_site` = '.$site['id_site'].' AND (`id_page` = '.$page['id_page'].' OR `id_page` = 0)');

    //////////////////////////////////////////
    //             GET OTHER CONTENTS       //
    //////////////////////////////////////////

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

  //////////////////////////////////////////////////////////
  /////////  HELPER FUNCTION //////////////////////////////
  /////////////////////////////////////////////////////////

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
    if($site['site_status'] != "active") return false;
    if($site['id_user'] == 0) return true;
  }

}
?>
