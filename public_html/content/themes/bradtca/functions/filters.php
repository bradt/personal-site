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

function bt_bloginfo( $output, $show ) {
	if ( 'template_url' == $show && !is_admin() && !defined( 'WP_LOCAL_DEV' ) ) {
		return 'http://assets.bradt.ca';
	}

	return $output;
}
add_filter( 'bloginfo_url', 'bt_bloginfo', null, 2 );

function bt_embed_oembed_html( $html ) {
	return preg_replace( '@src="https?:@', 'class="oembed" src="', $html );
}
add_filter( 'embed_oembed_html', 'bt_embed_oembed_html' );
