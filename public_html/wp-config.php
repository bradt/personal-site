<?php
if ( file_exists( dirname( __FILE__ ) . '/local-config.php' ) ) {
	define( 'WP_LOCAL_DEV', true );
	include dirname( __FILE__ ) . '/local-config.php';
}
else {
	define( 'WP_LOCAL_DEV', false );
	include dirname( __FILE__ ) . '/live-config.php';
}

define( 'WPLANG', '' );

// We run a proper cron on the server instead
// */5 * * * * curl --silent http://bradt.ca:8080/wp-cron.php?doing_wp_cron >/dev/null 2>&1
define( 'DISABLE_WP_CRON', true );

define( 'WP_CONTENT_DIR', dirname( __FILE__ ) . '/content' );
define( 'WP_CONTENT_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/content' );

if ( !defined( 'ABSPATH' ) )
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );

require_once ABSPATH . 'wp-settings.php';
