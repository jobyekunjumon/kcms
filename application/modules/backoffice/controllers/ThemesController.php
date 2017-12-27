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
    $breadcrumbs[1] = array('title' => 'Categories', 'link' => $this->view->baseUrl().'/backoffice/categories', 'status' => '' );
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
  ////////////////////////////////////////////////////////////////////
  //////////////   HELPER FYNCTIONS //////////////////////////////////
  ////////////////////////////////////////////////////////////////////

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
