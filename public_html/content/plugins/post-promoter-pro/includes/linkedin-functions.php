<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Return if linkedin account is found
 * @return bool If the Linkedin object exists
 */
function ppp_linkedin_enabled() {
	global $ppp_social_settings;

	if ( isset( $ppp_social_settings['linkedin'] ) && !empty( $ppp_social_settings['linkedin'] ) ) {
		return true;
	}

	return false;
}

/**
 * Registers LinkedIn as a service
 * @param  array $services The registered servcies
 * @return array           With LinkedIn added
 */
function ppp_li_register_service( $services ) {
	$services[] = 'li';

	return $services;
}
add_filter( 'ppp_register_social_service', 'ppp_li_register_service', 10, 1 );

/**
 * The LinkedIn icon
 * @param  string $string Default list view string for icon
 * @return string         The LinkedIn Icon HTML
 */
function ppp_li_account_list_icon( $string ) {
	return '<span class="dashicons icon-ppp-li"></span>';
}
add_filter( 'ppp_account_list_icon-li', 'ppp_li_account_list_icon', 10, 1 );

/**
 * The LinkedIn Avatar for the account list
 * @param  string $string Default icon string
 * @return string         The HTML for the LinkedIn Avatar
 */
function ppp_li_account_list_avatar( $string ) {
	return $string;
}
add_filter( 'ppp_account_list_avatar-li', 'ppp_li_account_list_avatar', 10, 1 );

/**
 * The name for the linked LinkedIn account
 * @param  string $string The default list name
 * @return string         The name for the attached LinkedIn account
 */
function ppp_li_account_list_name( $string ) {

	if ( ppp_linkedin_enabled() ) {
		global $ppp_social_settings;
		$string  = $ppp_social_settings['linkedin']->firstName . ' ' . $ppp_social_settings['linkedin']->lastName;
		$string .= '<br />' . $ppp_social_settings['linkedin']->headline;
	}

	return $string;
}
add_filter( 'ppp_account_list_name-li', 'ppp_li_account_list_name', 10, 1 );

/**
 * The actions column of the accounts list for LinkedIn
 * @param  string $string The default actions string
 * @return string         HTML for the LinkedIn Actions
 */
function ppp_li_account_list_actions( $string ) {

	if ( ! ppp_linkedin_enabled() ) {
		global $ppp_linkedin_oauth, $ppp_social_settings;
		$li_authurl = $ppp_linkedin_oauth->ppp_get_linkedin_auth_url( get_bloginfo( 'url' ) . $_SERVER['REQUEST_URI'] );

		$string = '<a class="button-primary" href="' . $li_authurl . '">' . __( 'Connect to Linkedin', 'ppp-txt' ) . '</a>';
	} else {
		$string  = '<a class="button-primary" href="' . admin_url( 'admin.php?page=ppp-social-settings&ppp_social_disconnect=true&ppp_network=linkedin' ) . '" >' . __( 'Disconnect from Linkedin', 'ppp-txt' ) . '</a>&nbsp;';
	}

	return $string;
}
add_filter( 'ppp_account_list_actions-li', 'ppp_li_account_list_actions', 10, 1 );

/**
 * The Extras column for the account list for LinkedIn
 * @param  string $string Default extras column string
 * @return string         The HTML for the LinkedIn Extras column
 */
function ppp_li_account_list_extras( $string ) {
	if ( ppp_linkedin_enabled() ) {
		global $ppp_social_settings, $ppp_options;
		if ( $ppp_options['enable_debug'] ) {
			$days_left  = round( ( $ppp_social_settings['linkedin']->expires_on - current_time( 'timestamp' ) ) / DAY_IN_SECONDS );
			$refresh_in = round( ( get_option( '_ppp_linkedin_refresh' ) - current_time( 'timestamp' ) ) / DAY_IN_SECONDS );

			$string .= '<br />' . sprintf( __( 'Token expires in %s days' , 'ppp-txt' ), $days_left );
			$string .= '<br />' . sprintf( __( 'Refresh notice in %s days', 'ppp-txt' ), $refresh_in );
		}
	}

	return $string;

}
add_filter( 'ppp_account_list_extras-li', 'ppp_li_account_list_extras', 10, 1 );

/**
 * Capture the oauth return from linkedin
 * @return void
 */
function ppp_capture_linkedin_oauth() {
	if ( isset( $_REQUEST['li_access_token'] ) && ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'ppp-social-settings' ) ) {
		global $ppp_linkedin_oauth;
		$ppp_linkedin_oauth->ppp_initialize_linkedin();
		wp_redirect( admin_url( 'admin.php?page=ppp-social-settings' ) );
		die();
	}
}
add_action( 'admin_init', 'ppp_capture_linkedin_oauth', 10 );

/**
 * Capture the disconnect request from Linkedin
 * @return void
 */
function ppp_disconnect_linkedin() {
	global $ppp_social_settings;
	$ppp_social_settings = get_option( 'ppp_social_settings' );
	if ( isset( $ppp_social_settings['linkedin'] ) ) {
		unset( $ppp_social_settings['linkedin'] );
		update_option( 'ppp_social_settings', $ppp_social_settings );
		delete_option( '_ppp_linkedin_refresh' );
	}
}
add_action( 'ppp_disconnect-linkedin', 'ppp_disconnect_linkedin', 10 );

/**
 * Add query vars for Linkedin
 * @param  array $vars Currenty Query Vars
 * @return array       Query vars array with linkedin added
 */
function ppp_li_query_vars( $vars ) {
	$vars[] = 'li_access_token';
	$vars[] = 'expires_in';

	return $vars;
}
add_filter( 'query_vars', 'ppp_li_query_vars' );

/**
 * Refreshes the Linkedin Access Token
 * @return void
 */
function ppp_li_execute_refresh() {
	if ( !ppp_linkedin_enabled() ) {
		return;
	}

	$refresh_date = (int) get_option( '_ppp_linkedin_refresh', true );

	if ( current_time( 'timestamp' ) > $refresh_date ) {
		add_action( 'admin_notices', 'ppp_linkedin_refresh_notice' );
	}
}
add_action( 'admin_init', 'ppp_li_execute_refresh' );

/**
 * Displays notice when the Linkedin Token is nearing expiration
 * @return void
 */
function ppp_linkedin_refresh_notice() {
	global $ppp_linkedin_oauth, $ppp_social_settings;

	// Look for the tokens coming back
	$ppp_linkedin_oauth->ppp_initialize_linkedin();

	$token = $ppp_social_settings['linkedin']->access_token;
	$url = $ppp_linkedin_oauth->ppp_get_linkedin_auth_url( admin_url( 'admin.php?page=ppp-social-settings' ) );
	$url = str_replace( '?ppp-social-auth', '?ppp-social-auth&ppp-refresh=true&access_token=' . $token, $url );

	$days_left = round( ( $ppp_social_settings['linkedin']->expires_on - current_time( 'timestamp' ) ) / DAY_IN_SECONDS );
	?>
	<div class="update-nag">
		<p><strong>Post Promoter Pro: </strong><?php printf( __( 'Your LinkedIn authentcation expires in within %d days. Please <a href="%s">refresh access.</a>.', 'ppp-txt' ), $days_left, $url ); ?></p>
	</div>
	<?php
}

/**
 * Define the linkedin tokens as constants
 * @param  array $social_tokens The Keys
 * @return void
 */
function ppp_set_li_token_constants( $social_tokens ) {
	if ( !empty( $social_tokens ) && property_exists( $social_tokens, 'linkedin' ) ) {
		define( 'LINKEDIN_KEY', $social_tokens->linkedin->api_key );
		define( 'LINKEDIN_SECRET', $social_tokens->linkedin->secret_key );
	}
}
add_action( 'ppp_set_social_token_constants', 'ppp_set_li_token_constants', 10, 1 );

/**
 * Share a post to Linkedin
 * @param  string $title       The Title of the Linkedin Post
 * @param  string $description The Description of the post
 * @param  string $link        The URL to the post
 * @param  mixed  $media       False for no media, url to the image if exists
 * @return array               The results array from the API
 */
function ppp_li_share( $title, $description, $link, $media ) {
	global $ppp_linkedin_oauth;
	$args = array (
		'title' => $title,
		'description' => $description,
		'submitted-url' => $link,
		'submitted-image-url' => $media
		);

	return $ppp_linkedin_oauth->ppp_linkedin_share( $args );
}

/**
 * Add the LinkedIn tab to the social media area
 * @param  array $tabs The existing tabs
 * @return array       The tabs with LinkedIn Added
 */
function ppp_li_add_admin_tab( $tabs ) {
	$tabs['li'] = array( 'name' => __( 'LinkedIn', 'ppp-txt' ), 'class' => 'icon-ppp-li' );

	return $tabs;
}
add_filter( 'ppp_admin_tabs', 'ppp_li_add_admin_tab', 10, 1 );

/**
 * Add the content box for LinkedIn in the social media settings
 * @param  array $content The existing content blocks
 * @return array          With LinkedIn
 */
function ppp_li_register_admin_social_content( $content ) {
	$content[] = 'li';

	return $content;
}
add_filter( 'ppp_admin_social_content', 'ppp_li_register_admin_social_content', 10, 1 );

/**
 * Add LinkedIn to the Meta Box Tabs
 * @param  array $tabs Existing Metabox Tabs
 * @return array       Metabox tabs with LinkedIn
 */
function ppp_li_add_meta_tab( $tabs ) {
	global $ppp_social_settings;
	if ( ! ppp_linkedin_enabled() ) {
		return $tabs;
	}

	$tabs['li'] = array( 'name' => __( 'LinkedIn', 'ppp-txt' ), 'class' => 'icon-ppp-li' );

	return $tabs;
}
add_filter( 'ppp_metabox_tabs', 'ppp_li_add_meta_tab', 10, 1 );

/**
 * Add LinkedIn to the Metabox Content
 * @param  array $content The existing metabox content
 * @return array          With LinkedIn
 */
function ppp_li_register_metabox_content( $content ) {
	global $ppp_social_settings;
	if ( ! ppp_linkedin_enabled() ) {
		return $content;
	}

	$content[] = 'li';

	return $content;
}
add_filter( 'ppp_metabox_content', 'ppp_li_register_metabox_content', 10, 1 );

/**
 * Registers the thumbnail size for LinkedIn
 * @return void
 */
function ppp_li_register_thumbnail_size() {
	add_image_size( 'ppp-li-share-image', 180, 110, true );
}
add_action( 'ppp_add_image_sizes', 'ppp_li_register_thumbnail_size' );

/**
 * Render the Metabox content for LinkedIn
 * @param  [type] $post [description]
 * @return [type]       [description]
 */
function ppp_li_add_metabox_content( $post ) {
	global $ppp_options;
	$default_text = !empty( $ppp_options['default_text'] ) ? $ppp_options['default_text'] : __( 'Social Text', 'ppp-txt' );

	$ppp_li_share_on_publish = get_post_meta( $post->ID, '_ppp_li_share_on_publish', true );
	$ppp_share_on_publish_title = get_post_meta( $post->ID, '_ppp_li_share_on_publish_title', true );
	$ppp_share_on_publish_desc = get_post_meta( $post->ID, '_ppp_li_share_on_publish_desc', true );

	?>
	<p>
	<?php $disabled = ( $post->post_status === 'publish' && time() > strtotime( $post->post_date ) ) ? true : false; ?>
	<input <?php if ( $disabled ): ?>readonly<?php endif; ?> type="checkbox" name="_ppp_li_share_on_publish" id="ppp_li_share_on_publish" value="1" <?php checked( '1', $ppp_li_share_on_publish, true ); ?> />&nbsp;
		<label for="ppp_li_share_on_publish"><?php _e( 'Share this post on LinkedIn at the time of publishing?', 'ppp-txt' ); ?></label>
		<p class="ppp_share_on_publish_text" style="display: <?php echo ( $ppp_li_share_on_publish ) ? '' : 'none'; ?>">
				<span class="left" id="ppp-li-image">
					<?php echo get_the_post_thumbnail( $post->ID, 'ppp-li-share-image', array( 'class' => 'left' ) ); ?>
				</span>
				<?php _e( 'Link Title', 'ppp-txt' ); ?>:<br />
				<input
				<?php if ( $disabled ): ?>readonly<?php endif; ?>
				onkeyup="PPPCountChar(this)"
				class="ppp-share-text"
				type="text"
				placeholder="<?php echo $default_text; ?>"
				name="_ppp_li_share_on_publish_title"
				<?php if ( isset( $ppp_share_on_publish_title ) ) {?>value="<?php echo htmlspecialchars( $ppp_share_on_publish_title ); ?>"<?php ;}?>
			/>
			<br />
			<?php _e( 'Link Description', 'ppp-txt' ); ?>:<br />
			<textarea <?php if ( $disabled ): ?>readonly<?php endif; ?> name="_ppp_li_share_on_publish_desc"><?php echo $ppp_share_on_publish_desc; ?></textarea>
			<br /><?php _e( 'Note: If set, the Featured image will be attached to this share', 'ppp-txt' ); ?>
		</p>
	</p>
	<?php
}
add_action( 'ppp_generate_metabox_content-li', 'ppp_li_add_metabox_content', 10, 1 );

/**
 * Save the items in our meta boxes
 * @param  int $post_id The Post ID being saved
 * @param  object $post    The Post Object being saved
 * @return int          The Post ID
 */
function ppp_li_save_post_meta_boxes( $post_id, $post ) {

	if ( ! ppp_should_save( $post_id, $post ) ) {
		return;
	}

	$ppp_li_share_on_publish = ( isset( $_REQUEST['_ppp_li_share_on_publish'] ) ) ? $_REQUEST['_ppp_li_share_on_publish'] : '0';
	$ppp_share_on_publish_title = ( isset( $_REQUEST['_ppp_li_share_on_publish_title'] ) ) ? $_REQUEST['_ppp_li_share_on_publish_title'] : '';
	$ppp_share_on_publish_desc = ( isset( $_REQUEST['_ppp_li_share_on_publish_desc'] ) ) ? $_REQUEST['_ppp_li_share_on_publish_desc'] : '';

	update_post_meta( $post_id, '_ppp_li_share_on_publish', $ppp_li_share_on_publish );
	update_post_meta( $post_id, '_ppp_li_share_on_publish_title', $ppp_share_on_publish_title );
	update_post_meta( $post_id, '_ppp_li_share_on_publish_desc', $ppp_share_on_publish_desc );

	return $post_id;
}
add_action( 'save_post', 'ppp_li_save_post_meta_boxes', 10, 2 ); // save the custom fields

/**
 * Share a linkedin post on Publish
 * @param  string $old_status The old post status
 * @param  string $new_status The new post status
 * @param  object $post       The Post object
 * @return void
 */
function ppp_li_share_on_publish( $old_status, $new_status, $post ) {
	global $ppp_options;
	$from_meta = get_post_meta( $post->ID, '_ppp_li_share_on_publish', true );
	$from_post = isset( $_POST['_ppp_li_share_on_publish'] );

	if ( empty( $from_meta ) && empty( $from_post ) ) {
		return;
	}

	// Determine if we're seeing the share on publish in meta or $_POST
	if ( $from_meta && !$from_post ) {
		$ppp_share_on_publish_title = get_post_meta( $post->ID, '_ppp_li_share_on_publish_title', true );
		$ppp_share_on_publish_desc = get_post_meta( $post->ID, '_ppp_li_share_on_publish_desc', true );
	} else {
		$ppp_share_on_publish_title = isset( $_POST['_ppp_li_share_on_publish_title'] ) ? $_POST['_ppp_li_share_on_publish_title'] : '';
		$ppp_share_on_publish_desc = isset( $_POST['_ppp_li_share_on_publish_desc'] ) ? $_POST['_ppp_li_share_on_publish_desc'] : false;
	}

	$thumbnail = ppp_post_has_media( $post->ID, 'li', true );

	$name = 'sharedate_0_' . $post->ID . '_li';

	$default_title = isset( $ppp_options['default_text'] ) ? $ppp_options['default_text'] : '';
	// If an override was found, use it, otherwise try the default text content
	$share_title = ( isset( $ppp_share_on_publish_title ) && !empty( $ppp_share_on_publish_title ) ) ? $ppp_share_on_publish_title : $default_title;

	// If the content is still empty, just use the post title
	$share_title = ( isset( $share_title ) && !empty( $share_title ) ) ? $share_title : get_the_title( $post->ID );

	$share_title = apply_filters( 'ppp_share_content', $share_title, array( 'post_id' => $post->ID ) );
	$share_link = ppp_generate_link( $post->ID, $name, true );

	$status['linkedin'] = ppp_li_share( $share_title, $ppp_share_on_publish_desc, $share_link, $thumbnail );

	if ( isset( $ppp_options['enable_debug'] ) && $ppp_options['enable_debug'] == '1' ) {
		update_post_meta( $post->ID, '_ppp-' . $name . '-status', $status );
	}
}
add_action( 'ppp_share_on_publish', 'ppp_li_share_on_publish', 10, 3 );
