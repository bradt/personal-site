<?php
function bt_widont( $str = '' ) {
	global $bt_widont_off;

	if ( isset( $bt_widont_off ) && $bt_widont_off ) {
		return $str;
	}

	return preg_replace( '|([^\s])\s+([^\s]+)\s*$|', '$1&nbsp;$2', $str );
}

add_filter( 'the_title', 'bt_widont' );
