<?php
function bt_widont( $str = '' ) {
	global $bt_widont_off;

	if ( isset( $bt_widont_off ) && $bt_widont_off ) {
		return $str;
	}

	return preg_replace( '|([^\s])\s+([^\s]+)\s*$|', '$1&nbsp;$2', $str );
}
add_filter( 'the_title', 'bt_widont' );

function bt_tiny_mce_before_init( $init ) {
	// Command separated string of extended elements
	$ext = 'iframe[*]';

	// Add to extended_valid_elements if it alreay exists
	if ( isset( $init['extended_valid_elements'] ) ) {
		$init['extended_valid_elements'] .= ',' . $ext;
	} else {
		$init['extended_valid_elements'] = $ext;
	}

	return $init;
}
add_filter( 'tiny_mce_before_init', 'bt_tiny_mce_before_init' );

function bt_embed_oembed_html( $html ) {
	return preg_replace( '@src="https?:@', 'class="oembed" src="', $html );
}
add_filter( 'embed_oembed_html', 'bt_embed_oembed_html' );

// I'm using Campaing Monitor's RSS to Email to send out email newsletter.
// Gmail strips out height:auto; from inline styles, so
// we can't do responsive images, so we just resize all images in the feed
function bt_the_content_feed( $content ) {
	if ( !preg_match_all( '@<img (.*?)>@', $content, $imgs ) ) {
		return $content;
	}

	$img_count = count( $imgs[0] );
	for ( $i = 0; $i < $img_count; $i++ ) {
		$img = $imgs[0][$i];

		if ( !preg_match( '@width="(.*?)"@', $img, $width ) || !preg_match( '@height="(.*?)"@', $img, $height ) ) {
			continue;
		}

		$new_width = 580;
		if ( $width[1] > $new_width ) {
			$new_height = floor( $new_width / $width[1] * $height[1] );
			$new_img = str_replace( $width[0], 'width="' . $new_width . '"', $img );
			$new_img = str_replace( $height[0], 'height="' . $new_height . '"', $new_img );
			$content = str_replace( $img, $new_img, $content );
		}
	}

	return $content;
}
add_filter( 'the_content_feed', 'bt_the_content_feed' );

function bt_get_obfuscated_email() {
	return '&#098;&#114;&#097;&#100;<span style="display:none;">+site</span>&#064;&#116;&#111;&#117;&#101;&#115;&#110;&#097;&#114;&#100;&#046;&#099;&#111;&#109;';
}

function bt_obfuscate_email( $content ) {
	return str_replace( 'brad@touesnard.com', bt_get_obfuscated_email(), $content );
}
add_filter( 'the_content', 'bt_obfuscate_email' );

function bt_remove_email_from_excerpt( $content ) {
	return str_replace( wp_strip_all_tags( bt_get_obfuscated_email() ), '', $content );
}
add_filter( 'get_the_excerpt', 'bt_remove_email_from_excerpt' );
