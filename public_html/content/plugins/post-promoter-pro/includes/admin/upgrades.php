<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Run any upgrade routines needed depending on versions
 * @return void
 */
function ppp_upgrade_plugin() {
	$ppp_version = get_option( 'ppp_version' );

	$upgrades_executed = false;

	// We don't have a version yet, so we need to run the upgrader
	if ( !$ppp_version && PPP_VERSION == '1.3' ) {
		ppp_v13_upgrades();
		$upgrades_executed = true;
	}

	if ( version_compare( $ppp_version, 2.1, '<' ) ) {
		ppp_v21_upgrades();
		$upgrades_executed = true;
	}

	if ( $upgrades_executed || version_compare( $ppp_version, PPP_VERSION, '<' ) ) {
		set_transient( '_ppp_activation_redirect', '1', 60 );
		update_option( 'ppp_version', PPP_VERSION );
	}

}

/**
 * Run the 1.3 upgrades
 * @return void
 */
function ppp_v13_upgrades() {
	global $ppp_share_settings;
	$uq_status = ( isset( $ppp_share_settings['ppp_unique_links'] ) && $ppp_share_settings['ppp_unique_links'] == '1' ) ? $ppp_share_settings['ppp_unique_links'] : 0;
	$ga_status = ( isset( $ppp_share_settings['ppp_ga_tags'] ) && $ppp_share_settings['ppp_ga_tags'] == '1' ) ? $ppp_share_settings['ppp_ga_tags'] : 0;

	if ( $uq_status ) {
		$ppp_share_settings['analytics'] = 'unique_links';
		unset( $ppp_share_settings['ppp_unique_links'] );
	} elseif ( $ga_status ) {
		$ppp_share_settings['analytics'] = 'google_analytics';
		unset( $ppp_share_settings['ppp_ga_tags'] );
	}

	update_option( 'ppp_share_settings', $ppp_share_settings );

	global $ppp_options;
	$ppp_options['default_text'] = '{post_title}';
	$ppp_options['days'] = array( 'day1' => 'on', 'day2' => 'on', 'day3' => 'on', 'day4' => 'on', 'day5' => 'on', 'day6' => 'on');

	update_option( 'ppp_options', $ppp_options );
}

/**
 * Run the 2.1 updates
 * @return void
 */
function ppp_v21_upgrades() {
	// Auto load was set to true, let's make that false, we don't always need the version
	delete_option( 'ppp_version' );
	add_option( 'ppp_version', $ppp_version, '', 'no' );
}
