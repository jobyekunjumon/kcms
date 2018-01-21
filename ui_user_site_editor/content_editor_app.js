var originalContent = new Array();

$(document).on('click','.btn_edit_text',function(){
  var elementId = $(this).attr('id');
  var contentId = elementId.split('-')[1];
  var contentType = elementId.split('-')[0].split('_')[2];
  originalContent[contentId] = $('#contenteditable-'+contentId).html();
  $('#contenteditable-'+contentId).attr('contenteditable','true');
  $('#rootcont_edit_'+contentType+'-'+contentId).removeClass('editable_text');
  $('#rootcont_edit_'+contentType+'-'+contentId).addClass('editable_text_active');
  var containerWidth = $('#rootcont_edit_'+contentType+'-'+contentId).width();
  if(containerWidth < 150) {
    $('#btn_save_'+contentType+'-'+contentId).html('<i class="fa fa-save"></i>');
    $('#btn_cancel_'+contentType+'-'+contentId).html('<i class="fa fa-times"></i>');
    $('#btn_edit_'+contentType+'-'+contentId).html('<i class="fa fa-edit"></i>');
    $('#btn_delete_'+contentType+'-'+contentId).html('<i class="fa fa-trash"></i>');
  } else {
    $('#btn_save_'+contentType+'-'+contentId).html('<i class="fa fa-save"></i> Save');
    $('#btn_cancel_'+contentType+'-'+contentId).html('<i class="fa fa-times"></i> Cancel');
    $('#btn_edit_'+contentType+'-'+contentId).html('<i class="fa fa-edit"></i> Edit');
    $('#btn_delete_'+contentType+'-'+contentId).html('<i class="fa fa-trash"></i> Delete');
  }
});
$(document).on('click','.btn_cancel_content_edit',function(){
  var elementId = $(this).attr('id');
  var contentId = elementId.split('-')[1];
  var contentType = elementId.split('-')[0].split('_')[2];
  $('#contenteditable-'+contentId).html(originalContent[contentId]);
  $('#contenteditable-'+contentId).attr('contenteditable','false');
  if(contentType == 'text' || contentType == 'html' ) {
    $('#rootcont_edit_'+contentType+'-'+contentId).removeClass('editable_text_active');
    $('#rootcont_edit_'+contentType+'-'+contentId).addClass('editable_text');
  } else {
    $('#rootcont_edit_'+contentType+'-'+contentId).removeClass('editable_'+contentType+'_active');
    $('#rootcont_edit_'+contentType+'-'+contentId).addClass('editable_'+contentType);
  }
});
$(document).on('click','.btn_save_text',function(){
  var elementId = $(this).attr('id');
  var contentId = elementId.split('-')[1];
  var contentType = elementId.split('-')[0].split('_')[2];
  var editedContent = $('#contenteditable-'+contentId).html();
  if(editedContent != originalContent) {
    var targetUrl = baseUrl+'/user/process-live-edit/save-text';
    $.post(targetUrl,{id_content:contentId,content:editedContent},function(response){
      var responseData = $.parseJSON(response);
      if(responseData.status == '1') {
        $('#contenteditable-'+contentId).html(editedContent);
        $('#contenteditable-'+contentId).attr('contenteditable','false');

        if(contentType == 'text' || contentType == 'html' ) {
          $('#rootcont_edit_'+contentType+'-'+contentId).removeClass('editable_text_active');
          $('#rootcont_edit_'+contentType+'-'+contentId).addClass('editable_text');
        } else {
          $('#rootcont_edit_'+contentType+'-'+contentId).removeClass('editable_'+contentType+'_active');
          $('#rootcont_edit_'+contentType+'-'+contentId).addClass('editable_'+contentType);
        }
      } else {
        alert(responseData.message);
        $('#contenteditable-'+contentId).html(originalContent);
        $('#contenteditable-'+contentId).attr('contenteditable','false');
        if(contentType == 'text' || contentType == 'html' ) {
          $('#rootcont_edit_'+contentType+'-'+contentId).removeClass('editable_text_active');
          $('#rootcont_edit_'+contentType+'-'+contentId).addClass('editable_text');
        } else {
          $('#rootcont_edit_'+contentType+'-'+contentId).removeClass('editable_'+contentType+'_active');
          $('#rootcont_edit_'+contentType+'-'+contentId).addClass('editable_'+contentType);
        }
      }
    });
  } else {
    $('#contenteditable-'+contentId).html(originalContent);
    $('#contenteditable-'+contentId).attr('contenteditable','false');
    if(contentType == 'text' || contentType == 'html' ) {
      $('#rootcont_edit_'+contentType+'-'+contentId).removeClass('editable_text_active');
      $('#rootcont_edit_'+contentType+'-'+contentId).addClass('editable_text');
    } else {
      $('#rootcont_edit_'+contentType+'-'+contentId).removeClass('editable_'+contentType+'_active');
      $('#rootcont_edit_'+contentType+'-'+contentId).addClass('editable_'+contentType);
    }
  }
});

$(document).on('click','.btn_edit_image',function(){
  var elementId = $(this).attr('id');
  var contentId = elementId.split('-')[1];
  var contentType = elementId.split('-')[0].split('_')[2];
  originalContent = $('#contenteditable-'+contentId).html();
  $('#contenteditable-'+contentId).attr('contenteditable','true');
  $('#rootcont_edit_'+contentType+'-'+contentId).removeClass('editable_image');
  $('#rootcont_edit_'+contentType+'-'+contentId).addClass('editable_image_active');
  var containerWidth = $('#rootcont_edit_'+contentType+'-'+contentId).width();
  if(containerWidth < 150) {
    $('#btn_save_'+contentType+'-'+contentId).html('<i class="fa fa-save"></i>');
    $('#btn_cancel_'+contentType+'-'+contentId).html('<i class="fa fa-times"></i>');
    $('#btn_edit_'+contentType+'-'+contentId).html('<i class="fa fa-edit"></i>');
    $('#btn_delete_'+contentType+'-'+contentId).html('<i class="fa fa-trash"></i>');
  } else {
    $('#btn_save_'+contentType+'-'+contentId).html('<i class="fa fa-save"></i> Save');
    $('#btn_cancel_'+contentType+'-'+contentId).html('<i class="fa fa-times"></i> Cancel');
    $('#btn_edit_'+contentType+'-'+contentId).html('<i class="fa fa-edit"></i> Edit');
    $('#btn_delete_'+contentType+'-'+contentId).html('<i class="fa fa-trash"></i> Delete');
  }
});
