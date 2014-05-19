<?php
/**
 * Display the General settings tab
 * @return void
 */
function ppp_admin_page() {
	global $ppp_options;
	$license 	= get_option( '_ppp_license_key' );
	$status 	= get_option( '_ppp_license_key_status' );
	?>
	<div id="icon-options-general" class="icon32"></div><h2><?php _e( 'Post Promoter Pro', 'ppp-txt' ); ?></h2>
	<div class="wrap">
		<form method="post" action="options.php">
			<?php wp_nonce_field( 'ppp-options' ); ?>
			<table class="form-table">

				<tr valign="top">
					<th scope="row" valign="top">
						<?php _e( 'License Key', 'ppp-txt' ); ?><br /><span style="font-size: x-small;"><?php _e( 'Enter your license key', 'ppp-txt' ); ?></span>
					</th>
					<td>
						<input id="ppp_license_key" name="_ppp_license_key" type="text" class="regular-text" value="<?php esc_attr_e( $license ); ?>" /><?php if( $status !== false && $status == 'valid' ) { ?>
						<span style="color:green;">&nbsp;<?php _e( 'active', 'ppp-txt' ); ?></span><?php } ?>
					</td>
				</tr>

				<?php if( false !== $license ) { ?>
					<tr valign="top">
						<th scope="row" valign="top">
							<?php _e( 'Activate License', 'ppp-txt' ); ?>
						</th>
						<td>
							<?php if( $status !== false && $status == 'valid' ) { ?>
								<?php wp_nonce_field( 'ppp_deactivate_nonce', 'ppp_deactivate_nonce' ); ?>
								<input type="submit" class="button-secondary" name="ppp_license_deactivate" value="<?php _e( 'Deactivate License', 'ppp-txt' ); ?>"/>
							<?php } else {
								wp_nonce_field( 'ppp_activate_nonce', 'ppp_activate_nonce' ); ?>
								<input type="submit" class="button-secondary" name="ppp_license_activate" value="<?php _e( 'Activate License', 'ppp-txt' ); ?>"/>
							<?php } ?>
						</td>
					</tr>
				<?php } ?>

				<tr valign="top">
					<th scope="row"><?php _e( 'Default Share Times', 'ppp-txt' ); ?><br />
						<span style="font-size: x-small;"><?php _e( 'When would you like your posts to be shared? You can changes this on a per post basis as well', 'ppp-txt' ); ?></span></th>
					<td>
						<strong><?php _e( 'Days After Publish', 'ppp-txt' ); ?></strong>
						<table id="ppp-days-table">
							<tr>
								<td><label for="ppp_options[times][day1]">1</label></td>
								<td><label for="ppp_options[times][day2]">2</label></td>
								<td><label for="ppp_options[times][day3]">3</label></td>
								<td><label for="ppp_options[times][day4]">4</label></td>
								<td><label for="ppp_options[times][day5]">5</label></td>
								<td><label for="ppp_options[times][day6]">6</label></td>
							</tr>
							<tr>
								<td><input id="day1" type="text" name="ppp_options[times][day1]" class="share-time-selector"
									<?php if ( $ppp_options['times']['day1'] != '' ) {?>value="<?php echo htmlspecialchars( $ppp_options['times']['day1'] ); ?>"<?php ;}?> size="8" /></td>
								<td><input id="day2" type="text" name="ppp_options[times][day2]" class="share-time-selector"
									<?php if ( $ppp_options['times']['day2'] != '' ) {?>value="<?php echo htmlspecialchars( $ppp_options['times']['day2'] ); ?>"<?php ;}?> size="8" /></td>
								<td><input id="day3" type="text" name="ppp_options[times][day3]" class="share-time-selector"
									<?php if ( $ppp_options['times']['day3'] != '' ) {?>value="<?php echo htmlspecialchars( $ppp_options['times']['day3'] ); ?>"<?php ;}?> size="8" /></td>
								<td><input id="day4" type="text" name="ppp_options[times][day4]" class="share-time-selector"
									<?php if ( $ppp_options['times']['day4'] != '' ) {?>value="<?php echo htmlspecialchars( $ppp_options['times']['day4'] ); ?>"<?php ;}?> size="8" /></td>
								<td><input id="day5" type="text" name="ppp_options[times][day5]" class="share-time-selector"
									<?php if ( $ppp_options['times']['day5'] != '' ) {?>value="<?php echo htmlspecialchars( $ppp_options['times']['day5'] ); ?>"<?php ;}?> size="8" /></td>
								<td><input id="day6" type="text" name="ppp_options[times][day6]" class="share-time-selector"
									<?php if ( $ppp_options['times']['day6'] != '' ) {?>value="<?php echo htmlspecialchars( $ppp_options['times']['day6'] ); ?>"<?php ;}?> size="8" /></td>
							</tr>
						</table>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php _e( 'Post Types', 'ppp-txt' ); ?><br /><span style="font-size: x-small;"><?php _e( 'What post types do you want to schedule for?', 'ppp-txt' ); ?></span></th>
					<td>
						<?php $post_types = get_post_types( array( 'public' => true, 'publicly_queryable' => true ), NULL, 'and' ); ?>
						<?php if ( array_key_exists( 'attachment', $post_types ) ) { unset( $post_types['attachment'] ); } ?>
						<?php foreach ( $post_types as $post_type => $type_data ): ?>
							<?php $value = ( isset( $ppp_options['post_types'] ) && isset( $ppp_options['post_types'][$post_type] ) ) ? true : false; ?>
							<input type="checkbox" name="ppp_options[post_types][<?php echo $post_type; ?>]" value="1" id="<?php echo $post_type; ?>" <?php checked( true, $value, true ); ?> />&nbsp;
							<label for="<?php echo $post_type; ?>"><?php echo $type_data->labels->name; ?></label></br />
						<?php endforeach; ?>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php _e( 'Advanced', 'ppp-txt' ); ?><br /><span style="font-size: x-small;"><?php _e( 'Tools for troubleshooting and advanced usage', 'ppp-txt' ); ?></span></th>
					<td>
						<p>
						<?php $debug_enabled = isset( $ppp_options['enable_debug'] ) ? true : false; ?>
						<input type="checkbox" name="ppp_options[enable_debug]" <?php checked( true, $debug_enabled, true ); ?> value="1" /> <?php _e( 'Enable Debug', 'ppp-txt' ); ?>
						</p>
						<p>
						<?php $delete_on_uninstall = isset( $ppp_options['delete_on_uninstall'] ) ? true : false; ?>
						<input type="checkbox" name="ppp_options[delete_on_uninstall]" <?php checked( true, $delete_on_uninstall, true ); ?> value="1" /> <?php _e( 'Delete All Data On Uninstall', 'ppp-txt' ); ?>
						</p>
					</td>
				</tr>

				<?php do_action( 'ppp_general_settings_after' ); ?>

				<input type="hidden" name="action" value="update" />
				<?php $page_options = apply_filters( 'ppp_settings_page_options', array( 'ppp_options', '_ppp_license_key' ) ); ?>
				<input type="hidden" name="page_options" value="<?php echo implode( ',', $page_options ); ?>" />
				<?php settings_fields( 'ppp-options' ); ?>
			</table>
			<input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'ppp-txt' ) ?>" />
		</form>
	</div>
	<?php
}


/**
* Display the Social tab
* @return void
*/
function ppp_display_social() {
	if ( isset( $_GET['ppp_twitter_disconnect'] ) ) {
		$ppp_social_settings = get_option( 'ppp_social_settings' );
		if ( isset( $ppp_social_settings['twitter'] ) ) {
			unset( $ppp_social_settings['twitter'] );
			update_option( 'ppp_social_settings', $ppp_social_settings );
		}
	}

	global $ppp_twitter_oauth;
	$ppp_share_settings = get_option( 'ppp_share_settings' );
	$tw_auth = $ppp_twitter_oauth->ppp_verify_twitter_credentials();
	?>
	<div id="icon-options-general" class="icon32"></div><h2><?php _e( 'Post Promoter Pro - Social Settings', 'ppp-txt' ); ?></h2>
		<div class="wrap">
		<form method="post" action="options.php">
			<?php wp_nonce_field( 'ppp-share-settings' ); ?>
			<table class="form-table">

				<tr valign="top">
					<?php if ( isset( $tw_auth['error'] ) ) {
						?><div class="update error"><p><?php echo $tw_auth['error']; ?></p></div><?php
					} ?>
					<th scope="row"><?php _e( 'Twitter', 'ppp-txt' ); ?></th>
					<td>
						<?php $ppp_twitter_oauth->ppp_initialize_twitter(); ?>
						<?php $ppp_social_settings = get_option( 'ppp_social_settings' ); ?>

						<?php if ( !isset( $ppp_social_settings['twitter']['user'] ) ) { ?>
							<?php $tw_authurl = $ppp_twitter_oauth->ppp_get_twitter_auth_url(); ?>
							<a href="<?php echo $tw_authurl; ?>"><img src="<?php echo PPP_URL; ?>/includes/images/sign-in-with-twitter-gray.png" /></a>
						<?php } else { ?>
						<div class="ppp-twitter-profile">
							<img class="ppp-social-icon" src="<?php echo $ppp_social_settings['twitter']['user']->profile_image_url_https; ?>" />
							<div class="ppp-twitter-info"><?php _e( 'Signed in as', 'ppp-txt' ); ?>:<br /><?php echo $ppp_social_settings['twitter']['user']->name; ?></div>
						</div>
						<br />
						<a class="button-primary" href="<?php echo admin_url( 'admin.php?page=ppp-social-settings&ppp_twitter_disconnect=true' ); ?>" ><?php _e( 'Disconnect from Twitter', 'ppp-txt' ); ?></a>&nbsp;
						<a class="button-secondary" href="https://twitter.com/settings/applications" target="blank"><?php _e( 'Revoke Access via Twitter', 'ppp-txt' ); ?></a>
						<?php } ?>
					</td>
				</tr>

				<?php
				$uq_status = ( isset( $ppp_share_settings['ppp_unique_links'] ) && $ppp_share_settings['ppp_unique_links'] == '1' ) ? $ppp_share_settings['ppp_unique_links'] : 0;
				$ga_status = ( isset( $ppp_share_settings['ppp_ga_tags'] ) && $ppp_share_settings['ppp_ga_tags'] == '1' ) ? $ppp_share_settings['ppp_ga_tags'] : 0;
				?>
				<tr valign="top">
					<th scope="row" valign="top">
						<?php _e( 'Analytics', 'ppp-txt' ); ?></span>
					</th>
					<td id="ppp-analytics-options">
						<p>
							<input id="ppp_unique_links"
							       class="ppp-analytics-checkbox"
							       name="ppp_share_settings[ppp_unique_links]"
							       type="checkbox"
							       value="1"
							       <?php if ( !empty( $ga_status ) ): ?> disabled<?php endif; ?>
							       <?php checked( '1', $uq_status, true ); ?>
							/>&nbsp<label for="ppp_unique_links"><?php _e( 'Simple Tracking', 'ppp-txt' ); ?></label><br />
							<small><?php _e( 'Appends a query string to shared links for analytics.', 'ppp-txt' ); ?><br /></small>
						</p>
						<br />
						<p>
							<input id="ppp_ga_tags"
							       class="ppp-analytics-checkbox"
							       name="ppp_share_settings[ppp_ga_tags]"
							       type="checkbox"
							       value="1"
							       <?php if ( !empty( $uq_status ) ): ?> disabled<?php endif; ?>
							       <?php checked( '1', $ga_status, true ); ?>
							/>&nbsp<label for="ppp_ga_tags"><?php _e( 'Google Analytics Tags', 'ppp-txt' ); ?></label><br />
							<small><?php _e( 'Results can be seen in the Acquisition Menu under "Campaigns"', 'ppp-txt' ); ?></small>
						</p>
						<p id="ppp-link-example">
						<hr />
						<small>Here is an example of what your link will look like: <br />
							<?php $post = wp_get_recent_posts( array( 'numberposts' => 1 ) ); ?>
							<code><?php echo ppp_generate_link( $post[0]['ID'], 'sharedate_1_' . $post[0]['ID'] ); ?></code></small>
						</p>
					</td>
				</tr>

				<?php settings_fields( 'ppp-share-settings' ); ?>

				<input type="hidden" name="action" value="update" />
				<input type="hidden" name="page_options" value="ppp_share_settings" />


			</table>

			<input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'ppp-txt' ) ?>" />

		</form>
	</div>
	<?php
}

/**
* Display the List Table tab
* @return void
*/
function ppp_display_schedule() {
	?>
	<style type="text/css">
		.wp-list-table .column-day { width: 5%; }
		.wp-list-table .column-date { width: 20%; }
		.wp-list-table .column-post_title { width: 25%; }
		.wp-list-table .column-content { width: 50%; }
	</style>
	<?php
	require_once PPP_PATH . 'includes/admin/class-schedule-table.php';
	$schedule_table = new PPP_Schedule_Table();
	$schedule_table->prepare_items();
	?>
	<div id="icon-options-general" class="icon32"></div><h2><?php _e( 'Post Promoter Pro - Scheduled Shares', 'ppp-txt' ); ?></h2>
	<div class="wrap">
		<?php $schedule_table->display() ?>
	</div>
	<?php
}

/**
 * Display the System Info Tab
 * @return void
 */
function ppp_display_sysinfo() {
	global $wpdb;
	global $ppp_options;
	?>
	<div id="icon-options-general" class="icon32"></div><h2><?php _e( 'Post Promoter Pro - System Info', 'ppp-txt' ); ?></h2>
		<div class="wrap">
		<textarea style="font-family: Menlo, Monaco, monospace; white-space: pre" onclick="this.focus();this.select()" readonly cols="150" rows="35">
	SITE_URL:                 <?php echo site_url() . "\n"; ?>
	HOME_URL:                 <?php echo home_url() . "\n"; ?>

	PPP Version:             <?php echo PPP_VERSION . "\n"; ?>
	WordPress Version:        <?php echo get_bloginfo( 'version' ) . "\n"; ?>

	PPP SETTINGS:
	<?php
	foreach ( $ppp_options as $name => $value ) {
	if ( $value == false )
		$value = 'false';

	if ( $value == '1' )
		$value = 'true';

	echo $name . ': ' . maybe_serialize( $value ) . "\n";
	}
	?>

	ACTIVE PLUGINS:
	<?php
	$plugins = get_plugins();
	$active_plugins = get_option( 'active_plugins', array() );

	foreach ( $plugins as $plugin_path => $plugin ) {
		// If the plugin isn't active, don't show it.
		if ( ! in_array( $plugin_path, $active_plugins ) )
			continue;

	echo $plugin['Name']; ?>: <?php echo $plugin['Version'] ."\n";

	}
	?>

	CURRENT THEME:
	<?php
	if ( get_bloginfo( 'version' ) < '3.4' ) {
		$theme_data = get_theme_data( get_stylesheet_directory() . '/style.css' );
		echo $theme_data['Name'] . ': ' . $theme_data['Version'];
	} else {
		$theme_data = wp_get_theme();
		echo $theme_data->Name . ': ' . $theme_data->Version;
	}
	?>


	Multi-site:               <?php echo is_multisite() ? 'Yes' . "\n" : 'No' . "\n" ?>

	ADVANCED INFO:
	PHP Version:              <?php echo PHP_VERSION . "\n"; ?>
	MySQL Version:            <?php echo mysql_get_server_info() . "\n"; ?>
	Web Server Info:          <?php echo $_SERVER['SERVER_SOFTWARE'] . "\n"; ?>

	PHP Memory Limit:         <?php echo ini_get( 'memory_limit' ) . "\n"; ?>
	PHP Post Max Size:        <?php echo ini_get( 'post_max_size' ) . "\n"; ?>
	PHP Time Limit:           <?php echo ini_get( 'max_execution_time' ) . "\n"; ?>

	WP_DEBUG:                 <?php echo defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' . "\n" : 'Disabled' . "\n" : 'Not set' . "\n" ?>

	WP Table Prefix:          <?php echo "Length: ". strlen( $wpdb->prefix ); echo " Status:"; if ( strlen( $wpdb->prefix )>16 ) {echo " ERROR: Too Long";} else {echo " Acceptable";} echo "\n"; ?>

	Show On Front:            <?php echo get_option( 'show_on_front' ) . "\n" ?>
	Page On Front:            <?php $id = get_option( 'page_on_front' ); echo get_the_title( $id ) . ' #' . $id . "\n" ?>
	Page For Posts:           <?php $id = get_option( 'page_on_front' ); echo get_the_title( $id ) . ' #' . $id . "\n" ?>

	Session:                  <?php echo isset( $_SESSION ) ? 'Enabled' : 'Disabled'; ?><?php echo "\n"; ?>
	Session Name:             <?php echo esc_html( ini_get( 'session.name' ) ); ?><?php echo "\n"; ?>
	Cookie Path:              <?php echo esc_html( ini_get( 'session.cookie_path' ) ); ?><?php echo "\n"; ?>
	Save Path:                <?php echo esc_html( ini_get( 'session.save_path' ) ); ?><?php echo "\n"; ?>
	Use Cookies:              <?php echo ini_get( 'session.use_cookies' ) ? 'On' : 'Off'; ?><?php echo "\n"; ?>
	Use Only Cookies:         <?php echo ini_get( 'session.use_only_cookies' ) ? 'On' : 'Off'; ?><?php echo "\n"; ?>

	UPLOAD_MAX_FILESIZE:      <?php if ( function_exists( 'phpversion' ) ) echo ini_get( 'upload_max_filesize' ); ?><?php echo "\n"; ?>
	POST_MAX_SIZE:            <?php if ( function_exists( 'phpversion' ) ) echo ini_get( 'post_max_size' ); ?><?php echo "\n"; ?>
	WordPress Memory Limit:   <?php echo WP_MEMORY_LIMIT; ?><?php echo "\n"; ?>
	DISPLAY ERRORS:           <?php echo ( ini_get( 'display_errors' ) ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A'; ?><?php echo "\n"; ?>
	FSOCKOPEN:                <?php echo ( function_exists( 'fsockopen' ) ) ? __( 'Your server supports fsockopen.', 'ppp-txt' ) : __( 'Your server does not support fsockopen.', 'ppp-txt' ); ?><?php echo "\n"; ?>
		</textarea>
	</div>
	<?php
}