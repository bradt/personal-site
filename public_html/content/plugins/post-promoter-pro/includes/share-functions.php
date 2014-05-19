<?php
/**
 * Create timestamps and unique identifiers for each cron.
 * @param  int $month
 * @param  int $day
 * @param  int $year
 * @param  int $post_id
 * @return array
 */
function ppp_get_timestamps( $month, $day, $year, $post_id ) {
	global $ppp_options, $ppp_social_settings;
	$days_ahead = 1;
	$times = array();
	$offset = (int) -( get_option( 'gmt_offset' ) ); // Make the timestamp in the users' timezone, b/c that makes more sense

	$ppp_post_override = get_post_meta( $post_id, '_ppp_post_override', true );
	$ppp_post_override_data = get_post_meta( $post_id, '_ppp_post_override_data', true );
	$override_times = wp_list_pluck( $ppp_post_override_data, 'time' );

	$tweet_times = ( empty( $ppp_post_override ) ) ? $ppp_options['times'] : $override_times;

	$times = array();
	foreach ( $tweet_times as $key => $data ) {
		$days_ahead = substr( $key, -1 );
		$share_time = explode( ':', $data );
		$hours = (int) $share_time[0];
		$minutes = (int) substr( $share_time[1], 0, 2 );
		$ampm = strtolower( substr( $share_time[1], -2 ) );

		if ( $ampm == 'pm' && $hours != 12 ) {
			$hours = $hours + 12;
		}

		if ( $ampm == 'am' && $hours == 12 ) {
			$hours = 00;
		}

		$hours   = $hours + $offset;

		$timestamp = mktime( $hours, $minutes, 0, $month, $day + $days_ahead, $year );

		if ( $timestamp > time() ) { // Make sure the timestamp we're getting is in the future
			$times[strtotime( date_i18n( 'd-m-Y H:i:s', $timestamp , true ) )] = 'sharedate_' . $days_ahead . '_' . $post_id;
		}
	}

	return apply_filters( 'ppp_get_timestamps', $times );
}

/**
 * Hook for the crons to fire and send tweets
 * @param  id $post_id
 * @param  string $name
 * @return void
 */
function ppp_share_post( $post_id, $name ) {
	global $ppp_options, $ppp_social_settings, $ppp_share_settings, $ppp_twitter_oauth;

	// If we've already started to share this, don't share it again.
	// Compensates for wp-cron's race conditions
	if ( get_transient( 'ppp_sharing' . $name ) === 'true' ) {
		return;
	}

	// For 60 seconds, don't allow another share to go for this post
	set_transient( 'ppp_sharing' . $name, 'true', 60 );
	$post = get_post( $post_id, OBJECT );

	$share_message = ppp_build_share_message( $post_id, $name );

	$status['twitter'] = ppp_send_tweet( $share_message );

	if ( isset( $ppp_options['enable_debug'] ) && $ppp_options['enable_debug'] == '1' ) {
		update_post_meta( $post_id, '_ppp-' . $name . '-status', $status );
	}
}

/**
 * Get the Social Share Tokens from the API
 * @return void
 */
function ppp_set_social_tokens() {
	if ( defined( 'PPP_TW_CONSUMER_KEY' ) && defined( 'PPP_TW_CONSUMER_SECRET' ) ) {
		return;
	}

	$social_tokens = get_transient( 'ppp_social_tokens' );

	if ( !$social_tokens ) {
		$license = trim( get_option( '_ppp_license_key' ) );
		$url = PPP_STORE_URL . '/?ppp-get-tokens&ppp-license-key=' . $license . '&ver=' . md5( time() . $license );
		$response = wp_remote_get( $url, array( 'timeout' => 15, 'sslverify' => false ) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$social_tokens = json_decode( wp_remote_retrieve_body( $response ) );
		if ( !isset( $social_tokens->error ) && isset( $social_tokens->twitter ) ) {
			set_transient( 'ppp_social_tokens', $social_tokens, WEEK_IN_SECONDS );
		}
	}

	if ( !empty( $social_tokens) && property_exists( $social_tokens, 'twitter' ) ) {
		define( 'PPP_TW_CONSUMER_KEY', $social_tokens->twitter->consumer_token );
		define( 'PPP_TW_CONSUMER_SECRET', $social_tokens->twitter->consumer_secret );
	}
}

/**
 * Generate the content for the shares
 * @param  int $post_id The Post ID
 * @param  string $name    The 'Name' from the cron
 * @return string          The Content to include in the social media post
 */
function ppp_generate_share_content( $post_id, $name ) {
	$ppp_post_override = get_post_meta( $post_id, '_ppp_post_override', true );

	if ( !empty( $ppp_post_override ) ) {
		$ppp_post_override_data = get_post_meta( $post_id, '_ppp_post_override_data', true );
		$name_array = explode( '_', $name );
		$day = 'day' . $name_array[1];
		$share_content = $ppp_post_override_data[$day]['text'];
	}

	$share_content = isset( $share_content ) ? $share_content : get_the_title( $post_id );

	return apply_filters( 'ppp_share_content', $share_content );
}

/**
 * Generate the link for the share
 * @param  int $post_id The Post ID
 * @param  string $name    The 'Name from the cron'
 * @return string          The URL to the post, to share
 */
function ppp_generate_link( $post_id, $name ) {
	global $ppp_share_settings;
	$share_link = get_permalink( $post_id );

	if ( ppp_link_tracking_enabled() ) {
		$share_link = ppp_generate_link_tracking( $share_link, $post_id, $name );
	}


	return apply_filters( 'ppp_share_link', $share_link );
}

function ppp_generate_link_tracking( $share_link, $post_id, $name ) {
	$name_parts = explode( '_', $name );
	if ( ppp_link_tracking_enabled( 'ppp_unique_links') ) {
		$share_link .= strpos( $share_link, '?' ) ? '&' : '?' ;

		$query_string_var = apply_filters( 'ppp_query_string_var', 'ppp' );

		$share_link .= $query_string_var . '=' . $post_id . '-' . $name_parts[1];
	} elseif ( ppp_link_tracking_enabled( 'ppp_ga_tags' ) ) {
		$utm['source']   = 'Twitter';
		$utm['medium']   = 'social';
		$utm['term']     =  ppp_get_post_slug_by_id( $post_id );
		$utm['content']  = $name_parts[1]; // The day after publishing
		$utm['campaign'] = 'PostPromoterPro';

		$utm_string  = strpos( $share_link, '?' ) ? '&' : '?' ;
		$first = true;
		foreach ( $utm as $key => $value ) {
			if ( !$first ) {
				$utm_string .= '&';
			}
			$utm_string .= 'utm_' . $key . '=' . $value;
			$first = false;
		}

		$share_link .= $utm_string;
	}

	$share_link = apply_filters( 'ppp_generate_link_tracking', $share_link, $post_id, $name );

	return $share_link;
}

/**
 * Combines the results from ppp_generate_share_content and ppp_generate_link into a single string
 * @param  int $post_id The Post ID
 * @param  string $name    The 'name' element from the Cron
 * @return string          The Full text for the social share
 */
function ppp_build_share_message( $post_id, $name ) {
	$share_content = ppp_generate_share_content( $post_id, $name );
	$share_link    = ppp_generate_link( $post_id, $name );

	return apply_filters( 'ppp_build_share_message', $share_content . ' ' . $share_link );
}

/**
 * Given a message, sends a tweet
 * @param  string $message The Text to share as the body of the tweet
 * @return object          The Results from the Twitter API
 */
function ppp_send_tweet( $message ) {
	global $ppp_twitter_oauth;
	return apply_filters( 'ppp_twitter_tweet', $ppp_twitter_oauth->ppp_tweet( html_entity_decode( htmlentities( $message ) ) ) );
}