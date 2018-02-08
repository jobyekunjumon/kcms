$(document).on('click','.btn_edit_content_map',function() {
  var mapId = $(this).attr('id').split('-')[1];
  var defLatitude = $('#lat_map-'+mapId).val();
  var defLongitude = $('#lng_map-'+mapId).val();
  $('#id_map').val(mapId);
  $('#us2-lat').val(defLatitude);
  $('#us2-lon').val(defLongitude);
  $("#us2").locationpicker({
      location: {
          latitude: defLatitude,
          longitude: defLongitude
      },
      radius: 300,
      enableAutocomplete: true,
      inputBinding: {
          locationNameInput: $("#us2-address"),
          latitudeInput: $("#us2-lat"),
          longitudeInput: $("#us2-lon")
      }
  });
  $("#us2").locationpicker("autosize");
});

$(document).on('click','#btn_save_map',function(){
  $('#update_map_status_cont').html('&nbsp;&nbsp;<i class="fa fa-spinner fa-spin fa-fw"></i> Updating map');
  var mapId = $('#id_map').val();
  var latitude = $('#us2-lat').val();
  var longitude = $('#us2-lon').val();
  var targetUrl = baseUrl+'/user/process-live-edit/update-map';
  $.post(targetUrl,{id_map:mapId,lat:latitude,lng:longitude},function(response){
    var outData = $.parseJSON(response);
    if(outData.status == "1" || outData.status == 1) {
      $('#update_map_status_cont').html('<span style="color:green;" >&nbsp;&nbsp;<i class="fa fa-check"></i> Map updated successfully.</span>');
    } else $('#update_map_status_cont').html('<span style="color:red;">&nbsp;&nbsp;<i class="fa fa-times"></i> Failed to update map.</span>');
  });
});

$('#mapModal').on('hidden.bs.modal', function () {
    var redirect = baseUrl+'/user/edit-site/show-site?name='+$('#site').val()+'&page='+$('#page').val();
    window.location.href = redirect;
})
