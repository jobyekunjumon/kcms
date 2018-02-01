var modalsActive = 0;
$(document).on('shown.bs.modal', function () {
  ++modalsActive;
});
$(document).on('hidden.bs.modal', function () {
  if (--modalsActive > 0) {
    $('body').addClass('modal-open');
  }
});

$('.top_layer_component').hover(function(){
  $(this).addClass('edit_spacer');
});
$('.top_layer_component').mouseleave(function(){
  $(this).removeClass('edit_spacer');
});
