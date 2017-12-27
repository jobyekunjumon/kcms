$(document).on('click','.live-text-edit',function(){	
	var entityId = $(this).attr('id');
	var elementId = entityId.split('-')[1];
	var content = '<input type="text" class="form-control date_mask" data-inputmask="\'alias\': \'yyyy/mm/dd\'" data-mask value="'+$('#cont-'+elementId).html()+'" id="input-'+elementId+'" name="input-'+elementId+'" />';
	var action = '<i id="save-'+elementId+'" class="fa fa-save pull-right live-text-edit-post-button" style="cursor:pointer;" ></i>';
	$('#cont-'+elementId).html(content); 
	$('#action-'+elementId).html(action); 
});
$(document).on('click','.live-text-edit-post-button',function(){	
	var entityId = $(this).attr('id');
	var elementId = entityId.split('-')[1];	 
	$('#action-'+elementId).html('<img src="../images/ajax_loader_small.gif" >');
	var targetUrl = baseUrl+'/index/asynceditfield';
	if($('#input-'+elementId).val() == $('#val-'+elementId).val()) {
		var content = $('#val-'+elementId).val();
		var action = '<i id="edit-'+elementId+'" class="fa fa-edit pull-right live-text-edit" style="cursor:pointer;" ></i>';
		$('#cont-'+elementId).html(content); 
		$('#action-'+elementId).html(action);
	} else { 
		$.post(targetUrl,{field_name:elementId,value:$('#input-'+elementId).val(),id_student:$('#id_student').val()},function(response){
			var data = jQuery.parseJSON(response);
			if(data.status == "ERROR") {
				alert(data.error);
				var content = $('#val-'+elementId).val();
				var action = '<i id="edit-'+elementId+'" class="fa fa-edit pull-right live-text-edit" style="cursor:pointer;" ></i>';
				$('#cont-'+elementId).html(content); 
				$('#action-'+elementId).html(action);
			} else if(data.status == "SUCCESS") {
				var content = $('#input-'+elementId).val();
				var action = '<i id="edit-'+elementId+'" class="fa fa-edit pull-right live-text-edit" style="cursor:pointer;" ></i>';
				$('#cont-'+elementId).html(content); 
				$('#val-'+elementId).val(content); 
				$('#action-'+elementId).html(action);
			}
		});
	}
});

$(document).on('click','.live-list-edit',function(){	
	var entityId = $(this).attr('id');
	var elementId = entityId.split('-')[1]; 
	var content = '';
	content = '<select class="form-control" id="input-'+elementId+'" name="input-'+elementId+'" >';
	content += getListItems(elementId,$('#val-'+elementId).val());
	content += '</select>'; 
	var action = '<i id="save-'+elementId+'" class="fa fa-save pull-right live-list-edit-post-button" style="cursor:pointer;" ></i>';
	$('#cont-'+elementId).html(content); 
	$('#action-'+elementId).html(action); 
});

$(document).on('click','.live-list-edit-post-button',function(){	
	var entityId = $(this).attr('id');
	var elementId = entityId.split('-')[1];	 	 
	$('#action-'+elementId).html('<img src="../images/ajax_loader_small.gif" >');
	var targetUrl = baseUrl+'/index/asynceditfield';
	if($('#input-'+elementId).val() == $('#val-'+elementId).val()) {
		var content = $('#val-'+elementId).val();
		var action = '<i id="edit-'+elementId+'" class="fa fa-edit pull-right live-list-edit" style="cursor:pointer;" ></i>';
		$('#cont-'+elementId).html(content); 
		$('#action-'+elementId).html(action);
	} else { 
		$.post(targetUrl,{field_name:elementId,value:$('#input-'+elementId).val(),id_student:$('#id_student').val()},function(response){
			var data = jQuery.parseJSON(response);
			if(data.status == "ERROR") {
				alert(data.error);
				var content = $('#val-'+elementId).val();
				var action = '<i id="edit-'+elementId+'" class="fa fa-edit pull-right live-list-edit" style="cursor:pointer;" ></i>';
				$('#cont-'+elementId).html(content); 
				$('#action-'+elementId).html(action);
			} else if(data.status == "SUCCESS") {
				var content = $('#input-'+elementId).val();
				var action = '<i id="edit-'+elementId+'" class="fa fa-edit pull-right live-list-edit" style="cursor:pointer;" ></i>';
				$('#cont-'+elementId).html(content); 
				$('#val-'+elementId).val(content); 
				$('#action-'+elementId).html(action);
			}
		});
	}
});

function getListItems(field,selectedValue) {
	var out = '';
	if(field == "gender") {
		out = '<option value="male"';
		if(selectedValue == "male") out += ' selected="selected" ';
		out += '>Male</option>';
		out += '<option value="female"';
		if(selectedValue == "female") out += ' selected="selected" ';
		out += '>Female</option>';
		return out;
	} else if(field == "sponsor") {
		out = '<option value="Self"';
			if(selectedValue == "Self") out += ' selected="selected" ';
		out += '>Self</option>';
		out += '<option value="Parent"';
			if(selectedValue == "Parent") out += ' selected="selected" ';
		out += '>Parent</option>';
		out += '<option value="Government"';
			if(selectedValue == "Government") out += ' selected="selected" ';
		out += '>Government</option>';
		out += '<option value="Company"';
			if(selectedValue == "Company") out += ' selected="selected" ';
		out += '>Company</option>';
		return out;
	} else if(field == "housing") {
		out = '<option value="On-campus"';
			if(selectedValue == "On-campus") out += ' selected="selected" ';
		out += '>On-campus</option>';
		out += '<option value="Apartment"';
			if(selectedValue == "Apartment") out += ' selected="selected" ';
		out += '>Apartment</option>';
		out += '<option value="Studio"';
			if(selectedValue == "Studio") out += ' selected="selected" ';
		out += '>Studio</option>';
		out += '<option value="Roommate"';
			if(selectedValue == "Roommate") out += ' selected="selected" ';
		out += '>Roommate</option>';
		out += '<option value="Homestay"';
			if(selectedValue == "Homestay") out += ' selected="selected" ';
		out += '>Homestay</option>';
		out += '<option value="House"';
			if(selectedValue == "House") out += ' selected="selected" ';
		out += '>House</option>';
		return out;
	} else if(field == "housing_details") {
		out = '<option value="1 Bedroom"';
			if(selectedValue == "1 Bedroom") out += ' selected="selected" ';
		out += '>1 Bedroom</option>';
		out += '<option value="2 Bedrooms"';
			if(selectedValue == "2 Bedrooms") out += ' selected="selected" ';
		out += '>2 Bedrooms</option>';
		out += '<option value="3 Bedrooms"';
			if(selectedValue == "3 Bedrooms") out += ' selected="selected" ';
		out += '>3 Bedrooms</option>';		
		return out;
	} else if(field == "nationality" || field == "residing_country") {
		var targetUrl = baseUrl+'/index/asyncgetselectentries';
		$.post(targetUrl,{field_name:field,selected_value:selectedValue},function(response){
			$('#input-'+field).html(response);
			return response;
		});
	} 
}

$(document).ready(function(){	
	if($('#show_live_edit_group_form').val()) {		
		$('#edit-'+$('#show_live_edit_group_form').val()).click();
	}
});
