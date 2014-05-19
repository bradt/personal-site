<?php

function ppp_link_tracking_enabled( $option = false ) {
	global $ppp_share_settings;

	if ( !empty( $option ) ) {
		return isset( $ppp_share_settings[$option] );
	}

	if ( isset( $ppp_share_settings['ppp_unique_links'] ) &&
		 $ppp_share_settings['ppp_unique_links'] == '1' ) {
		return true;
	}

	if ( isset( $ppp_share_settings['ppp_ga_tags'] ) &&
		 $ppp_share_settings['ppp_ga_tags'] == '1' ) {
		return true;
	}

	return apply_filters( 'ppp_is_link_tracking_enabled', false );
}

function ppp_get_post_slug_by_id( $post_id ) {
	$post_data = get_post( $post_id, ARRAY_A );
	$slug = $post_data['post_name'];

	return $slug;
}