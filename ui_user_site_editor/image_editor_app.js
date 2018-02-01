var originalContent = new Array();

$("div#dropzoneContainer").dropzone({
  url: baseUrl+"/user/media/async-upload-media",
  paramName: "file",
  maxFilesize: 1, // MB
});

$(document).on('click','.btn_edit_image',function(){
  var elementId = $(this).attr('id');
  var contentId = elementId.split('-')[1];
  var contentType = elementId.split('-')[0].split('_')[2];

  originalContent[contentId] = $('#imageeditable-'+contentId).html();
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
  var galleryModalFooterButtons = '<button type="button" class="btn btn-default btn-sm" data-dismiss="modal"><i class="fa fa-times"></i> Close</button>';
  galleryModalFooterButtons += '<button type="button" class="btn btn-primary btn-sm btn_select_image" id="btn_select_image-'+contentId+'" data-dismiss="modal" ><i class="fa fa-check"></i> Select Image</button>';
  $('#gallery_modal_footer').html(galleryModalFooterButtons);
});

$(document).on('click','.btn_cancel_image_edit',function(){
  var elementId = $(this).attr('id');
  var contentId = elementId.split('-')[1];
  var contentType = elementId.split('-')[0].split('_')[2];
  $('#imageeditable-'+contentId).html(originalContent[contentId]);
  $('#rootcont_edit_'+contentType+'-'+contentId).removeClass('editable_image_active');
  $('#rootcont_edit_'+contentType+'-'+contentId).addClass('editable_image');
});

$(document).on('click','.btn_change_image,#btn_search_stock_media',function(){
	$('#btn_search_stock_media').html('<i class="fa fa-spinner fa-spin fa-fw"></i>');
	$('#btn_search_stock_media').attr('disabled',true);

	var targetUrl = baseUrl+'/user/media/async-get-media-library';
	$.post(targetUrl,function(response) {
		$('#stock_media_library_cont').html(response);
		$('#btn_search_stock_media').html('Search');
		$('#btn_search_stock_media').attr('disabled',false);
	});
});

$(document).on('click','.btn_change_image,#btn_search_user_media,#my_images_tab_selector',function(){
	$('#btn_search_user_media').html('<i class="fa fa-spinner fa-spin fa-fw"></i>');
	$('#btn_search_user_media').attr('disabled',true);

  var userId = $('#id_user').val();
	var targetUrl = baseUrl+'/user/media/async-get-media-library';
	$.post(targetUrl,{id_user:userId},function(response) {
		$('#user_media_library_cont').html(response);
		$('#btn_search_user_media').html('Search');
		$('#btn_search_user_media').attr('disabled',false);
	});
});

$(document).on('click','.media_image',function(){
	$('.media_image').removeClass('media_image_selected');
	$(this).addClass('media_image_selected');
	var elementId = $(this).attr('id');
	var idMedia = elementId.split('_')[1];

	var targetUrl = baseUrl+'/user/media/async-get-media';
	$.post(targetUrl,{id_media:idMedia},function(response){
		data = $.parseJSON(response);
		if(data.status == 'ERROR') {
			$('#media_cont').html(data.data.message);
		} else if(data.status == 'OK') {
			$('#media_cont').html(composeMediaHtml(data.data.media));
		}
	});
});

$(document).on('click','#btn_edit_media',function(){
	$('#btn_edit_media').html('<i class="fa fa-spinner fa-spin fa-fw"></i>');
	$('#btn_edit_media').attr('disabled',true);
	var mediaCaption = '';
	var mediaAltText = $('#alt_text').val();
	var mediaId = $('#id_media').val();
	var targetUrl = baseUrl+'/user/media/async-update-media';
	$.post(targetUrl,{id_media:mediaId,caption:mediaCaption,alt_text:mediaAltText},function(response){
		data = $.parseJSON(response);
		if(data.status == 'ERROR') {
			$('#edit_media_status_msg').html(data.data.message);
		} else {
      $('#edit_media_status_msg').html('<span style="color:green"> <i class="fa fa-check"></i> Updated</span>');
    }
		$('#btn_edit_media').html('Update');
		$('#btn_edit_media').attr('disabled',false);
	});
});

$(document).on('click','.btn_select_image',function(){
  var elementId = $(this).attr('id');
  var contentId = elementId.split('-')[1];
  var contentType = elementId.split('-')[0].split('_')[2];
  var mediaId = $('#id_media').val();
  var mediaCaption = $('#caption').val();
	var mediaAltText = $('#alt_text').val();
  var mediaThumbnail = $('#thumbnail').val();
  var mediaWidth = $('#media_size_width').val();
  var mediaHeight = $('#media_size_height').val();

  var targetUrl = baseUrl+'/user/process-live-edit/update-site-media';
	$.post(targetUrl,{id_media:mediaId,id_content:contentId,alt_text:mediaAltText,thumbnail:mediaThumbnail,media_size_width:mediaWidth,media_size_height:mediaHeight},function(response){
		data = $.parseJSON(response);
		if(data.status == 'ERROR') {
			alert(data.message);
		} else {
      $('#imageeditable-'+contentId).html(data.updated_content);
      $('#rootcont_edit_'+contentType+'-'+contentId).removeClass('editable_image_active');
      $('#rootcont_edit_'+contentType+'-'+contentId).addClass('editable_image');
    }
	});
});

$(document).on('change','#thumbnail',function(){
  $('#media_size_width').val('');
  $('#media_size_height').val('');
  if($(this).val() == 'custom') {
    $('#image_size_input_cont').show();
  } else {
    $('#image_size_input_cont').hide();
  }
});

function composeMediaHtml(media) {
	var out = '<h4>Media Details</h4>';
	out += '<img src="'+media.file_directory+media.file_name+'-small.'+media.file_extension+'" />';
	out += '<br>'+media.file_name+'.'+media.file_extension;
	out += '<br><i class="fa fa-calendar"></i> <b>'+media.uploaded_on+'</b>';
	out += '<br> Size : <b>'+media.file_size+' B</b>';
	out += '<hr>';
	out += '<label>Alt Text</label>';
	out += '<input type="text" value="'+media.alt_text+'" id="alt_text" name="alt_text" class="form-control">';

  out += '<div class="form-group">';
    out += '<label class="">Image Size</label>';
    out += '<select name="thumbnail" id="thumbnail" class="form-control" >';
      out += '<option value="">Original Image</option>';
      out += '<option value="tiny">Tiny (Width 75)</option>';
      out += '<option value="tinysq">Tiny Square (Width 75 X Height 75)</option>';
      out += '<option value="small">Small (Width 150 X Height 150) </option>';
      out += '<option value="smallsq">Small Square (Width 150) </option>';
      out += '<option value="medium">Medium (Width 150)</option>';
      out += '<option value="custom">Custom Size</option>';
    out += '</select>';
  out += '</div>';

  out += '<div id="image_size_input_cont" style="display:none;">';
    out += '<div class="row" >';
      out += '<div class="col-sm-6" >';
        out += '<div class="form-group">';
          out += '<label class="">Width</label>';
          out += '<input type="text" name="media_size_width" id="media_size_width" class="form-control" />';
        out += '</div>'; // form-group
      out += '</div>'; // col

      out += '<div class="col-sm-6" >';
        out += '<div class="form-group">';
          out += '<label class="">Height</label>';
          out += '<input type="text" name="media_size_height" id="media_size_height" class="form-control" />';
        out += '</div>'; // form-group
      out += '</div>'; // col
    out += '</div>'; // row
  out += '</div>'; // image_size_input_cont

	//if(media.id_user != 0 ) out += '<br><button class="btn btn-sm btn-primary" id="btn_edit_media" type="button">Update</button><span id="edit_media_status_msg"></span>';
	out += '<input type="hidden" value="'+media.id_media+'" id="id_media" name="id_media" />';
  out += '<input type="hidden" value="'+media.alt_text+'" id="media_alt_text" name="media_alt_text">';
	out += '<input type="hidden" value="'+media.file_extension+'" id="file_extension" name="file_extension">';
	out += '<input type="hidden" value="'+media.file_directory+'" id="file_directory" name="file_directory">';
	out += '<input type="hidden" value="'+media.file_name+'" id="media_name" name="media_name">';
	out += '<input type="hidden" value="'+media.file_name+'" id="file_name" name="file_name">';
	return out;
}
