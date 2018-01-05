$(document).on('click','.btn_add_content',function(){
  var elementId = $(this).attr('id');
  var elementComponents = elementId.split('-');
  var componentId = elementComponents[1];
  $('#modal_add_title').html('Add content for component : '+componentId);
  $('#component_id').val(componentId);
});

$(document).on('change','#component_type',function(){
  var componentType = $(this).val();
  if(componentType == 'slider' || componentType == 'html' || componentType == 'menu') {
    $('#modal_add_content').addClass('modal-lg');
  } else {
    $('#modal_add_content').removeClass('modal-lg');
  }

  var inputForm = getInputForm(componentType);
  $('#content_form').html(inputForm);
  if(componentType == 'html') {
    CKEDITOR.replace('content_input_html');
  }

});

$(document).on('click','#btn_submit_add_content',function(){
  $('#frmAddContent').submit();
  // var formData = $('#frmAddContent').serialize();
  // var htmlData = $('#content_input_html').val();
  // alert(htmlData);
  // alert(formData);
});

$(document).on('change','.link_type',function(){
  var linkType = $(this).val();
  var linkTypeAttributeElement = '';
  if(linkType == 'internal_page_link') {
    linkTypeAttributeElement = '<input type="text" class="form-control" placeholder="Enter target ID" name="link_type_attribute" id="link_type_attribute" value="" />';
    $('#menu_item_specifier_cont').html(linkTypeAttributeElement);
  } else if(linkType == 'external') {
    linkTypeAttributeElement = '<input type="text" class="form-control" placeholder="Enter external link" name="link_type_attribute" id="link_type_attribute" value="" />';
    $('#menu_item_specifier_cont').html(linkTypeAttributeElement);
  } else if(linkType == 'page_link') {
    // get pages
    var siteId = $('#id_site').val();
    $.post('get-pages-select-entry',{id_site:siteId},function(response){
      linkTypeAttributeElement = '<select name="link_type_attribute" id="link_type_attribute" class="form-control">';
      linkTypeAttributeElement += '<option value="">Select a page</option>';
      linkTypeAttributeElement += response;
      linkTypeAttributeElement += '</select>';
      $('#menu_item_specifier_cont').html(linkTypeAttributeElement);
    });
  }


});

function getInputForm(componentType) {
  var out = '';
  switch (componentType) {
    case 'slider':
      out += '<div class="row" >';
        out += '<div class="col-sm-4" >';
          out += '<div class="form-group">';
            out += '<label >Preffered image size</label>';
            out += '<input type="text" name="preffered_image_size" id="preffered_image_size"  class="form-control" />';
          out += '</div>';
        out += '</div>';

        out += '<div class="col-sm-8" >';
          out += '<div class="form-group">';
            out += '<label >Slider Properties</label><br>';
            out += '<label >Show pagination <input type="checkbox" name="show_pagination" id="show_pagination" checked="checked" /></label>&nbsp;&nbsp;';
            out += '<label >Show navigation <input type="checkbox" name="show_navigation" id="show_navigation" checked="checked" /></label>&nbsp;&nbsp;';
            out += '<label >Show text in slider <input type="checkbox" name="show_item_description" id="show_item_description" checked="checked" /></label>&nbsp;&nbsp;';
          out += '</div>';
        out += '</div>';
      out += '</div>';

      out += '<div class="row" style="overflow:auto;">';
        out += '<div class="col-sm-12" >';
          out += '<a style="cursor:pointer;" data-target="#galleryModal"  data-toggle="modal" class="text-success" id="btn_show_media_gallery"> <i class="fa fa-image"></i> Select Image</a>';
          out += '<div id="featured_image_cont" >';
          out += '</div>'; //featured_image_cont
        out += '</div>';
      out += '</div>';
      break;
    case 'image':
      out += '<div class="" >';
        out += '<a style="cursor:pointer;" data-target="#galleryModal"  data-toggle="modal" class="text-success" id="btn_show_media_gallery"> <i class="fa fa-image"></i> Select Image</a>';
        out += '<div id="featured_image_cont" >';
        out += '</div>'; //featured_image_cont
      out += '</div>';
      break;
    case 'text':
      out += '<div class="form-group">';
        out += '<label >Add text</label>';
        out += '<textarea name="content_input"  class="form-control"></textarea>';
      out += '</div>';
      break;
    case 'html':
      out += '<div class="form-group">';
        out += '<label >Add html</label>';
        out += '<textarea name="content_input" id="content_input_html" class="form-control"></textarea>';
      out += '</div>';
      break;
    case 'menu':
        out += '<div class="row">';
          out += '<div class="col-sm-6">';
            out += '<div class="form-group">';
              out += '<select class="form-control col-sm-6" name="menu_type" id="menu_type" id="menu_type">';
                out += '<option value="" >Select menu type</option>';
                out += '<option value="main_menu" >Main Menu</option>';
                out += '<option value="footer_menu" >Footer Menu</option>';
              out += '</select>';
            out += '</div>';
          out += '</div>'; // col 6
          out += '<div class="col-sm-6">';
            out += '<div class="form-group">';
              out += '<input type="text" name="menu_title" id="menu_title" class="form-control" placeholder="Menu Title" />';
              out += '</select>';
            out += '</div>';
          out += '</div>'; // col 6
        out += '</div>'; // row

        out += '<div class="menu_items_cont" id="menu_items_cont">';
          out += '<div class="row" >';
            out += '<div class="col-sm-12">';
              out += '<h4>Add a default menu item</h4>';
            out += '</div>';// col
          out += '</div>';// row
        out += '</div>';

        out += '<div class="menu_items_cont" id="menu_items_cont">';

          out += '<div class="row menu_items_row" id="menu_item_cont" >';
            out += '<div class="col-sm-5">';
              out += '<input type="text" name="title" id="title" class="form-control" placeholder="Title" />';
            out += '</div>';// col
            out += '<div class="col-sm-3">';
              out += '<select class="form-control" name="target" id="target">';
                out += '<option value="">Open in same window</option>';
                out += '<option value="_blank">Open in new window</option>';
              out += '</select>';
            out += '</div>';// col
            out += '<div class="col-sm-3">';
              out += '<select class="form-control link_type" name="link_type" id="link_type">';
                out += '<option value="">Select type</option>';
                out += '<option value="internal_page_link">Link to same page</option>';
                out += '<option value="page_link">Link to another page</option>';
                out += '<option value="external">External link</option>';
              out += '</select>';
            out += '</div>';// col

            out += '<div class="clear-fix "></div>';
            out += '<div class="col-sm-11 margin-top-sm" id="menu_item_specifier_cont">';
            out += '</div>';// col

          out += '</div>';// row

        out += '</div>';
        break;
    default:

  }

  return out;
}

$(document).on('change','#thumbnail',function(){
  $('#media_size_width').val('');
  $('#media_size_height').val('');
  if($(this).val() == 'custom') {
    $('#image_size_input_cont').show();
  } else {
    $('#image_size_input_cont').hide();
  }
});

$(document).on('click','.remove_featured',function(){
	var elementId = $(this).attr('id');
	var mediId = elementId.split('_')[2];
	$('#featured_img_box_'+mediId).remove();
});

$(document).on('click','#btn_set_fetured_image',function(){
  var imgHtml = '';
  if($('#component_type').val() == 'image' ) {
    imgHtml = getFeaturedImageHtml();
    $('#featured_image_cont').html($('#featured_image_cont').html()+imgHtml);
  } else if($('#component_type').val() == 'slider' ) {
    imgHtml = getSliderImageHtml();
    $('#featured_image_cont').html($('#featured_image_cont').html()+imgHtml);
    /*
    var mediaId = $('#id_media').val();
    CKEDITOR.replace( 'item_data_'+mediaId, {
			toolbarGroups: [
				{"name":"basicstyles","groups":["basicstyles"]},
				{"name":"links","groups":["links"]},
				{"name":"paragraph","groups":["list","blocks"]},
				{"name":"document","groups":["mode"]},
				{"name":"insert","groups":["insert"]},
				{"name":"styles","groups":["styles"]},
				{"name":"about","groups":["about"]}
			],
			removeButtons: 'Underline,Strike,Subscript,Superscript,Anchor,Styles,Specialchar'
		} );*/
  }
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
			imgHtml += '<br><a style="cursor:pointer" class="remove_featured text-info" id="remove_featured_'+mediaId+'" ><i class="fa fa-times" ></i> Remove</a>';
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

function getFeaturedImageHtml() {
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
			imgHtml += '<br><a style="cursor:pointer" class="remove_featured text-info" id="remove_featured_'+mediaId+'" ><i class="fa fa-times" ></i> Remove</a>';
			imgHtml += '<input type="hidden" value="'+mediaId+'" id="featured_images_'+mediaId+'" name="featured_images[]">';
    imgHtml += '</div>';
    imgHtml += '<div class="col-sm-8" >';
      imgHtml += '<div class="form-group">';
        imgHtml += '<label class="">Alt Text</label>';
        imgHtml += '<input name="alt_text" class="form-control" value="'+mediaAltText+'" id="alt_text_'+mediaId+'" placeholder="Alt Text" type="text">';
      imgHtml += '</div>';

      imgHtml += '<div class="form-group">';
        imgHtml += '<label class="">Image Size</label>';
        imgHtml += '<select name="thumbnail" id="thumbnail" class="form-control" >';
          imgHtml += '<option value="">Original Image</option>';
          imgHtml += '<option value="tiny">Tiny (Width 75)</option>';
          imgHtml += '<option value="tinysq">Tiny Square (Width 75 X Height 75)</option>';
          imgHtml += '<option value="small">Small (Width 150 X Height 150) </option>';
          imgHtml += '<option value="smallsq">Small Square (Width 150) </option>';
          imgHtml += '<option value="medium">Medium (Width 150)</option>';
          imgHtml += '<option value="custom">Custom Size</option>';
        imgHtml += '</select>';
      imgHtml += '</div>';

      imgHtml += '<div id="image_size_input_cont" style="display:none;">';
        imgHtml += '<div class="row" >';
          imgHtml += '<div class="col-sm-6" >';
            imgHtml += '<div class="form-group">';
              imgHtml += '<label class="">Width</label>';
              imgHtml += '<input type="text" name="media_size_width" id="media_size_width" class="form-control" />';
            imgHtml += '</div>'; // form-group
          imgHtml += '</div>'; // col

          imgHtml += '<div class="col-sm-6" >';
            imgHtml += '<div class="form-group">';
              imgHtml += '<label class="">Height</label>';
              imgHtml += '<input type="text" name="media_size_height" id="media_size_height" class="form-control" />';
            imgHtml += '</div>'; // form-group
          imgHtml += '</div>'; // col

        imgHtml += '</div>'; // row
      imgHtml += '</div>'; //image_size_input_cont

    imgHtml += '</div>';
  imgHtml += '</div>'; // row

  return imgHtml;
}

$(document).on('click','.media_image',function(){
	$('.media_image').removeClass('media_image_selected');
	$(this).addClass('media_image_selected');
	var elementId = $(this).attr('id');
	var mediId = elementId.split('_')[1];
	var targetUrl = '../media/async-get-media';
	$.post(targetUrl,{id_media:mediId},function(response){
		data = $.parseJSON(response);
		if(data.status == 'ERROR') {
			$('#media_cont').html(data.data.message);
		} else if(data.status == 'OK') {
			$('#media_cont').html(composeMediaHtml(data.data.media));
		}
	});
});

$(document).on('click','#btn_edit_media',function(){
	$('#btn_edit_media').html('<i class="fa fa-spinner fa-spin fa-fw margin-bottom"></i>');
	$('#btn_edit_media').attr('disabled',true);
	var mediaCaption = $('#caption').val();
	var mediaAltText = $('#alt_text').val();
	var mediaId = $('#id_media').val();
	var targetUrl = '../media/async-update-media';
	$.post(targetUrl,{id_media:mediaId,caption:mediaCaption,alt_text:mediaAltText},function(response){
		data = $.parseJSON(response);
		if(data.status == 'ERROR') {
			alert(data.data.message);
		}
		$('#btn_edit_media').html('Update');
		$('#btn_edit_media').attr('disabled',false);
	});
});

function composeMediaHtml(media) {
	var out = '<h4>Media Details</h4>';
	out += '<img src="'+media.file_directory+media.file_name+'-small.'+media.file_extension+'" />';
	out += '<br>'+media.file_name+'.'+media.file_extension;
	out += '<br><i class="fa fa-calendar"></i> <b>'+media.uploaded_on+'</b>';
	out += '<br> Size : <b>'+media.file_size+' B</b>';
	out += '<hr>';
	out += '<label>Caption</label>';
	out += '<textarea type="text" id="caption" name="caption" class="form-control" rows="1">'+media.caption+'</textarea>';
	out += '<label>Alt Text</label>';
	out += '<input type="text" value="'+media.alt_text+'" id="alt_text" name="alt_text" class="form-control">';
	out += '<br><button class="btn btn-sm btn-primary" id="btn_edit_media" type="button">Update</button>';
	out += '<input type="hidden" value="'+media.id_media+'" id="id_media" name="id_media" />';

  out += '<input type="hidden" value="'+media.alt_text+'" id="media_alt_text" name="media_alt_text">';
	out += '<input type="hidden" value="'+media.file_extension+'" id="file_extension" name="file_extension">';
	out += '<input type="hidden" value="'+media.file_directory+'" id="file_directory" name="file_directory">';
	out += '<input type="hidden" value="'+media.file_name+'" id="media_name" name="media_name">';
	out += '<input type="hidden" value="'+media.file_name+'" id="file_name" name="file_name">';
	return out;
}

$(document).on('click','#btn_show_media_gallery,#btn_search_media',function(){
	$('#btn_search_media').html('<i class="fa fa-spinner fa-spin fa-fw margin-bottom"></i>');
	$('#btn_search_media').attr('disabled',true);
	var fileName = $('#file_name').val();
	var dateFrom = $('#date_from').val();
	var dateTo = $('#date_to').val();
	var targetUrl = '../media/async-get-media-library';
	$.post(targetUrl,{file_name:fileName,date_from:dateFrom,date_to:dateTo},function(response) {
		$('#library_cont').html(response);
		$('#btn_search_media').html('Filter');
		$('#btn_search_media').attr('disabled',false);
	});
});

$(document).on('click','#btn_add_media',function(){
	$('#upload_frm_cont').toggle();
});

$(document).on('click','.close_upload_frm_cont',function(){
	$('#upload_frm_cont').toggle();
});
