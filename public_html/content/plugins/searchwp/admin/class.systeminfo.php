<?php

// based on Easy Digital Downloads System Info by Chris Christoff

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class SearchWP_System_Info {

	private $searchwp;

	function __construct() {
		$this->searchwp = SWP();
	}

	function output() {
		global $wpdb;

		$theme_data = wp_get_theme();
		/** @noinspection PhpUndefinedFieldInspection */
		$theme = $theme_data->Name . ' ' . $theme_data->Version;

		// Try to identifty the hosting provider
		$host = false;
		if ( defined( 'WPE_APIKEY' ) ) {
			$host = 'WP Engine';
		} elseif ( defined( 'PAGELYBIN' ) ) {
			$host = 'Pagely';
		}

		$utf8mb4_failed_upgrade = false;
		if ( searchwp_get_option( 'utf8mb4_upgrade_failed' ) ) {
			$utf8mb4_failed_upgrade = true;
		}

		?>
	<form action="" method="post" dir="ltr">
		<textarea readonly="readonly" onclick="this.focus();this.select()" class="searchwp-system-info-textarea" name="searchwp-sysinfo" title="<?php _e( 'To copy the system info, click below then press CTRL + C (PC) or CMD + C (Mac).', 'searchwp' ); ?>">
### Begin System Info ###

## Please include this information when posting support requests ##

<?php if ( $utf8mb4_failed_upgrade ) : ?>
Failed utf8mb4 upgrade:   Yes
<?php endif; ?>

Multisite:                <?php echo is_multisite() ? 'Yes' . "\n" : 'No' . "\n" ?>

SITE_URL:                 <?php echo esc_url( site_url() ) . "\n"; ?>
HOME_URL:                 <?php echo esc_url( home_url() ) . "\n"; ?>

SearchWP Version:         <?php echo esc_textarea( $this->searchwp->version ) . "\n"; ?>
WordPress Version:        <?php echo esc_textarea( get_bloginfo( 'version' ) ) . "\n"; ?>
Permalink Structure:      <?php echo esc_textarea( get_option( 'permalink_structure' ) ) . "\n"; ?>
Active Theme:             <?php echo esc_textarea( $theme ) . "\n"; ?>
<?php if ( $host ) : ?>
Host:                     <?php echo esc_textarea( $host ) . "\n"; ?>
<?php endif; ?>

Registered Post Stati:    <?php echo esc_textarea( implode( ', ', get_post_stati() ) ) . "\n\n"; ?>

PHP Version:              <?php echo esc_textarea( PHP_VERSION ) . "\n"; ?>
MySQL Version:            <?php echo esc_textarea( $wpdb->db_version() ) . "\n"; ?>
Web Server Info:          <?php echo esc_textarea( $_SERVER['SERVER_SOFTWARE'] ) . "\n"; ?>

WordPress Memory Limit:   <?php echo esc_textarea( WP_MEMORY_LIMIT ); ?><?php echo "\n"; ?>
PHP Safe Mode:            <?php echo ini_get( 'safe_mode' ) ? 'Yes' : 'No'; ?><?php echo "\n"; ?>
PHP Memory Limit:         <?php echo esc_textarea( ini_get( 'memory_limit' ) ) . "\n"; ?>
PHP Upload Max Size:      <?php echo esc_textarea( ini_get( 'upload_max_filesize' ) ) . "\n"; ?>
PHP Post Max Size:        <?php echo esc_textarea( ini_get( 'post_max_size' ) ) . "\n"; ?>
PHP Upload Max Filesize:  <?php echo esc_textarea( ini_get( 'upload_max_filesize' ) ) . "\n"; ?>
PHP Time Limit:           <?php echo esc_textarea( ini_get( 'max_execution_time' ) ) . "\n"; ?>
PHP Max Input Vars:       <?php echo esc_textarea( ini_get( 'max_input_vars' ) ) . "\n"; ?>
PHP Arg Separator:        <?php echo esc_textarea( ini_get( 'arg_separator.output' ) ) . "\n"; ?>
PHP Allow URL File Open:  <?php echo ini_get( 'allow_url_fopen' ) ? 'Yes' : 'No'; ?><?php echo "\n"; ?>

WP_DEBUG:                 <?php echo defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' . "\n" : 'Disabled' . "\n" : 'Not set' . "\n" ?>

WP Table Prefix:          <?php echo 'Length: '. strlen( $wpdb->prefix ); echo ' Status:'; if ( strlen( $wpdb->prefix ) > 16 ) { echo ' ERROR: Too Long'; } else { echo ' Acceptable'; } echo "\n"; ?>

Show On Front:            <?php echo esc_textarea( get_option( 'show_on_front' ) ) . "\n" ?>
Page On Front:            <?php $id = get_option( 'page_on_front' ); echo esc_textarea( get_the_title( $id ) . ' (#' . $id . ')' ) . "\n" ?>
Page For Posts:           <?php $id = get_option( 'page_for_posts' ); echo esc_textarea( get_the_title( $id ) . ' (#' . $id . ')' ) . "\n" ?>

<?php
$request['cmd'] = '_notify-validate';

$params = array(
	'sslverify'		=> false,
	'timeout'		=> 60,
	'user-agent'	=> 'SearchWP',
	'body'			=> $request,
);

$response = wp_remote_post( 'https://searchwp.com/', $params );

if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
	$WP_REMOTE_POST = 'wp_remote_post() works' . "\n";
} else {
	$WP_REMOTE_POST = 'wp_remote_post() does not work' . "\n";
}
?>
WP Remote Post:           <?php echo esc_textarea( $WP_REMOTE_POST ); ?>

Session:                  <?php echo isset( $_SESSION ) ? 'Enabled' : 'Disabled'; ?><?php echo "\n"; ?>
Session Name:             <?php echo esc_html( ini_get( 'session.name' ) ); ?><?php echo "\n"; ?>
Cookie Path:              <?php echo esc_html( ini_get( 'session.cookie_path' ) ); ?><?php echo "\n"; ?>
Save Path:                <?php echo esc_html( ini_get( 'session.save_path' ) ); ?><?php echo "\n"; ?>
Use Cookies:              <?php echo ini_get( 'session.use_cookies' ) ? 'On' : 'Off'; ?><?php echo "\n"; ?>
Use Only Cookies:         <?php echo ini_get( 'session.use_only_cookies' ) ? 'On' : 'Off'; ?><?php echo "\n"; ?>

DISPLAY ERRORS:           <?php echo ( ini_get( 'display_errors' ) ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A'; ?><?php echo "\n"; ?>
FSOCKOPEN:                <?php echo ( function_exists( 'fsockopen' ) ) ? 'Your server supports fsockopen.' : 'Your server does not support fsockopen.'; ?><?php echo "\n"; ?>
cURL:                     <?php echo ( function_exists( 'curl_init' ) ) ? 'Your server supports cURL.' : 'Your server does not support cURL.'; ?><?php echo "\n"; ?>
SOAP Client:              <?php echo ( class_exists( 'SoapClient' ) ) ? 'Your server has the SOAP Client enabled.' : 'Your server does not have the SOAP Client enabled.'; ?><?php echo "\n"; ?>
SUHOSIN:                  <?php echo ( extension_loaded( 'suhosin' ) ) ? 'Your server has SUHOSIN installed.' : 'Your server does not have SUHOSIN installed.'; ?><?php echo "\n"; ?>

TEMPLATES:

search.php                <?php echo file_exists( get_stylesheet_directory() . '/search.php' ) ? 'Yes' : 'No'; ?>


POTENTIAL TEMPLATE CONFLICTS:

<?php
$conflicts = new SearchWP_Conflicts();
if ( ! empty( $conflicts->search_template_conflicts ) ) {
	foreach ( $conflicts->search_template_conflicts as $line_number => $the_conflicts ) {
		echo esc_textarea( 'Line ' . absint( $line_number ) . ': ' . implode( ', ', $the_conflicts ) ) . "\n";
	}
} else {
	echo "NONE\n";
}
?>

POTENTIAL FILTER CONFLICTS

<?php
if ( ! empty( $conflicts->filter_conflicts ) ) {
	foreach ( $conflicts->filter_conflicts as $filter_name => $potential_conflict ) {
		foreach ( $potential_conflict as $conflict ) {
			echo esc_textarea( $filter_name . ' => ' . $conflict ) . "\n";
		}
	}
} else {
	echo "NONE\n";
}
?>

ACTIVE PLUGINS:

<?php
$plugins = get_plugins();
$active_plugins = get_option( 'active_plugins', array() );

foreach ( $plugins as $plugin_path => $plugin ) {
	// if the plugin isn't active, don't show it.
	if ( ! in_array( $plugin_path, $active_plugins ) ) {
		continue;
	}

	echo esc_textarea( $plugin['Name'] . ': ' . $plugin['Version'] ) . "\n";
}

if ( is_multisite() ) :
	?>

	NETWORK ACTIVE PLUGINS:

	<?php
	$plugins = wp_get_active_network_plugins();
	$active_plugins = get_site_option( 'active_sitewide_plugins', array() );

	foreach ( $plugins as $plugin_path ) {
		$plugin_base = plugin_basename( $plugin_path );

		// If the plugin isn't active, don't show it.
		if ( ! array_key_exists( $plugin_base, $active_plugins ) ) {
			continue;
		}

		$plugin = get_plugin_data( $plugin_path );

		echo esc_textarea( $plugin['Name'] . ' :' . $plugin['Version'] ) . "\n";
	}

endif; ?>

STATS:

<?php

if ( isset( $this->searchwp->settings['stats'] ) ) {
	if ( ! empty( $this->searchwp->settings['stats']['last_activity'] ) ) {
		$this->searchwp->settings['stats']['last_activity'] = human_time_diff( $this->searchwp->settings['stats']['last_activity'], current_time( 'timestamp' ) ) . ' ago';
	}
	echo esc_textarea( print_r( $this->searchwp->settings['stats'], true ) );
	echo "\n";
} else {
	echo esc_textarea( print_r( get_option( SEARCHWP_PREFIX . 'settings' ), true ) );
	echo "\n";
}

$indexer = new SearchWPIndexer();
$row_count = $indexer->get_main_table_row_count();
echo 'Main table row count: ';
echo absint( $row_count );
echo "\n";
if ( isset( $this->searchwp->settings['running'] ) ) {
	echo 'Running: ';
	echo ! empty( $this->searchwp->settings['running'] ) ? 'Yes' : 'No';
	echo "\n";
}
if ( isset( $this->searchwp->settings['busy'] ) ) {
	echo 'Busy: ';
	echo ! empty( $this->searchwp->settings['busy'] ) ? 'Yes' : 'No';
	echo "\n";
}
if ( isset( $this->searchwp->settings['doing_delta'] ) ) {
	echo 'Doing Delta: ';
	echo ! empty( $this->searchwp->settings['running'] ) ? 'Yes' : 'No';
	echo "\n";
}
if ( isset( $this->searchwp->settings['processing_purge_queue'] ) ) {
	echo 'Processing Purge Queue: ';
	echo ! empty( $this->searchwp->settings['processing_purge_queue'] ) ? 'Yes' : 'No';
	echo "\n";
}
if ( isset( $this->searchwp->settings['paused'] ) ) {
	echo 'Paused: ';
	echo ! empty( $this->searchwp->settings['paused'] ) ? 'Yes' : 'No';
	echo "\n";
}
?>

SETTINGS:

<?php if ( isset( $this->searchwp->settings['engines'] ) ) { echo esc_textarea( print_r( $this->searchwp->settings['engines'], true ) ); } ?>

PURGE QUEUE:

<?php echo isset( $this->searchwp->settings['purgeQueue'] ) ? esc_textarea( print_r( $this->searchwp->settings['purgeQueue'], true ) ) : '[Empty]'; ?>


### End System Info ###</textarea></form>
	<?php }

}
