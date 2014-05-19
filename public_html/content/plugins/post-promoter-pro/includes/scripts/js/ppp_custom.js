(function ($) {
	$('.share-time-selector').timepicker({ 'step': 15 });

	$('#_ppp_post_override').click( function() {
		$('.post-override-matrix').toggle();
	});

	$('#_ppp_post_exclude').click( function() {
		$('#ppp-post-override-wrap').toggle();
	});

	$('.ppp-share-enable-day').click( function() {
		var checkbox = $(this);
		if (checkbox.is(':checked')) {
			checkbox.siblings('input').prop('readonly', false).prop('disabled', false);
		} else {
			checkbox.siblings('input').prop('readonly', true).prop('disabled', true);
		}
	});

	$('.ppp-analytics-checkbox').click( function() {
		var clicked = $(this);
		var status  = $(this).is(':checked');
		$('#ppp-analytics-options input').each(function() {
			if ($(this).attr('id') != clicked.attr('id')) {
				$(this).attr('checked', false);
				$(this).attr('disabled', status);
			}
		});
	});

})(jQuery);

function PPPCountChar(val) {
	var len = val.value.length;
	var lengthField = jQuery(val).next('.ppp-text-length');
	lengthField.text(len);
	if (len < 100 ) {
		lengthField.css('color', '#339933');
	} else if ( len >= 100 && len < 117 ) {
		lengthField.css('color', '#CC9933');
	} else if ( len > 117 ) {
		lengthField.css('color', '#FF3333');
	}
};