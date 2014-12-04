<?php

/**
 * Schedule social media posts with wp_schedule_single_event
 * @param  id $post_id
 * @param  object $post
 * @return void
 */
function ppp_schedule_share( $post_id, $post ) {
	global $ppp_options;
	$allowed_post_types = isset( $ppp_options['post_types'] ) ? $ppp_options['post_types'] : array();
	$allowed_post_types = apply_filters( 'ppp_schedule_share_post_types', $allowed_post_types );

	if ( !isset( $_POST['post_status'] ) || !array_key_exists( $post->post_type, $allowed_post_types ) ) {
		return;
	}

	$ppp_post_exclude = get_post_meta( $post_id, '_ppp_post_exclude', true );
	if ( $ppp_post_exclude ) { // If the post meta says to exclude from social media posts, delete all scheduled and return
		ppp_remove_scheduled_shares( $post_id );
		return;
	}

	if ( ( $_POST['post_status'] == 'publish' && $_POST['original_post_status'] == 'publish' ) ||
	     ( $_POST['post_status'] == 'future' && $_POST['original_post_status'] == 'future' ) ) {
		// Be sure to clear any currently scheduled tweets so we aren't creating multiple instances
		// This will stop something from moving between draft and post and continuing to schedule tweets
		ppp_remove_scheduled_shares( $post_id );
	}

	if( ( $_POST['post_status'] == 'publish' && $_POST['original_post_status'] != 'publish' ) || // From anything to published
		( $_POST['post_status'] == 'future' && $_POST['original_post_status'] == 'future' ) || // Updating a future post
		( $_POST['post_status'] == 'publish' && $_POST['original_post_status'] == 'publish' ) ) { // Updating an already published post
		global $ppp_options, $ppp_social_settings;

		$timestamps = ppp_get_timestamps( $_POST['mm'], $_POST['jj'], $_POST['aa'], $post_id );

		foreach ( $timestamps as $timestamp => $name ) {
			wp_schedule_single_event( $timestamp, 'ppp_share_post_event', array( $post_id, $name ) );
		}
	}
}
// This action is for the cron event. It triggers ppp_share_post when the crons run
add_action( 'ppp_share_post_event', 'ppp_share_post', 10, 2 );

/**
 * Given a post ID remove it's scheduled shares
 * @param  int $post_id The Post ID to remove shares for
 * @return void
 */
function ppp_remove_scheduled_shares( $post_id ) {
	do_action( 'ppp_pre_remove_scheduled_shares', $post_id );
	$days_ahead = 1;
	while ( $days_ahead <= ppp_share_days_count() ) {
		$name = 'sharedate_' . $days_ahead . '_' . $post_id;
		wp_clear_scheduled_hook( 'ppp_share_post_event', array( $post_id, $name ) );

		$days_ahead++;
	}
	do_action( 'ppp_post_remove_scheduled_shares', $post_id );
}

/**
 * Given an array of arguments, remove a share
 * @param  array $args Array containing 2 values $post_id and $name
 * @return void
 */
function ppp_remove_scheduled_share( $args ) {
	wp_clear_scheduled_hook( 'ppp_share_post_event', $args );
	return;
}

/**
 * Get all the crons hooked into 'ppp_share_post_event'
 * @return array All crons scheduled for Post Promoter Pro
 */
function ppp_get_shceduled_crons() {
	$all_crons = get_option( 'cron' );
	$ppp_crons = array();

	foreach ( $all_crons as $timestamp => $cron ) {
		if ( ! isset( $cron['ppp_share_post_event'] ) ) {
			continue;
		}

		foreach ( $cron['ppp_share_post_event'] as $key => $single_event ) {
			$single_event['timestamp'] = $timestamp;
			$ppp_crons[$key] = $single_event;
		}

	}

	return $ppp_crons;
}
