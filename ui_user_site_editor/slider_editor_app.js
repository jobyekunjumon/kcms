$(document).on('click','#btn_save_slider',function(){
  var targetUrl = baseUrl+'/user/process-live-edit/update-slider';
  var frmData = $('#frmSlider').serialize();
  $.post(targetUrl,frmData,function(response){

    data = $.parseJSON(response);
		if(data.status == '0') {
			alert(data.message);
		} else if(data.status == '1') {
			var redirect = baseUrl+'/user/edit-site/show-site?name='+data.site+'&page='+data.page;
      window.location.href = redirect;
		}
  });
});

$(document).on('click','.btn_change_slider_images',function(){
  var elementId = $(this).attr('id');
  var contentId = elementId.split('-')[1];
  var contentType = elementId.split('-')[0].split('_')[2];

  var targetUrl = baseUrl+'/user/process-live-edit/get-slider-images';
  $.post(targetUrl,{id_slider:contentId},function(response){
    data = $.parseJSON(response);
		if(data.status == '0') {
			$('#featured_image_cont').html(data.message);
		} else if(data.status == '1') {
			$('#featured_image_cont').html(data.content);
		}
  });
});

$(document).on('click','.btn_edit_slider',function(){
  var elementId = $(this).attr('id');
  var contentId = elementId.split('-')[1];
  var contentType = elementId.split('-')[0].split('_')[2];

  originalContent[contentId] = $('#slidereditable-'+contentId).html();
  $('#rootcont_edit_'+contentType+'-'+contentId).removeClass('editable_slider');
  $('#rootcont_edit_'+contentType+'-'+contentId).addClass('editable_slider_active');
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
  $('#id_slider').val(contentId);
  var galleryModalFooterButtons = '<button type="button" class="btn btn-default btn-sm" data-dismiss="modal"><i class="fa fa-times"></i> Close</button>';
  galleryModalFooterButtons += '<button type="button" class="btn btn-primary btn-sm btn_select_sliderimage" id="btn_select_sliderimage-'+contentId+'" data-dismiss="modal" ><i class="fa fa-check"></i> Select Image</button>';
  $('#gallery_modal_footer').html(galleryModalFooterButtons);

});

$(document).on('click','.btn_cancel_slider_edit',function(){
  var elementId = $(this).attr('id');
  var contentId = elementId.split('-')[1];
  var contentType = elementId.split('-')[0].split('_')[2];
  $('#slidereditable-'+contentId).html(originalContent[contentId]);
  $('#rootcont_edit_'+contentType+'-'+contentId).removeClass('editable_slider_active');
  $('#rootcont_edit_'+contentType+'-'+contentId).addClass('editable_slider');
});

$(document).on('click','#btn_show_media_gallery',function(){
	$('#btn_search_stock_media').html('<i class="fa fa-spinner fa-spin fa-fw"></i>');
	$('#btn_search_stock_media').attr('disabled',true);

	var targetUrl = baseUrl+'/user/media/async-get-media-library';
	$.post(targetUrl,function(response) {
		$('#stock_media_library_cont').html(response);
		$('#btn_search_stock_media').html('Search');
		$('#btn_search_stock_media').attr('disabled',false);
	});
});

$(document).on('click','#btn_show_media_gallery',function(){
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

$(document).on('click','.btn_select_sliderimage',function(){
  var imgHtml = '';
  imgHtml = getSliderImageHtml();
  $('#featured_image_cont').append(imgHtml);
});

$(document).on('click','.remove_featured',function(){
	var elementId = $(this).attr('id');
	var mediId = elementId.split('_')[2];
	$('#featured_img_box_'+mediId).remove();
});

function getSliderImageHtml() {
  var mediaId = $('#id_media').val();
	var fileName = $('#media_name').val();
	var fileDirectory = $('#file_directory').val();
	var fileExtension = $('#file_extension').val();
	var fileUrl = fileDirectory+fileName+'-small.'+fileExtension;
  var mediaAltText = $('#media_alt_text').val();

	var imgHtml = '';
	imgHtml = '<div id="featured_img_box_'+mediaId+'" class="row"  style="border:1px dashed #2d2d2d; margin:5px 0; padding:2px;" >';
    imgHtml += '<div class="col-sm-4" >';
      imgHtml += '<img class="featured_image" id="featured_image_'+mediaId+'" src="'+fileUrl+'" style="max-width:100%" >';
			imgHtml += '<br><a style="cursor:pointer" class="remove_featured text-danger" id="remove_featured_'+mediaId+'" ><i class="fa fa-times" ></i> Remove</a>';
			imgHtml += '<input type="hidden" value="'+mediaId+'" id="featured_images_'+mediaId+'" name="featured_images[]">';
    imgHtml += '</div>';
    imgHtml += '<div class="col-sm-8" >';

      imgHtml += '<div class="form-group">';
        imgHtml += '<label class="">Alt Text</label>';
        imgHtml += '<input name="alt_text[]" class="form-control" value="'+mediaAltText+'" id="alt_text_'+mediaId+'" placeholder="Alt Text" type="text">';
      imgHtml += '</div>';

      imgHtml += '<div class="form-group">';
        imgHtml += '<label class="">Slider Caption</label>';
        imgHtml += '<textarea name="item_data[]" class="form-control item_data"  id="item_data_'+mediaId+'" placeholder="Slider caption" ></textarea>';
      imgHtml += '</div>';

    imgHtml += '</div>';
  imgHtml += '</div>'; // row

  return imgHtml;
}
