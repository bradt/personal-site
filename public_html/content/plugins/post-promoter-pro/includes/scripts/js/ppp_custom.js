var tweetLengthYellow = 100;
var tweetLengthRed    = 117;

(function ($) {
	$('.share-time-selector').timepicker({ 'step': 15 });
	$('.share-date-selector').datepicker({
		dateFormat: 'mm/dd/yy',
		minDate: 0
	});

	$('input[id*="_share_on_publish"]').click( function() {
		$(this).parent().siblings('.ppp_share_on_publish_text').toggle();
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

	var PPP_Twitter_Configuration = {
		init: function() {
			this.add();
			this.remove();
			this.featured_image();
		},
		clone_repeatable: function(row) {

			// Retrieve the highest current key
			var key = highest = 1;
			row.parent().find( '.ppp-repeatable-row' ).each(function() {
				var current = $(this).data( 'key' );
				if( parseInt( current ) > highest ) {
					highest = current;
				}
			});
			key = highest += 1;

			clone = row.clone();

			/** manually update any select box values */
			clone.find( 'select' ).each(function() {
				$( this ).val( row.find( 'select[name="' + $( this ).attr( 'name' ) + '"]' ).val() );
			});

			clone.removeClass( 'ppp-add-blank' );

			clone.attr( 'data-key', key );
			clone.find( 'td input, td select, textarea' ).val( '' );
			clone.find( 'input, select, textarea' ).each(function() {
				var name = $( this ).attr( 'name' );

				name = name.replace( /\[(\d+)\]/, '[' + parseInt( key ) + ']');

				$( this ).attr( 'name', name ).attr( 'id', name );
				$( this ).removeClass('hasDatepicker');
				$( this ).prop('readonly', false);
			});

			clone.find( '.ppp-remove-repeatable' ).css('display', 'inline-block');
			clone.find( '.ppp-upload-file' ).show();

			return clone;
		},
		add: function() {
			$( 'body' ).on( 'click', '.submit .ppp-add-repeatable', function(e) {
				e.preventDefault();
				var button = $( this ),
				row = button.parent().parent().prev( 'tr' ),
				clone = PPP_Twitter_Configuration.clone_repeatable(row);
				clone.insertAfter( row );

				$('.share-time-selector').timepicker({ 'step': 15 });
				$('.share-date-selector').datepicker({ dateFormat : 'mm/dd/yy', minDate: 0});
			});
		},
		remove: function() {
			$( 'body' ).on( 'click', '.ppp-remove-repeatable', function(e) {
				e.preventDefault();

				var row   = $(this).parent().parent( 'tr' ),
					count = row.parent().find( 'tr' ).length - 1,
					type  = $(this).data('type'),
					repeatable = 'tr.edd_repeatable_' + type + 's';

				/** remove from price condition */
				$( '.edd_repeatable_condition_field option[value=' + row.index() + ']' ).remove();

				if( count > 1 ) {
					$( 'input, select', row ).val( '' );
					row.fadeOut( 'fast' ).remove();
				} else {
					switch( type ) {
						case 'price' :
							alert( edd_vars.one_price_min );
							break;
						case 'file' :
							$( 'input, select', row ).val( '' );
							break;
						default:
							alert( edd_vars.one_field_min );
							break;
					}
				}

				/* re-index after deleting */
				$(repeatable).each( function( rowIndex ) {
					$(this).find( 'input, select' ).each(function() {
						var name = $( this ).attr( 'name' );
						name = name.replace( /\[(\d+)\]/, '[' + rowIndex+ ']');
						$( this ).attr( 'name', name ).attr( 'id', name );
					});
				});
			});
		},
		featured_image: function() {

			// WP 3.5+ uploader
			var file_frame;
			window.formfield = '';

			$('body').on('click', '.ppp-upload-file-button', function(e) {

				e.preventDefault();

				var button = $(this);

				window.formfield = $(this).closest('.ppp-repeatable-upload-wrapper');

				// If the media frame already exists, reopen it.
				if ( file_frame ) {
					//file_frame.uploader.uploader.param( 'post_id', set_to_post_id );
					file_frame.open();
					return;
				}

				// Create the media frame.
				file_frame = wp.media.frames.file_frame = wp.media( {
					frame: 'post',
					state: 'insert',
					title: button.data( 'uploader-title' ),
					button: {
						text: button.data( 'uploader-button-text' )
					},
					multiple: $( this ).data( 'multiple' ) == '0' ? false : true  // Set to true to allow multiple files to be selected
				} );

				file_frame.on( 'menu:render:default', function( view ) {
					// Store our views in an object.
					var views = {};

					// Unset default menu items
					view.unset( 'library-separator' );
					view.unset( 'gallery' );
					view.unset( 'featured-image' );
					view.unset( 'embed' );

					// Initialize the views in our view object.
					view.set( views );
				} );

				// When an image is selected, run a callback.
				file_frame.on( 'insert', function() {

					var selection = file_frame.state().get('selection');
					selection.each( function( attachment, index ) {
						attachment = attachment.toJSON();
						if ( 0 === index ) {
							// place first attachment in field
							window.formfield.find( '.ppp-repeatable-attachment-id-field' ).val( attachment.id );
							window.formfield.find( '.ppp-repeatable-upload-field' ).val( attachment.url );
						} else {
							// Create a new row for all additional attachments
							var row = window.formfield,
								clone = EDD_Download_Configuration.clone_repeatable( row );

							clone.find( '.ppp-repeatable-attachment-id-field' ).val( attachment.id );
							clone.find( '.ppp-repeatable-upload-field' ).val( attachment.url );
							clone.insertAfter( row );
						}
					});
				});

				// Finally, open the modal
				file_frame.open();
			});


			// WP 3.5+ uploader
			var file_frame;
			window.formfield = '';

		}

	}

	PPP_Twitter_Configuration.init();

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
