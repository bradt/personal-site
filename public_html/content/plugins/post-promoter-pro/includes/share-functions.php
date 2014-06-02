<?php
/**
 * Determine if we should share this post when it's being published
 * @param  int    $post_id The Post ID being published
 * @param  object $post    The Post Object
 * @return void
 */
function ppp_share_on_publish( $new_status, $old_status, $post ) {
	// don't publish password protected posts
	if ( '' !== $post->post_password ) {
		return;
	}

	if ( $new_status == 'publish' && $old_status != 'publish' ) {
		global $ppp_options;

		$allowed_post_types = isset( $ppp_options['post_types'] ) ? $ppp_options['post_types'] : array();
		$allowed_post_types = apply_filters( 'ppp_schedule_share_post_types', $allowed_post_types );

		if ( !isset( $post->post_status ) || !array_key_exists( $post->post_type, $allowed_post_types ) ) {
			return;
		}

		$from_meta = get_post_meta( $post->ID, '_ppp_share_on_publish', true );
		$from_post = isset( $_POST['_ppp_share_on_publish'] );

		if ( empty( $from_meta ) && empty( $from_post ) ) {
			return;
		}

		// Determine if we're seeing the share on publish in meta or $_POST
		if ( $from_meta && !$from_post ) {
			$ppp_share_on_publish_text = get_post_meta( $post->ID, '_ppp_share_on_publish_text', true );
		} else {
			$ppp_share_on_publish_text = isset( $_POST['_ppp_share_on_publish_text'] ) ? $_POST['_ppp_share_on_publish_text'] : '';
		}

		$share_content = ( !empty( $ppp_share_on_publish_text ) ) ? $ppp_share_on_publish_text : ppp_generate_share_content( $post->ID, null, false );
		$name = 'sharedate_0_' . $post->ID;
		$share_link = ppp_generate_link( $post->ID, $name, true );

		$status['twitter'] = ppp_send_tweet( $share_content . ' ' . $share_link );

		if ( isset( $ppp_options['enable_debug'] ) && $ppp_options['enable_debug'] == '1' ) {
			update_post_meta( $post->ID, '_ppp-' . $name . '-status', $status );
		}
	}
}

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

	$tweet_times = ( empty( $ppp_post_override ) ) ? ppp_get_default_times() : $override_times;

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
 * Returns if a day is enabled by default
 * @return  bool Day is enabled or not
 */
function ppp_is_day_enabled( $day ) {
	global $ppp_options;

	$day_status = ( ( isset( $ppp_options['days']['day' . $day] ) && $ppp_options['days']['day' . $day] === 'on' ) ) ? true : false;

	return apply_filters( 'ppp_is_day_enabled', $day_status, $day );
}

function ppp_get_default_times() {
	$number_of_days = ppp_share_days_count();
	$day = 1;
	$times = array();
	while ( $day <= $number_of_days ) {
		if ( ppp_is_day_enabled( $day ) ) {
			$times['day' . $day] = ppp_get_day_default_time( $day );
		}
		$day++;
	}

	return $times;
}

function ppp_get_day_default_time( $day ) {
	global $ppp_options;

	if ( isset( $ppp_options['times']['day' . $day] ) ) {
		return $ppp_options['times']['day' . $day];
	}

	return '8:00am';
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

	if ( !empty( $social_tokens ) && property_exists( $social_tokens, 'twitter' ) ) {
		define( 'PPP_TW_CONSUMER_KEY', $social_tokens->twitter->consumer_token );
		define( 'PPP_TW_CONSUMER_SECRET', $social_tokens->twitter->consumer_secret );
	}

	if ( !empty( $social_tokens ) && property_exists( $social_tokens, 'bitly' ) ) {
		define( 'bitly_clientid', $social_tokens->bitly->client_id );
		define( 'bitly_secret', $social_tokens->bitly->client_secret );
	}
}

/**
 * Generate the content for the shares
 * @param  int $post_id The Post ID
 * @param  string $name    The 'Name' from the cron
 * @return string          The Content to include in the social media post
 */
function ppp_generate_share_content( $post_id, $name, $is_scheduled = true ) {
	global $ppp_options;
	$default_text = isset( $ppp_options['default_text'] ) ? $ppp_options['default_text'] : '';
	$ppp_post_override = get_post_meta( $post_id, '_ppp_post_override', true );

	if ( $is_scheduled && !empty( $ppp_post_override ) ) {
		$ppp_post_override_data = get_post_meta( $post_id, '_ppp_post_override_data', true );
		$name_array = explode( '_', $name );
		$day = 'day' . $name_array[1];
		$share_content = $ppp_post_override_data[$day]['text'];
	}

	// If an override was found, use it, otherwise try the default text content
	$share_content = ( isset( $share_content ) && !empty( $share_content ) ) ? $share_content : $default_text;

	// If the content is still empty, just use the post title
	$share_content = ( isset( $share_content ) && !empty( $share_content ) ) ? $share_content : get_the_title( $post_id );

	return apply_filters( 'ppp_share_content', $share_content, array( 'post_id' => $post_id ) );
}

/**
 * Generate the link for the share
 * @param  int $post_id The Post ID
 * @param  string $name    The 'Name from the cron'
 * @return string          The URL to the post, to share
 */
function ppp_generate_link( $post_id, $name, $scheduled = true ) {
	global $ppp_share_settings;
	$share_link = get_permalink( $post_id );

	if ( ppp_link_tracking_enabled() ) {
		$share_link = ppp_generate_link_tracking( $share_link, $post_id, $name );
	}

	if ( ppp_is_shortener_enabled() && $scheduled ) {
		$shortener_name = $ppp_share_settings['shortener'];
		$share_link = apply_filters( 'ppp_apply_shortener-' . $shortener_name, $share_link );
	}


	return apply_filters( 'ppp_share_link', $share_link );
}

/**
 * Given a link, determine if link tracking needs to be applied
 * @param  string $share_link The Link to share
 * @param  int    $post_id    The Post ID the link belongs to
 * @param  string $name       The Name string from the cron
 * @return string             The URL to post, with proper analytics applied if necessary
 */
function ppp_generate_link_tracking( $share_link, $post_id, $name ) {
	if ( ppp_link_tracking_enabled() ) {
		global $ppp_share_settings;
		$link_tracking_type = $ppp_share_settings['analytics'];

		// Given the setting name, devs can extend this and apply a filter of ppp_analytics-[setting value]
		// to apply their own rules for link tracking
		$share_link = apply_filters( 'ppp_analytics-' . $link_tracking_type, $share_link, $post_id, $name );
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
function ppp_build_share_message( $post_id, $name, $scheduled = true ) {
	$share_content = ppp_generate_share_content( $post_id, $name );
	$share_link    = ppp_generate_link( $post_id, $name, $scheduled );

	return apply_filters( 'ppp_build_share_message', $share_content . ' ' . $share_link );
}

/**
 * Given a message, sends a tweet
 * @param  string $message The Text to share as the body of the tweet
 * @return object          The Results from the Twitter API
 */
function ppp_send_tweet( $message ) {
	global $ppp_twitter_oauth;
	return apply_filters( 'ppp_twitter_tweet', $ppp_twitter_oauth->ppp_tweet( html_entity_decode( $message ) ) );
}