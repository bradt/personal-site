<?php

$options = get_option( 'ppp_options' );

if ( isset( $options['delete_on_uninstall'] ) ) {
	require_once 'includes/cron-functions.php';

	$crons = ppp_get_shceduled_crons();
	foreach( $crons as $cron ) {
		$ppp_data   = $cron['ppp_share_post_event'];
		$array_keys = array_keys( $ppp_data );
		$hash_key   = $array_keys[0];
		$event_info = $ppp_data[$hash_key];
		ppp_remove_scheduled_shares( $event_info['args'][0] );
	}

	delete_option( 'ppp_options' );
	delete_option( '_ppp_license_key' );
	delete_option( 'ppp_social_settings' );
	delete_option( 'ppp_share_settings' );
	delete_option( '_ppp_license_key_status' );
	delete_transient( 'ppp_social_tokens' );
}