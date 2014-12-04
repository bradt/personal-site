<?php

/**
 * Adds the WP.me Shortner to the list of available shorteners
 * @param string $selected_shortener The currently selected url shortener
 * @return void
 */
function ppp_add_wpme_shortener( $selected_shortener ) {
	if( class_exists( 'Jetpack' ) && Jetpack::is_module_active( 'shortlinks' ) ) { ?>
		<option value="wpme" <?php selected( $selected_shortener, 'wpme', true ); ?>>WP.me</option>
	<?php }
}
add_action( 'ppp_url_shorteners', 'ppp_add_wpme_shortener', 10, 1 );

/**
 * Displays the wpme settings area when wpme is selected as the URL shortener
 * @return void
 */
function ppp_display_wpme_settings() {
	?>
	<p>
		<strong>Note:</strong>
		<em>
			<p><?php _e( 'The WP.me URL shortener does not support the Analytics settings listed above. No analytics tracking will be available.', 'ppp-txt' ); ?></p>
		</em>
	</p>
	<?php
}
add_action( 'ppp_shortener_settings-wpme', 'ppp_display_wpme_settings', 10 );


/**
 * Convert a link to WP.me
 * @param string $link The link, before shortening
 * @return string      The link, after shortening through wp.me
 */
function ppp_apply_wpme( $link ) {
	if ( class_exists( 'Jetpack' ) && Jetpack::is_module_active( 'shortlinks' ) ) {
		$id = url_to_postid( $link );
		$result = wpme_get_shortlink( $id );

		if ( ! empty( $result ) ) {
			return $result;
		} else {
			return $link;
		}
	}

	return $link;
}
add_filter( 'ppp_apply_shortener-wpme', 'ppp_apply_wpme', 10, 1 );