var tweetLengthYellow = 100;
var tweetLengthRed    = 117;

(function ($) {
	$('.share-time-selector').timepicker({ 'step': 15 });

	$('input[id*="_share_on_publish"]').click( function() {
		$(this).parent().siblings('.ppp_share_on_publish_text').toggle();
	});

	$('input[id*="_post_override"]').click( function() {
		$(this).siblings('.post-override-matrix').toggle();
	});

	$('input[id*="_post_exclude"]').click( function() {
		$(this).siblings('.ppp-post-override-wrap').toggle();
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
				var url = $('#bitly-redirect-url').val();
				window.location.replace( url );
			} else if (response === 'INVALID_LOGIN') {
				$('.spinner').hide();
				$('#ppp-bitly-invalid-login').show();
				button.addClass('button-primary');
				button.removeClass('button-secondary');
				button.css('opacity', '1');
			}
		});
	});

	$('#fb-page').change( function() {
		var data = {};
		var select = $('#fb-page');
		select.attr('disabled', 'disabled');
		select.css('opacity', '.5');
		select.next('.spinner').show();
		data.action   = 'fb_set_page';
		data.account = select.val();
		select.width('75%');

		$.post(ajaxurl, data, function(response) {
			select.removeAttr('disabled');
			select.css('opacity', '1');
			select.next('.spinner').hide();
			select.width('100%');
		});
	});

	$('#ppp-tabs li').click( function(e) {
		e.preventDefault();
		$('#ppp-tabs li').removeClass('tabs');
		$(this).addClass('tabs');
		var clickedId = $(this).children(':first').attr('href');

		$('#ppp_schedule_metabox .wp-tab-panel').hide();
		$(clickedId).show();
		return false;
	});

	$('#ppp-social-connect-tabs a').click( function(e) {
		e.preventDefault();
		$('#ppp-social-connect-tabs a').removeClass('nav-tab-active');
		$(this).addClass('nav-tab-active');
		var clickedId = $(this).attr('href');

		$('.ppp-social-connect').hide();
		$(clickedId).show();
		return false;
	});

	$('.ppp-tw-featured-image-input').click( function() {

		if($(this).is(':checked')) {
			tweetLengthYellow = tweetLengthYellow - 22;
			tweetLengthRed    = tweetLengthRed - 22;
		} else {
			tweetLengthYellow = tweetLengthYellow + 22;
			tweetLengthRed    = tweetLengthRed + 22;
		}

	});

})(jQuery);

function PPPCountChar(val) {
	var len = val.value.length;
	var lengthField = jQuery(val).next('.ppp-text-length');

	lengthField.text(len);

	PPPColorLengthChange(len, lengthField);
}

function PPPColorLengthChange(length, object) {
	if (length < tweetLengthYellow ) {
		object.css('color', '#339933');
	} else if ( length >= tweetLengthYellow && length < tweetLengthRed ) {
		object.css('color', '#CC9933');
	} else if ( length > tweetLengthRed ) {
		object.css('color', '#FF3333');
	}
}
