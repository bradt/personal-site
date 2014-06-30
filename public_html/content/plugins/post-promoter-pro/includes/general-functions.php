<?php

/**
 * Returns if a link tracking method is enabled
 * @return boolean True if a form of link tracking is enabled, false if not
 */
function ppp_link_tracking_enabled() {
	global $ppp_share_settings;
	$result = false;

	if ( isset( $ppp_share_settings['analytics'] ) && !empty( $ppp_share_settings['analytics'] ) ) {
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
 * Return if twitter account is found
 * @return bool If the Twitter object exists
 */
function ppp_twitter_enabled() {
	global $ppp_social_settings;

	if ( isset( $ppp_social_settings['twitter'] ) && !empty( $ppp_social_settings['twitter'] ) ) {
		return true;
	}

	return false;
}

/**
 * Return if bitly account is found
 * @return bool If the Bitly object exists
 */
function ppp_bitly_enabled() {
	global $ppp_social_settings;

	if ( isset( $ppp_social_settings['bitly'] ) && !empty( $ppp_social_settings['bitly'] ) ) {
		return true;
	}

	return false;
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
	return stripslashes( html_entity_decode( $string ) );
}