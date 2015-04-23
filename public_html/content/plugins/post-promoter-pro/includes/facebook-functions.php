<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Return if Facebook account is found
 * @return bool If the Twitter object exists
 */
function ppp_facebook_enabled() {
	global $ppp_social_settings;

	if ( isset( $ppp_social_settings['facebook'] ) && !empty( $ppp_social_settings['facebook'] ) ) {
		return true;
	}

	return false;
}

/**
 * Register Facebook as a service
 * @param  array $services The Currently registered services
 * @return array           The services with Facebook added
 */
function ppp_fb_register_service( $services ) {
	$services[] = 'fb';

	return $services;
}
add_filter( 'ppp_register_social_service', 'ppp_fb_register_service', 10, 1 );

/**
 * Registers the facebook icon
 * @param  string $string The item passed into the list icons
 * @return string         The Facebook Icon
 */
function ppp_fb_account_list_icon( $string ) {
	return '<span class="dashicons icon-ppp-fb"></span>';
}
add_filter( 'ppp_account_list_icon-fb', 'ppp_fb_account_list_icon', 10, 1 );

/**
 * Show the Facebook Avatar in the account list
 * @param  string $string The list default
 * @return string         The Facebook avatar
 */
function ppp_fb_account_list_avatar( $string ) {

	if ( ppp_facebook_enabled() ) {
		global $ppp_social_settings;
		$avatar_url = $ppp_social_settings['facebook']->avatar;
		$string = '<img class="ppp-social-icon" src="' . $avatar_url . '" />';
	}

	return $string;
}
add_filter( 'ppp_account_list_avatar-fb', 'ppp_fb_account_list_avatar', 10, 1 );

/**
 * Adds Facebook name to the list-class
 * @param  string $string The default name
 * @return string         The name of the auth'd Facebook Profile
 */
function ppp_fb_account_list_name( $string ) {

	if ( ppp_facebook_enabled() ) {
		global $ppp_social_settings;
		$string  = $ppp_social_settings['facebook']->name;
	}

	return $string;
}
add_filter( 'ppp_account_list_name-fb', 'ppp_fb_account_list_name', 10, 1 );

/**
 * The Facebook actions for the list view
 * @param  string $string The default list view actions
 * @return string         The HTML for the actions
 */
function ppp_fb_account_list_actions( $string ) {

	if ( ! ppp_facebook_enabled() ) {
		global $ppp_facebook_oauth, $ppp_social_settings;
		$fb_authurl = $ppp_facebook_oauth->ppp_get_facebook_auth_url( admin_url( 'admin.php?page=ppp-social-settings' ) );

		$string = '<a class="button-primary" href="' . $fb_authurl . '">' . __( 'Connect to Facebook', 'ppp-txt' ) . '</a>';
	} else {
		$string  = '<a class="button-primary" href="' . admin_url( 'admin.php?page=ppp-social-settings&ppp_social_disconnect=true&ppp_network=facebook' ) . '" >' . __( 'Disconnect from Facebook', 'ppp-txt' ) . '</a>&nbsp;';
	}

	return $string;
}
add_filter( 'ppp_account_list_actions-fb', 'ppp_fb_account_list_actions', 10, 1 );

/**
 * The Facebook Extras section for the list-class
 * @param  string $string The default extras colun
 * @return string         The HTML for the Pages dropdown and debug info
 */
function ppp_fb_account_list_extras( $string ) {

	if ( ppp_facebook_enabled() ) {
		global $ppp_social_settings, $ppp_facebook_oauth, $ppp_options;
		$pages = $ppp_facebook_oauth->ppp_get_fb_user_pages( $ppp_social_settings['facebook']->access_token );
		$selected = isset( $ppp_social_settings['facebook']->page ) ? $ppp_social_settings['facebook']->page : 'me';

		if ( !empty( $pages ) ) {
			$string = '<label>' . __( 'Publish as:', 'ppp-txt' ) . '</label><br />';
			$string .= '<select id="fb-page">';
			$string .= '<option value="me">' . __( 'Me', 'ppp-txt' ) . '</option>';
			foreach ( $pages as $page ) {
				$value = $page->name . '|' . $page->access_token . '|' . $page->id;
				$string .= '<option ' . selected( $value, $selected, false ) . ' value="' . $value . '">' . $page->name . '</option>';
			}
			$string .= '</select><span class="spinner"></span>';
		}

		if ( $ppp_options['enable_debug'] ) {
			$days_left  = absint( round( ( $ppp_social_settings['facebook']->expires_on - current_time( 'timestamp' ) ) / DAY_IN_SECONDS ) );
			$refresh_in = absint( round( ( get_option( '_ppp_facebook_refresh' ) - current_time( 'timestamp' ) ) / DAY_IN_SECONDS ) );

			$string .= '<br />' . sprintf( __( 'Token expires in %s days' , 'ppp-txt' ), $days_left );
			$string .= '<br />' . sprintf( __( 'Refresh notice in %s days', 'ppp-txt' ), $refresh_in );
		}
	}

	return $string;
}
add_filter( 'ppp_account_list_extras-fb', 'ppp_fb_account_list_extras', 10, 1 );

/**
 * Sets the constants for the oAuth tokens for Twitter
 * @param  array $social_tokens The tokens stored in the transient
 * @return void
 */
function ppp_set_fb_token_constants( $social_tokens ) {
	if ( !empty( $social_tokens ) && property_exists( $social_tokens, 'facebook' ) ) {
		define( 'PPP_FB_APP_ID', $social_tokens->facebook->app_id );
		define( 'PPP_FB_APP_SECRET', $social_tokens->facebook->app_secret );
	}
}
add_action( 'ppp_set_social_token_constants', 'ppp_set_fb_token_constants', 10, 1 );

/**
 * Capture the oauth return from facebook
 * @return void
 */
function ppp_capture_facebook_oauth() {
	$should_capture = false;

	if ( isset( $_GET['state'] ) && strpos( $_GET['state'], 'ppp-local-keys-fb' ) !== false ) {
		// Local config
		$should_capture = true;
	}

	if ( isset( $_REQUEST['fb_access_token'] ) ) {
		// Returning from remote config
		$should_capture = true;
	}

	if ( $should_capture && ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'ppp-social-settings' ) ) {
		global $ppp_facebook_oauth;
		$ppp_facebook_oauth->ppp_initialize_facebook();
		wp_redirect( admin_url( 'admin.php?page=ppp-social-settings' ) );
		die();
	}

}
add_action( 'admin_init', 'ppp_capture_facebook_oauth', 10 );

/**
 * Capture the disconnect request from Facebook
 * @return void
 */
function ppp_disconnect_facebook() {
	global $ppp_social_settings;
	$ppp_social_settings = get_option( 'ppp_social_settings' );
	if ( isset( $ppp_social_settings['facebook'] ) ) {
		unset( $ppp_social_settings['facebook'] );
		update_option( 'ppp_social_settings', $ppp_social_settings );
		delete_option( '_ppp_facebook_refresh' );
	}
}
add_action( 'ppp_disconnect-facebook', 'ppp_disconnect_facebook', 10 );

/**
 * Add query vars for Facebook
 * @param  array $vars Currenty Query Vars
 * @return array       Query vars array with facebook added
 */
function ppp_fb_query_vars( $vars ) {
	$vars[] = 'fb_access_token';
	$vars[] = 'expires_in';

	return $vars;
}
add_filter( 'query_vars', 'ppp_fb_query_vars' );

/**
 * Refreshes the Facebook Access Token
 * @return void
 */
function ppp_fb_execute_refresh() {

	if ( ! ppp_facebook_enabled() ) {
		return;
	}

	$refresh_date = (int) get_option( '_ppp_facebook_refresh', true );

	if ( current_time( 'timestamp' ) > $refresh_date ) {
		add_action( 'admin_notices', 'ppp_facebook_refresh_notice' );
	}
}
add_action( 'admin_init', 'ppp_fb_execute_refresh', 99 );

/**
 * Displays notice when the Facebook Token is nearing expiration
 * @return void
 */
function ppp_facebook_refresh_notice() {

	if ( ! ppp_facebook_enabled() ) {
		return;
	}

	global $ppp_facebook_oauth, $ppp_social_settings;

	// Look for the tokens coming back
	$ppp_facebook_oauth->ppp_initialize_facebook();

	$token = $ppp_social_settings['facebook']->access_token;
	$url = $ppp_facebook_oauth->ppp_get_facebook_auth_url( admin_url( 'admin.php?page=ppp-social-settings' ) );
	$url = str_replace( '?ppp-social-auth', '?ppp-social-auth&ppp-refresh=true&access_token=' . $token, $url );

	$days_left = absint( round( ( $ppp_social_settings['facebook']->expires_on - current_time( 'timestamp' ) ) / DAY_IN_SECONDS ) );
	?>
	<div class="update-nag">
		<?php if ( $days_left > 0 ): ?>
			<p><strong>Post Promoter Pro: </strong><?php printf( __( 'Your Facebook authentication expires in within %d days. Please <a href="%s">refresh access</a>.', 'ppp-txt' ), $days_left, $url ); ?></p>
		<?php elseif ( $days_left < 1 ): ?>
			<p><strong>Post Promoter Pro: </strong><?php printf( __( 'Your Facebook authentication has expired. Please <a href="%s">refresh access</a>.', 'ppp-txt' ), $url ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Share a post to Facebook
 * @param  string $link        The link to Share
 * @param  string $message     The message attached to the link
 * @return array               The results array from the API
 */
function ppp_fb_share( $link, $message, $picture ) {
	global $ppp_facebook_oauth;

	return $ppp_facebook_oauth->ppp_fb_share_link( $link, ppp_entities_and_slashes( $message ), $picture );
}

/**
 * Registers the thumbnail size for Facebook
 * @return void
 */
function ppp_fb_register_thumbnail_size() {
	add_image_size( 'ppp-fb-share-image', 1200, 627, true );
}
add_action( 'ppp_add_image_sizes', 'ppp_fb_register_thumbnail_size' );

/**
 * Add Facebook to the Meta Box Tabs
 * @param  array $tabs Existing Metabox Tabs
 * @return array       Metabox tabs with Facebook
 */
function ppp_fb_add_meta_tab( $tabs ) {
	global $ppp_social_settings;
	if ( ! ppp_facebook_enabled() ) {
		return $tabs;
	}

	$tabs['fb'] = array( 'name' => __( 'Facebook', 'ppp-txt' ), 'class' => 'icon-ppp-fb' );

	return $tabs;
}
add_filter( 'ppp_metabox_tabs', 'ppp_fb_add_meta_tab', 10, 1 );

/**
 * Add Facebook to the Metabox Content
 * @param  array $content The existing metabox content
 * @return array          With Facebook
 */
function ppp_fb_register_metabox_content( $content ) {
	global $ppp_social_settings;
	if ( ! ppp_facebook_enabled() ) {
		return $content;
	}

	$content[] = 'fb';

	return $content;
}
add_filter( 'ppp_metabox_content', 'ppp_fb_register_metabox_content', 10, 1 );

/**
 * Render the Metabox content for Facebook
 * @param  [type] $post [description]
 * @return [type]       [description]
 */
function ppp_fb_add_metabox_content( $post ) {
	global $ppp_options;
	$default_text = !empty( $ppp_options['default_text'] ) ? $ppp_options['default_text'] : __( 'Social Text', 'ppp-txt' );

	$ppp_fb_share_on_publish = get_post_meta( $post->ID, '_ppp_fb_share_on_publish', true );
	$ppp_share_on_publish_title = get_post_meta( $post->ID, '_ppp_fb_share_on_publish_title', true );
	$ppp_share_on_publish_desc = get_post_meta( $post->ID, '_ppp_fb_share_on_publish_desc', true );

	?>
	<p>
	<?php $disabled = ( $post->post_status === 'publish' && time() > strtotime( $post->post_date ) ) ? true : false; ?>
	<input <?php if ( $disabled ): ?>readonly<?php endif; ?> type="checkbox" name="_ppp_fb_share_on_publish" id="ppp_fb_share_on_publish" value="1" <?php checked( '1', $ppp_fb_share_on_publish, true ); ?> />&nbsp;
		<label for="ppp_fb_share_on_publish"><?php _e( 'Share this post on Facebook at the time of publishing?', 'ppp-txt' ); ?></label>
		<p class="ppp_share_on_publish_text" style="display: <?php echo ( $ppp_fb_share_on_publish ) ? '' : 'none'; ?>">
			<?php _e( 'Link Message', 'ppp-txt' ); ?>:<br />
				<input
				<?php if ( $disabled ): ?>readonly<?php endif; ?>
				class="ppp-share-text"
				type="text"
				placeholder="<?php echo $default_text; ?>"
				name="_ppp_fb_share_on_publish_title"
				<?php if ( isset( $ppp_share_on_publish_title ) ) {?>value="<?php echo htmlspecialchars( $ppp_share_on_publish_title ); ?>"<?php ;}?>
			/>
				<span id="ppp-fb-image">
					<br /><br />
					<?php echo get_the_post_thumbnail( $post->ID, 'ppp-fb-share-image' ); ?>
				</span>
			<br /><?php _e( 'Note: If set, the Featured image will be attached to this share', 'ppp-txt' ); ?>
		</p>
	</p>
	<?php
}
add_action( 'ppp_generate_metabox_content-fb', 'ppp_fb_add_metabox_content', 10, 1 );

/**
 * Save the items in our meta boxes
 * @param  int $post_id The Post ID being saved
 * @param  object $post    The Post Object being saved
 * @return int          The Post ID
 */
function ppp_fb_save_post_meta_boxes( $post_id, $post ) {

	if ( ! ppp_should_save( $post_id, $post ) ) {
		return;
	}

	$ppp_fb_share_on_publish = ( isset( $_REQUEST['_ppp_fb_share_on_publish'] ) ) ? $_REQUEST['_ppp_fb_share_on_publish'] : '0';
	$ppp_share_on_publish_title = ( isset( $_REQUEST['_ppp_fb_share_on_publish_title'] ) ) ? $_REQUEST['_ppp_fb_share_on_publish_title'] : '';

	update_post_meta( $post_id, '_ppp_fb_share_on_publish', $ppp_fb_share_on_publish );
	update_post_meta( $post_id, '_ppp_fb_share_on_publish_title', $ppp_share_on_publish_title );
}
add_action( 'save_post', 'ppp_fb_save_post_meta_boxes', 10, 2 ); // save the custom fields

/**
 * Share a Facebook post on Publish
 * @param  string $old_status The old post status
 * @param  string $new_status The new post status
 * @param  object $post       The Post object
 * @return void
 */
function ppp_fb_share_on_publish( $new_status, $old_status, $post ) {
	global $ppp_options;
	$from_meta = get_post_meta( $post->ID, '_ppp_fb_share_on_publish', true );
	$from_post = isset( $_POST['_ppp_fb_share_on_publish'] );

	if ( empty( $from_meta ) && empty( $from_post ) ) {
		return;
	}

	// Determine if we're seeing the share on publish in meta or $_POST
	if ( $from_meta && !$from_post ) {
		$ppp_share_on_publish_title = get_post_meta( $post->ID, '_ppp_fb_share_on_publish_title', true );
	} else {
		$ppp_share_on_publish_title = isset( $_POST['_ppp_fb_share_on_publish_title'] ) ? $_POST['_ppp_fb_share_on_publish_title'] : '';
	}

	$thumbnail = ppp_post_has_media( $post->ID, 'fb', true );

	$name = 'sharedate_0_' . $post->ID . '_fb';

	$default_title = isset( $ppp_options['default_text'] ) ? $ppp_options['default_text'] : '';
	// If an override was found, use it, otherwise try the default text content
	$share_title = ( isset( $ppp_share_on_publish_title ) && !empty( $ppp_share_on_publish_title ) ) ? $ppp_share_on_publish_title : $default_title;

	// If the content is still empty, just use the post title
	$share_title = ( isset( $share_title ) && !empty( $share_title ) ) ? $share_title : get_the_title( $post->ID );

	$share_title = apply_filters( 'ppp_share_content', $share_title, array( 'post_id' => $post->ID ) );
	$share_link = ppp_generate_link( $post->ID, $name, true );

	$status['facebook'] = ppp_fb_share( $share_link, $share_title, $thumbnail );

	if ( isset( $ppp_options['enable_debug'] ) && $ppp_options['enable_debug'] == '1' ) {
		update_post_meta( $post->ID, '_ppp-' . $name . '-status', $status );
	}
}
add_action( 'ppp_share_on_publish', 'ppp_fb_share_on_publish', 10, 3 );


/**
 * Update the Post As field for Facebook
 * @return sends 1 when successfully updated
 */
function ppp_fb_update_page() {
	global $ppp_social_settings, $ppp_facebook_oauth;

	ppp_set_social_tokens();

	$account = isset( $_POST['account'] ) ? $_POST['account'] : false;

	if ( !empty( $account ) ) {
		$ppp_social_settings['facebook']->page = $account;

		update_option( 'ppp_social_settings', $ppp_social_settings );
		echo 1;
	} else {
		echo 0;
	}

	die(); // this is required to return a proper result
}
add_action( 'wp_ajax_fb_set_page', 'ppp_fb_update_page' );
