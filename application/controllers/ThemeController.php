<?php

class ThemeController extends Zend_Controller_Action {

  public function init() {
    /* Initialize action controller here */
    $layout = $this->_helper->layout();
    $layout->setLayout('layout_cms');
  }

  public function indexAction(){
    $theme['title'] = 'exp1';
    $activePage['template'] = 'home';
    $themeDir = APPLICATION_PATH.'/../themes/'.$theme['title'];

    $content['@headTitle'] = 'Expirement theme';
    $content['@headMeta'] = '<meta description="test theme">';
    $content['@siteTitle'] = 'Site Title';
    $content['@menu'] = '<ul class="nav navbar-nav">
      <li class="active"><a href="#">Home</a></li>
      <li><a href="#">About</a></li>
      <li><a href="#">Contact</a></li>
      <li class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Dropdown <span class="caret"></span></a>
        <ul class="dropdown-menu">
          <li><a href="#">Action</a></li>
          <li><a href="#">Another action</a></li>
          <li><a href="#">Something else here</a></li>
          <li role="separator" class="divider"></li>
          <li class="dropdown-header">Nav header</li>
          <li><a href="#">Separated link</a></li>
          <li><a href="#">One more separated link</a></li>
          <li class="dropdown-submenu">
            <a class="test" tabindex="-1" href="#">New dropdown <span class="caret"></span></a>
            <ul class="dropdown-menu">
              <li><a tabindex="-1" href="#">2nd level dropdown</a></li>
              <li><a tabindex="-1" href="#">2nd level dropdown</a></li>
              <li class="dropdown-submenu">
                <a class="test" href="#">Another dropdown <span class="caret"></span></a>
                <ul class="dropdown-menu">
                  <li><a href="#">3rd level dropdown</a></li>
                  <li><a href="#">3rd level dropdown</a></li>
                </ul>
              </li>
            </ul>
          </li>
        </ul>
      </li>
    </ul>';
    $content['@slider'] = '<div id="myCarousel" class="carousel slide" data-ride="carousel">
      <!-- Indicators -->
      <ol class="carousel-indicators">
        <li data-target="#myCarousel" data-slide-to="0" class="active"></li>
        <li data-target="#myCarousel" data-slide-to="1"></li>
        <li data-target="#myCarousel" data-slide-to="2"></li>
      </ol>

      <!-- Wrapper for slides -->
      <div class="carousel-inner">
        <div class="item active">
          <img src="'.$this->themeDir.'/img/img-1.jpeg" alt="">
          <div class="slider-data">
            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
            <button type="button" class="btn btn-warning" name="button">This is a button</button>
          </div>
        </div>

        <div class="item">
          <img src="'.$this->themeDir.'/img/img-2.jpeg" alt="">
          <div class="slider-data">
            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
            <button type="button" class="btn btn-warning" name="button">This is a button</button>
          </div>
        </div>

        <div class="item">
          <img src="'.$this->themeDir.'/img/img-3.jpeg" alt="">
          <div class="slider-data">
            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
            <button type="button" class="btn btn-warning" name="button">This is a button</button>
          </div>
        </div>
      </div>

      <!-- Left and right controls -->
      <a class="left carousel-control" href="#myCarousel" data-slide="prev">
        <span class="glyphicon glyphicon-chevron-left"></span>
        <span class="sr-only">Previous</span>
      </a>
      <a class="right carousel-control" href="#myCarousel" data-slide="next">
        <span class="glyphicon glyphicon-chevron-right"></span>
        <span class="sr-only">Next</span>
      </a>
    </div>
';
    if(isset($themeDir) && $themeDir) $this->view->themeDir = $themeDir;
    if(isset($activePage) && $activePage) $this->view->activePage = $activePage;
    if(isset($content) && $content) $this->view->content = $content;
  }

}
?>
