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
    if($site['site_status'] != "active") return false;
    if($site['id_user'] == 0) return true;
  }

}
?>
