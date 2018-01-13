$(document).on('click','#btn_submit_add_page',function(){
  $('#add_page_status_cont').html('&nbsp;&nbsp;<i class="fa fa-spinner fa-spin fa-fw"></i> Updating changes');
  var siteId = $('#id_site').val();
  var menuId = $('#id_menu').val();
  var pageTitle = $('#page_title').val();
  var keywords = ($('#keywords').val())?$('#keywords').val():'';
  var pageLayout = $('#page_layout').val();
  $.post('add-page',{id_site:siteId,id_menu:menuId,page_title:pageTitle,keywords:keywords,page_layout:pageLayout},function(response){
    var outData = $.parseJSON(response);
    if(outData.status == 0 ) $('#add_page_status_cont').html(outData.message);
    else if(outData.status == 1 ) {
      $("#modal_add_pages").modal('toggle');
      getMenuItems(siteId,menuId);
    }
    $('#add_page_status_cont').html('');
  });

});

$(document).on('click','.layout_image_wrapper',function(){
  var layout = $(this).attr('id');
  $('.layout_image_wrapper').removeClass('selected');
  $('#'+layout).addClass('selected');
  $('#page_layout').val(layout);
});

$(document).on('click','#btn_add_page',function(){
  $("#modal_add_pages").modal('show');
});

$(document).on('click','.btn_delete_menu_item',function(){
  $('#btn_update_menu_order_status_cont').html('&nbsp;&nbsp;<i class="fa fa-spinner fa-spin fa-fw"></i> Updating changes');
  var menuItemId = $(this).attr('id').split('-')[1];
  var siteId = $('#id_site').val();
  var menuId = $('#id_menu').val();
  $.post('delete-menu-item',{id_site:siteId,id_menu_item:menuItemId},function(response){

    if(response == "1") {
      $('#btn_update_menu_order_status_cont').html('<span style="color:green;" >&nbsp;&nbsp;<i class="fa fa-check"></i> Menu item deleted successfully.</span>');
      getMenuItems(siteId,menuId);
    } else $('#btn_update_menu_order_status_cont').html('<span style="color:red;">&nbsp;&nbsp;<i class="fa fa-times"></i> Failed to delete menu item.</span>');
  });
});

$(document).on('click','#btn_update_menu_order',function(){
  $('#btn_update_menu_order_status_cont').html('&nbsp;&nbsp;<i class="fa fa-spinner fa-spin fa-fw"></i> Updating changes');
  var menuId = $('#id_menu').val();
  var siteId = $('#id_site').val();
  var menuOrder = $('#nestable-output').val();
  $.post('update-menu-order',{id_site:siteId,id_menu:menuId,menu_order:menuOrder},function(response){
    if(response == "1") $('#btn_update_menu_order_status_cont').html('<span style="color:green;" >&nbsp;&nbsp;<i class="fa fa-check"></i> Menu order changed successfully.</span>');
    else $('#btn_update_menu_order_status_cont').html('<span style="color:red;">&nbsp;&nbsp;<i class="fa fa-times"></i> Failed to update menu order.</span>');
  });

});

$(document).on('click','#btn_save_menu_item_changes',function(){
  $('#btn_save_menu_item_changes').html('<i class="fa fa-spinner fa-spin fa-fw"></i>');
  var menuId = $('#id_menu').val();
  var siteId = $('#id_site').val();
  var title = $('#title').val();
  var target = $('#target').val();
  var linkType = $('.link_type_specifier_input').val();
  var linkTypeAttribute = $('#link_type_attribute').val();
  var idToEdit = $('#id_to_edit').val();
  var menuItemId = idToEdit;
  $.post('get-edit-menu-form',{id_menu_item:menuItemId,action:'add_menu_item',id_menu:menuId,id_site:siteId,id_to_edit:idToEdit,title:title,target:target,link_type:linkType,link_type_attribute:linkTypeAttribute},function(response){
    $('#menu_item_details_cont').html(response);
    getMenuItems(siteId,menuId);
  });
});

$(document).on('change','.link_type_specifier_input',function(){
  var linkType = $(this).val();
  var linkTypeAttributeElement = '';
  var menuItemId = $(this).attr('id').split('-')[1];
  var siteId = $('#id_site').val();
  $.post('get-linktype-specifier-input',{id_site:siteId,id_menu_item:menuItemId,link_type:linkType},function(response){
    $('#menu_item_attribute_container').html(response);
  });
});

$(document).on('click','#btn_add_menu_item',function(){
  var siteId = $('#id_site').val();
  $.post('get-edit-menu-form',{id_site:siteId},function(response){
    $('#menu_item_details_cont').html(response);
  });
});

$(document).on('click','.btn_view_menu_item',function(){
  var menuItemId = $(this).attr('id').split('-')[1];
  var siteId = $('#id_site').val();
  $.post('get-edit-menu-form',{id_site:siteId,id_menu_item:menuItemId},function(response){
    $('#menu_item_details_cont').html(response);
  });
});

$(document).on('click','.btn_edit_content_menu',function(){
  $('#modal_edit_menu').modal('show');
  var menuId = $(this).attr('id').split('-')[1];
  var siteId = $('#id_site').val();
  $('#id_menu').val(menuId);
  getMenuItems(siteId,menuId);
});

function getMenuItems(siteId,menuId) {
  $.post('get-menu-items',{id_site:siteId,id_menu:menuId},function(response){
    $('#content_form_edit_menu').html(response);
    $('#nestable').nestable({
        group: 1
    }).on('change', updateOutputData);
    updateOutputData($('#nestable').data('output', $('#nestable-output')));
  });
}

function  updateOutputData(e) {
    var list   = e.length ? e : $(e.target),
    output = list.data('output');
    if (window.JSON) {
        output.val(window.JSON.stringify(list.nestable('serialize')));//, null, 2));
    }
}
