(function ($) {
	$('.share-time-selector').timepicker({ 'step': 15 });

	$('#ppp_share_on_publish').click( function() {
		$('#ppp_share_on_publish_text').toggle();
	});

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

	$('#bitly-login').click( function() {
		var data = {};
		var button = $('#bitly-login');
		button.removeClass('button-primary');
		button.addClass('button-secondary');
		button.css('opacity', '.5');
		$('.spinner').show();
		$('#ppp-bitly-invalid-login').hide();
		data.action   = 'ppp_bitly_connect';
		data.username = $('#bitly-username').val();
		data.password = $('#bitly-password').val();

		$.post(ajaxurl, data, function(response) {
			if (response == '1') {
				window.location.replace( '/wp-admin/admin.php?page=ppp-social-settings' );
			} else if (response === 'INVALID_LOGIN') {
				$('.spinner').hide();
				$('#ppp-bitly-invalid-login').show();
				button.addClass('button-primary');
				button.removeClass('button-secondary');
				button.css('opacity', '1');
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
}