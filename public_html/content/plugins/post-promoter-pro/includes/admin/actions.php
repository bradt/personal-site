<?php

function ppp_capture_twitter_oauth() {
	if ( isset( $_REQUEST['oauth_verifier'] ) && isset( $_REQUEST['oauth_token'] ) ) {
		global $ppp_twitter_oauth;
		$ppp_twitter_oauth->ppp_initialize_twitter();
		wp_redirect( admin_url( 'admin.php?page=ppp-social-settings' ) );
		die();
	}
}
add_action( 'admin_init', 'ppp_capture_twitter_oauth', 10 );

/**
 * Adds the Bit.ly Shortener to the list of available shorteners
 * @param  string $selected_shortener The currently selected url shortener
 * @return void
 */
function ppp_add_bitly_shortener( $selected_shortener ) {
	?><option value="bitly" <?php selected( $selected_shortener, 'bitly', true ); ?>>Bit.ly</option><?php
}
add_action( 'ppp_url_shorteners', 'ppp_add_bitly_shortener', 10, 1 );

/**
 * Displays the bitly settings area when bitly is selected as the URL shortener
 * @return void
 */
function ppp_display_bitly_settings() {
	global $ppp_bitly_oauth, $ppp_social_settings;
	?>
	<p>
		<?php if ( !ppp_bitly_enabled() ) : ?>
		<form autocomplete="off">
			<input id="bitly-username" name="ppp-bitly-username" autocomplete="off" type="text" value="" placeholder="Bit.ly Username" size="25" /><br />
			<input id="bitly-password" name="ppp-bitly-password" autocomplete="off" type="password" value="" placeholder="Bit.ly Password" size="25" /><br />
			<span id="ppp-bitly-invalid-login" style="color: #993333; display: none;"><?php _e( 'Invalid Login or Password', 'ppp-txt' ); ?></span><br />
			<div id="ppp-bitly-login-form-submit">
				<a href="#" id="bitly-login" class="button-primary">Connect To Bit.ly</a><span class="spinner"></span>
			</div>
		</form>
		<?php endif; ?>
		<?php if ( ppp_bitly_enabled() ) : ?>
			<div class="ppp-bitly-profile">
				<img class="ppp-social-icon" src="<?php echo $ppp_social_settings['bitly']['avatar']; ?>" />
				<div class="ppp-bitly-info">
					<?php _e( 'Signed in as', 'ppp-txt' ); ?>:<br /><?php echo $ppp_social_settings['bitly']['login']; ?><br />
					<?php _e( 'Access Token: ', 'ppp-txt' ); ?><code><?php echo $ppp_social_settings['bitly']['access_token']; ?></code>
				</div>
			</div>
			<p>
				<a class="button-primary" href="<?php echo admin_url( 'admin.php?page=ppp-social-settings&ppp_bitly_disconnect=true' ); ?>" ><?php _e( 'Disconnect from Bit.ly', 'ppp-txt' ); ?></a>
			</p>
		<?php endif; ?>
	</p>
	<?php
}
add_action( 'ppp_shortener_settings-bitly', 'ppp_display_bitly_settings', 10 );

/**
 * The steps to run when clicking to deactivate bit.ly
 * @return void
 */
function ppp_disconnect_bitly() {
	global $ppp_social_settings;
	if ( isset( $_GET['ppp_bitly_disconnect'] ) ) {
		$ppp_social_settings = get_option( 'ppp_social_settings' );
		if ( isset( $ppp_social_settings['bitly'] ) ) {
			unset( $ppp_social_settings['bitly'] );
			update_option( 'ppp_social_settings', $ppp_social_settings );
		}
	}
}
add_action( 'ppp_social_settings_pre_form', 'ppp_disconnect_bitly', 10 );