<?php

/**
 * Checks to see if a session is set and it's appropriate to start one, and starts it if necessary
 * @return void
 */
function ppp_maybe_start_session() {
	if ( ( is_admin() || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) && !isset( $_SESSION ) ) {
		session_start();
	}
}

/**
 * Returns if a link tracking method is enabled
 * @return boolean True if a form of link tracking is enabled, false if not
 */
function ppp_link_tracking_enabled() {
	global $ppp_share_settings;
	$result = false;

	if ( isset( $ppp_share_settings['analytics'] ) && $ppp_share_settings['analytics'] !== 'none' ) {
		$result =  true;
	}

	return apply_filters( 'ppp_is_link_tracking_enabled', $result );
}

/**
 * Get a post slug via the ID
 * @param  int $post_id The post ID
 * @return string       The slug of the post
 */
function ppp_get_post_slug_by_id( $post_id ) {
	$post_data = get_post( $post_id, ARRAY_A );
	$slug = $post_data['post_name'];

	return $slug;
}

/**
 * Get's the array of text replacements
 * @return array The array of text replacements, each with a token and description items
 */
function ppp_get_text_tokens() {
	return apply_filters( 'ppp_text_tokens', array() );
}

/**
 * Returns the number of says to setup shares for
 * @return  int The number of days
 */
function ppp_share_days_count() {
	return apply_filters( 'ppp_share_days_count', 6 );
}

/**
 * Returns if the shortener option is chosen
 * @return boolean	True/False if the shortener has been selected
 */
function ppp_is_shortener_enabled() {
	global $ppp_share_settings;

	return ( isset( $ppp_share_settings['shortener'] ) && !empty( $ppp_share_settings['shortener'] ) && $ppp_share_settings != '-1' );
}

/**
 * Strips slashes and html_entities_decode for sending to the networks.
 */
function ppp_entities_and_slashes( $string ) {
	return stripslashes( html_entity_decode( $string, ENT_COMPAT, 'UTF-8' ) );
}

/**
 * Runs hook for the social networks to add their thumbnail sizes
 * @return void
 */
function ppp_add_image_sizes() {
	do_action( 'ppp_add_image_sizes' );
}
