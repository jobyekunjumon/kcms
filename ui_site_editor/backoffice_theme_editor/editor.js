var modalsActive = 0;
$(document).on('shown.bs.modal', function () {
  ++modalsActive;
});
$(document).on('hidden.bs.modal', function () {
  if (--modalsActive > 0) {
    $('body').addClass('modal-open');
  }
});


$(document).on('click','.btn_delete_content',function(){
  var elementId = $(this).attr('id');
  var elementComponents = elementId.split('-');
  $('#id_content_to_delete').val(elementComponents[2]);
  $('#type_content_to_delete').val(elementComponents[1]);
  $('#modal-delete-confirm').modal('show');
});

$(document).on('click','#btn_confirm_deletion',function() {
  $('#btn_delete_content_status_cont').html('&nbsp;&nbsp;<i class="fa fa-spinner fa-spin fa-fw"></i> Updating changes');
  var siteId = $('#id_site').val();
  var contentId = $('#id_content_to_delete').val();
  var contentType = $('#type_content_to_delete').val();
  $.post('delete-content',{id_site:siteId,id_content:contentId,content_type:contentType},function(response){
    var outData = $.parseJSON(response);
    if(outData.status == 0 ) $('#btn_delete_content_status_cont').html(outData.message);
    else if(outData.status == 1 ) {
      $('#btn_delete_content_status_cont').html(outData.message);
      timedRefresh(500);
    }
  });
});

$(document).on('click','.btn_add_content',function(){
  var elementId = $(this).attr('id');
  var elementComponents = elementId.split('-');
  var componentId = elementComponents[1];
  $('#modal_add_title').html('Add content for component : '+componentId);
  $('#component_id').val(componentId);
});

$(document).on('change','#component_type',function(){
  var componentType = $(this).val();
  if(componentType == 'slider' || componentType == 'html' || componentType == 'menu' || componentType == 'form' ) {
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
    case 'map':
        out += '<div class="form-group">';
          out += '<label >Add map iFrame code</label>';
          out += '<textarea name="content_input"  class="form-control"></textarea>';
        out += '</div>';
        break;
    case 'html':
      out += '<div class="form-group">';
        out += '<label >Add html</label>';
        out += '<textarea name="content_input" id="content_input_html" class="form-control"></textarea>';
      out += '</div>';
      break;
    case 'form':
      out += '<div class="row">';
        out += '<div class="col-sm-4">';
          out += '<div class="form-group">';
            out += '<input type="text" name="form_name" id="form_name" class="form-control" placeholder="Form Name" />';
            out += '</select>';
          out += '</div>';
        out += '</div>'; // col 6

        out += '<div class="col-sm-4">';
          out += '<div class="form-group">';
            out += '<select class="form-control col-sm-6" name="form_type" id="form_type" >';
              out += '<option value="" >Select form type</option>';
              out += '<option value="contact_form" >Contact form</option>';
              out += '<option value="signup_form" >Signup form</option>';
              out += '<option value="login_form" >Login form</option>';
              out += '<option value="newsletter_form" >News letter subscription</option>';
              out += '<option value="other_form" >Other</option>';
            out += '</select>';
          out += '</div>';
        out += '</div>'; // col 6

        out += '<div class="col-sm-4">';
          out += '<div class="form-group">';
            out += '<select class="form-control col-sm-6" name="data_handler" id="menu_type" id="data_handler">';
              out += '<option value="" >How to handle data ?</option>';
              out += '<option value="email" >Send email to admin</option>';
              out += '<option value="save" >Save to database</option>';
              out += '<option value="save_and_email" >Email and save to database</option>';
            out += '</select>';
          out += '</div>';
        out += '</div>'; // col 6

      out += '</div>'; // row

      out += '<div class="" id="">';
        out += '<div class="row" >';
          out += '<div class="col-sm-12">';
            out += '<h4>Add form items</h4>';
          out += '</div>';// col
        out += '</div>';// row
      out += '</div>';
      var formElemetCount = 1;
      out += '<div class="form_items_cont" id="form_items_cont">';

        out += '<div class="row form_item_row" id="form_item_row_'+formElemetCount+'" style="margin-bottom:20px;">';
          out += '<div class="col-sm-2">';
            out += '<input type="text" name="elements['+formElemetCount+'][element_name]" id="element_name_'+formElemetCount+'" class="form-control" placeholder="Name" />';
          out += '</div>';// col
          out += '<div class="col-sm-2">';
            out += '<select class="form-control" name="elements['+formElemetCount+'][element_type]" id="element_type_'+formElemetCount+'">';
              out += '<option value="text">Text box</option>';
              out += '<option value="textarea">Text Area</option>';
              out += '<option value="submit">Submit Button</option>';
              out += '<option value="cancel">Cancel Button</option>';
              out += '<option value="button">Normal Button</option>';
            out += '</select>';
          out += '</div>';// col

          out += '<div class="col-sm-3">';
            out += '<input type="text" name="elements['+formElemetCount+'][default_value]" id="default_value_'+formElemetCount+'" class="form-control" placeholder="Default value" />';
          out += '</div>';// col

          out += '<div class="col-sm-4">';
            out += '<label >Validations : </label> &nbsp;&nbsp;&nbsp;&nbsp;';
            out += '<label >Required <input type="checkbox" name="elements['+formElemetCount+'][validations][]" value="required" id="validation_required_'+formElemetCount+'" checked="checked" /></label>&nbsp;&nbsp;&nbsp;&nbsp;';
            out += '<label >Email <input type="checkbox" name="elements['+formElemetCount+'][validations][]" value="email" id="validation_email_'+formElemetCount+'"  /></label>';
          out += '</div>';// col

        out += '</div>';// row

      out += '</div>';

      out += '<div class="row">';
        out += '<div class="col-sm-12">';
          out += '<input type="hidden" name="form_element_count" id="form_element_count" value="'+formElemetCount+'" />';
          out += '<button type="button" class="btn btn-sm btn-warning" id="btn_add_form_element"><i class="fa fa-plus"></i> Add Element</button>';
        out += '</div>';
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

$(document).on('click','#btn_add_form_element',function(){
  var formElemetCount = parseInt($('#form_element_count').val())+1;
  var out ='';
  out += '<div class="row form_item_row" id="form_item_row_'+formElemetCount+'" style="margin-bottom:20px;">';
    out += '<div class="col-sm-2">';
      out += '<input type="text" name="elements['+formElemetCount+'][element_name]" id="element_name_'+formElemetCount+'" class="form-control" placeholder="Name" />';
    out += '</div>';// col
    out += '<div class="col-sm-2">';
      out += '<select class="form-control" name="elements['+formElemetCount+'][element_type]" id="element_type_'+formElemetCount+'">';
        out += '<option value="text">Text box</option>';
        out += '<option value="textarea">Text Area</option>';
        out += '<option value="submit">Submit Button</option>';
        out += '<option value="cancel">Cancel Button</option>';
        out += '<option value="button">Normal Button</option>';
      out += '</select>';
    out += '</div>';// col

    out += '<div class="col-sm-3">';
      out += '<input type="text" name="elements['+formElemetCount+'][default_value]" id="default_value_'+formElemetCount+'" class="form-control" placeholder="Default value" />';
    out += '</div>';// col

    out += '<div class="col-sm-4">';
      out += '<label >Validations : </label> &nbsp;&nbsp;&nbsp;&nbsp;';
      out += '<label >Required <input type="checkbox" name="elements['+formElemetCount+'][validations][]" value="required" id="validation_required_'+formElemetCount+'" checked="checked" /></label>&nbsp;&nbsp;&nbsp;&nbsp;';
      out += '<label >Email <input type="checkbox"name="elements['+formElemetCount+'][validations][]" value="email"  id="validation_email_'+formElemetCount+'"  /></label>';
    out += '</div>';// col

    out += '<div class="col-sm-1">';
      out += '<button type="button" class="btn btn-sm btn-danger btn_remove_form_element" id="btn_remove_form_element-'+formElemetCount+'"><i class="fa fa-times"></i></button>';
    out += '</div>';// col

  out += '</div>';// row
  $('#form_items_cont').append(out);
  $('#form_element_count').val(formElemetCount);
});

$(document).on('click','.btn_remove_form_element',function(){
  var formElemetCount = $(this).attr('id').split('-')[1];
  $('#form_item_row_'+formElemetCount).remove();
  $('#form_element_count').val((formElemetCount-1));
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
			],
			removeButtons: 'Underline,Strike,Subscript,Superscript,Anchor,Styles,Specialchar'
		} ); */
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

function timedRefresh(timeoutPeriod) {
	setTimeout("location.reload(true);",timeoutPeriod);
}
