
$(document).on('change','.link_type_specifier_input',function(){
  var linkType = $(this).val();
  var linkTypeAttributeElement = '';
  var menuItemId = $(this).attr('id').split('-')[1];
  var siteId = $('#id_site').val();
  $.post('get-linktype-specifier-input',{id_site:siteId,id_menu_item:menuItemId,link_type:linkType},function(response){
    $('#menu_item_attribute_container').html(response);
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
  $.post('get-menu-items',{id_site:siteId,id_menu:menuId},function(response){
    $('#content_form_edit_menu').html(response);
    $('#nestable').nestable({
        group: 1
    }).on('change', updateOutputData);
    updateOutputData($('#nestable').data('output', $('#nestable-output')));
  });
});

function  updateOutputData(e) {
    var list   = e.length ? e : $(e.target),
    output = list.data('output');
    if (window.JSON) {
        output.val(window.JSON.stringify(list.nestable('serialize')));//, null, 2));
    }
}
