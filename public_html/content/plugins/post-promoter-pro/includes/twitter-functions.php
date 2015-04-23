<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Return if twitter account is found
 * @return bool If the Twitter object exists
 */
function ppp_twitter_enabled() {
	global $ppp_social_settings;

	if ( isset( $ppp_social_settings['twitter'] ) && !empty( $ppp_social_settings['twitter'] ) ) {
		return true;
	}

	return false;
}

/**
 * Register Twitter as a servcie
 * @param  array $services The registered services
 * @return array           With Twitter added
 */
function ppp_tw_register_service( $services ) {
	$services[] = 'tw';

	return $services;
}
add_filter( 'ppp_register_social_service', 'ppp_tw_register_service', 10, 1 );

/**
 * The Twitter Icon
 * @param  string $string The default icon
 * @return string         The HTML for the Twitter Icon
 */
function ppp_tw_account_list_icon( $string ) {
	return '<span class="dashicons icon-ppp-tw"></span>';
}
add_filter( 'ppp_account_list_icon-tw', 'ppp_tw_account_list_icon', 10, 1 );

/**
 * The avatar for the connected Twitter Account
 * @param  string $string Default avatar string
 * @return string         The Twitter avatar
 */
function ppp_tw_account_list_avatar( $string ) {

	if ( ppp_twitter_enabled() ) {
		global $ppp_social_settings;
		$avatar_url = $ppp_social_settings['twitter']['user']->profile_image_url_https;
		$string = '<img class="ppp-social-icon" src="' . $avatar_url . '" />';
	}

	return $string;
}
add_filter( 'ppp_account_list_avatar-tw', 'ppp_tw_account_list_avatar', 10, 1 );

/**
 * The name of the connected Twitter account for the list view
 * @param  string $string The default name
 * @return string         The name from Twitter
 */
function ppp_tw_account_list_name( $string ) {

	if ( ppp_twitter_enabled() ) {
		global $ppp_social_settings;
		$string = $ppp_social_settings['twitter']['user']->name;
	}

	return $string;
}
add_filter( 'ppp_account_list_name-tw', 'ppp_tw_account_list_name', 10, 1 );

/**
 * The actions for the Twitter account list
 * @param  string $string The default actions
 * @return string         The actions buttons HTML for Twitter
 */
function ppp_tw_account_list_actions( $string ) {

	if ( ! ppp_twitter_enabled() ) {
		global $ppp_twitter_oauth, $ppp_social_settings;
		$tw_auth = $ppp_twitter_oauth->ppp_verify_twitter_credentials();
		$tw_authurl = $ppp_twitter_oauth->ppp_get_twitter_auth_url();

		$string = '<a href="' . $tw_authurl . '"><img src="' . PPP_URL . '/includes/images/sign-in-with-twitter-gray.png" /></a>';
	} else {
		$string  = '<a class="button-primary" href="' . admin_url( 'admin.php?page=ppp-social-settings&ppp_social_disconnect=true&ppp_network=twitter' ) . '" >' . __( 'Disconnect from Twitter', 'ppp-txt' ) . '</a>&nbsp;';
		$string .= '<a class="button-secondary" href="https://twitter.com/settings/applications" target="blank">' . __( 'Revoke Access via Twitter', 'ppp-txt' ) . '</a>';
	}

	return $string;
}
add_filter( 'ppp_account_list_actions-tw', 'ppp_tw_account_list_actions', 10, 1 );

/**
 * Listen for the oAuth tokens and verifiers from Twitter when in admin
 * @return void
 */
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
 * Listen for the disconnect from Twitter
 * @return void
 */
function ppp_disconnect_twitter() {
	global $ppp_social_settings;
	$ppp_social_settings = get_option( 'ppp_social_settings' );
	if ( isset( $ppp_social_settings['twitter'] ) ) {
		unset( $ppp_social_settings['twitter'] );
		update_option( 'ppp_social_settings', $ppp_social_settings );
	}
}
add_action( 'ppp_disconnect-twitter', 'ppp_disconnect_twitter', 10 );

/**
 * Given a message, sends a tweet
 * @param  string $message The Text to share as the body of the tweet
 * @return object          The Results from the Twitter API
 */
function ppp_send_tweet( $message, $post_id, $use_media = false ) {
	global $ppp_twitter_oauth;

	return apply_filters( 'ppp_twitter_tweet', $ppp_twitter_oauth->ppp_tweet( ppp_entities_and_slashes( $message ), $use_media ) );
}

/**
 * Combines the results from ppp_generate_share_content and ppp_generate_link into a single string
 * @param  int $post_id The Post ID
 * @param  string $name    The 'name' element from the Cron
 * @return string          The Full text for the social share
 */
function ppp_tw_build_share_message( $post_id, $name, $scheduled = true ) {
	$share_content = ppp_tw_generate_share_content( $post_id, $name );
	$share_link    = ppp_generate_link( $post_id, $name, $scheduled );

	return apply_filters( 'ppp_tw_build_share_message', $share_content . ' ' . $share_link );
}

/**
 * Generate the content for the shares
 * @param  int $post_id The Post ID
 * @param  string $name    The 'Name' from the cron
 * @return string          The Content to include in the social media post
 */
function ppp_tw_generate_share_content( $post_id, $name, $is_scheduled = true ) {
	global $ppp_options;
	$default_text = isset( $ppp_options['default_text'] ) ? $ppp_options['default_text'] : '';
	$ppp_tweets   = get_post_meta( $post_id, '_ppp_tweets', true );

	if ( $is_scheduled && ! empty( $ppp_tweets ) ) {
		$ppp_post_override_data = get_post_meta( $post_id, '_ppp_post_override_data', true );
		$name_array    = explode( '_', $name );
		$index         = $name_array[1];
		$share_content = $ppp_tweets[$index]['text'];
	}

	// If an override was found, use it, otherwise try the default text content
	$share_content = ( isset( $share_content ) && !empty( $share_content ) ) ? $share_content : $default_text;

	// If the content is still empty, just use the post title
	$share_content = ( isset( $share_content ) && !empty( $share_content ) ) ? $share_content : get_the_title( $post_id );

	return apply_filters( 'ppp_share_content', $share_content, array( 'post_id' => $post_id ) );
}

/**
 * Return if media is supported for this scheduled tweet
 * @param  int $post_id The Post ID
 * @param  int $day     The day of this tween
 * @return bool         Weather or not this tweet should contain a media post
 */
function ppp_tw_use_media( $post_id, $index ) {
	if ( empty( $post_id ) || empty( $index ) ) {
		return false;
	}

	$share_data = get_post_meta( $post_id, '_ppp_tweets', true );
	$use_media  = ! empty( $share_data[$index]['attachment_id'] ) || ! empty( $share_data[$index]['image'] ) ? true : false;

	return $use_media;
}

/**
 * Sets the constants for the oAuth tokens for Twitter
 * @param  array $social_tokens The tokens stored in the transient
 * @return void
 */
function ppp_set_tw_token_constants( $social_tokens ) {
	if ( !empty( $social_tokens ) && property_exists( $social_tokens, 'twitter' ) ) {
		define( 'PPP_TW_CONSUMER_KEY', $social_tokens->twitter->consumer_token );
		define( 'PPP_TW_CONSUMER_SECRET', $social_tokens->twitter->consumer_secret );
	}
}
add_action( 'ppp_set_social_token_constants', 'ppp_set_tw_token_constants', 10, 1 );

/**
 * Register Twitter for the Social Media Accounts section
 * @param  array $tabs Array of existing tabs
 * @return array       The Array of existing tabs with Twitter added
 */
function ppp_tw_add_admin_tab( $tabs ) {
	$tabs['tw'] = array( 'name' => __( 'Twitter', 'ppp-txt' ), 'class' => 'icon-ppp-tw' );

	return $tabs;
}
add_filter( 'ppp_admin_tabs', 'ppp_tw_add_admin_tab', 10, 1 );

/**
 * Register the Twitter connection area for the Social Media Accounts section
 * @param  array $content The existing content tokens
 * @return array          The content tokens with Twitter added
 */
function ppp_tw_register_admin_social_content( $content ) {
	$content[] = 'tw';

	return $content;
}
add_filter( 'ppp_admin_social_content', 'ppp_tw_register_admin_social_content', 10, 1 );

/**
 * Register the Twitter metabox tab
 * @param  array $tabs The tabs
 * @return array       The tabs with Twitter added
 */
function ppp_tw_add_meta_tab( $tabs ) {
	global $ppp_social_settings;
	if ( !isset( $ppp_social_settings['twitter'] ) ) {
		return $tabs;
	}

	$tabs['tw'] = array( 'name' => __( 'Twitter', 'ppp-txt' ), 'class' => 'icon-ppp-tw' );

	return $tabs;
}
add_filter( 'ppp_metabox_tabs', 'ppp_tw_add_meta_tab', 10, 1 );

/**
 * Register the metabox content for Twitter
 * @param  array $content The existing metabox tokens
 * @return array          The metabox tokens with Twitter added
 */
function ppp_tw_register_metabox_content( $content ) {
	global $ppp_social_settings;
	if ( !isset( $ppp_social_settings['twitter'] ) ) {
		return $content;
	}

	$content[] = 'tw';

	return $content;
}
add_filter( 'ppp_metabox_content', 'ppp_tw_register_metabox_content', 10, 1 );

/**
 * Registers the thumbnail size for Twitter
 * @return void
 */
function ppp_tw_register_thumbnail_size() {
	add_image_size( 'ppp-tw-share-image', 440, 220, true );
}
add_action( 'ppp_add_image_sizes', 'ppp_tw_register_thumbnail_size' );

/**
 * The callback that adds Twitter metabox content
 * @param  object $post The post object
 * @return void         Displays the metabox content
 */
function ppp_tw_add_metabox_content( $post ) {
	global $ppp_options;
	$default_text = !empty( $ppp_options['default_text'] ) ? $ppp_options['default_text'] : __( 'Social Text', 'ppp-txt' );

	$ppp_post_exclude = get_post_meta( $post->ID, '_ppp_post_exclude', true );

	$ppp_share_on_publish = get_post_meta( $post->ID, '_ppp_share_on_publish', true );
	$ppp_share_on_publish_text = get_post_meta( $post->ID, '_ppp_share_on_publish_text', true );
	$ppp_share_on_publish_include_image = get_post_meta( $post->ID, '_ppp_share_on_publish_include_image', true );
	?>
		<p>
			<p>
			<?php $disabled = ( $post->post_status === 'publish' && time() > strtotime( $post->post_date ) ) ? true : false; ?>
			<input <?php if ( $disabled ): ?>readonly<?php endif; ?> type="checkbox" name="_ppp_share_on_publish" id="ppp_share_on_publish" value="1" <?php checked( '1', $ppp_share_on_publish, true ); ?> />&nbsp;
				<label for="ppp_share_on_publish"><?php _e( 'Tweet this post at the time of publishing?', 'ppp-txt' ); ?></label>
				<p class="ppp_share_on_publish_text" style="display: <?php echo ( $ppp_share_on_publish ) ? '' : 'none'; ?>">
						<input
						<?php if ( $disabled ): ?>readonly<?php endif; ?>
						onkeyup="PPPCountChar(this)"
						class="ppp-share-text"
						type="text"
						placeholder="<?php echo $default_text; ?>"
						name="_ppp_share_on_publish_text"
						<?php if ( isset( $ppp_share_on_publish_text ) ) {?>value="<?php echo htmlspecialchars( $ppp_share_on_publish_text ); ?>"<?php ;}?>
					/><span class="ppp-text-length"></span>
					<br />
					<span>
						<input class="ppp-tw-featured-image-input" <?php if ( $disabled ): ?>readonly<?php endif; ?> id="ppp-share-on-publish-image" type="checkbox" name="_ppp_share_on_publish_include_image" value="1" <?php checked( '1', $ppp_share_on_publish_include_image, true ); ?>/>
						&nbsp;<label for="ppp-share-on-publish-image"><?php _e( 'Attach Featured Image In Tweet', 'ppp-txt' ); ?></label>
					</span>
				</p>
			</p>
			<div class="ppp-post-override-wrap">
				<p><h3><?php _e( 'Scheduled Tweets', 'ppp-txt' ); ?></h3></p>
				<div id="ppp-tweet-fields" class="ppp-tweet-fields">
					<input type="hidden" id="edd-variable-prices" class="edd-variable-prices-name-field" value=""/>
					<div id="ppp-tweet-fields" class="ppp-meta-table-wrap">
						<table class="widefat ppp-repeatable-table" width="100%" cellpadding="0" cellspacing="0">
							<thead>
								<tr>
									<th style="width: 100px"><?php _e( 'Date', 'ppp-txt' ); ?></th>
									<th style="width: 75px;"><?php _e( 'Time', 'ppp-txt' ); ?></th>
									<th><?php _e( 'Text', 'ppp-txt' ); ?></th>
									<th style"width: 200px;"><?php _e( 'Image', 'ppp-txt' ); ?></th>
									<th style="width: 20px;"></th>
								</tr>
							</thead>
							<tbody>
								<?php $tweets = get_post_meta( $post->ID, '_ppp_tweets', true ); ?>
								<?php if ( ! empty( $tweets ) ) : ?>

									<?php foreach ( $tweets as $key => $value ) :
										$date          = isset( $value['date'] )          ? $value['date']          : '';
										$time          = isset( $value['time'] )          ? $value['time']          : '';
										$text          = isset( $value['text'] )          ? $value['text']          : '';
										$image         = isset( $value['image'] )         ? $value['image']         : '';
										$attachment_id = isset( $value['attachment_id'] ) ? $value['attachment_id'] : '';

										$args = apply_filters( 'ppp_tweet_row_args', compact( 'date','time','text','image','attachment_id' ), $value );
										?>

										<?php ppp_render_tweet_row( $key, $args, $post->ID ); ?>
									<?php endforeach; ?>

								<?php else: ?>

									<?php ppp_render_tweet_row( 1, array( 'date' => '', 'time' => '', 'text' => '', 'image' => '', 'attachment_id' => '' ), $post->ID, 1 ); ?>

								<?php endif; ?>

								<tr>
									<td class="submit" colspan="4" style="float: none; clear:both; background:#fff;">
										<a class="button-secondary ppp-add-repeatable" style="margin: 6px 0;"><?php _e( 'Add New Tweet', 'ppp-txt' ); ?></a>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div><!--end #edd_variable_price_fields-->

				<p><?php _e( 'Do not include links in your text, this will be added automatically.', 'ppp-txt' ); ?></p>
			</div>
		</p>
	<?php
}
add_action( 'ppp_generate_metabox_content-tw', 'ppp_tw_add_metabox_content', 10, 1 );

function ppp_render_tweet_row( $key, $args = array(), $post_id ) {
	$share_time = strtotime( $args['date'] . ' ' . $args['time'] );
	$readonly   = current_time( 'timestamp' ) > $share_time ? 'readonly="readonly" ' : false;
	$no_date    = ! empty( $readonly ) ? ' hasDatepicker' : '';
	$hide       = ! empty( $readonly ) ? 'display: none;' : '';
?>
	<tr class="ppp-tweet-wrapper ppp-repeatable-row" data-key="<?php echo esc_attr( $key ); ?>">
		<td>
			<input <?php echo $readonly; ?>type="text" class="share-date-selector<?php echo $no_date; ?>" name="_ppp_tweets[<?php echo $key; ?>][date]" placeholder="mm/dd/yyyy" value="<?php echo $args['date']; ?>" />
		</td>

		<td>
			<input <?php echo $readonly; ?>type="text" class="share-time-selector" name="_ppp_tweets[<?php echo $key; ?>][time]" value="<?php echo $args['time']; ?>" />
		</td>

		<td>
			<input <?php echo $readonly; ?>class="ppp-tweet-text-repeatable" type="text" name="_ppp_tweets[<?php echo $key; ?>][text]" value="<?php echo $args['text']; ?>" />
		</td>

		<td class="ppp-repeatable-upload-wrapper" style="width: 200px">
			<div class="ppp-repeatable-upload-field-container">
				<input type="hidden" name="_ppp_tweets[<?php echo $key; ?>][attachment_id]" class="ppp-repeatable-attachment-id-field" value="<?php echo esc_attr( absint( $args['attachment_id'] ) ); ?>"/>
				<input <?php echo $readonly; ?>type="text" class="ppp-repeatable-upload-field ppp-upload-field" name="_ppp_tweets[<?php echo $key; ?>][image]" placeholder="<?php _e( 'Upload or Enter URL', 'ppp-txt' ); ?>" value="<?php echo $args['image']; ?>" />

				<span class="ppp-upload-file" style="<?php echo $hide; ?>">
					<a href="#" title="<?php _e( 'Insert File', 'ppp-txt' ) ?>" data-uploader-title="<?php _e( 'Insert File', 'ppp-txt' ); ?>" data-uploader-button-text="<?php _e( 'Insert', 'ppp-txt' ); ?>" class="ppp-upload-file-button" onclick="return false;">
						<span class="dashicons dashicons-upload"></span>
					</a>
				</span>

			</div>
		</td>

		<td>
			<a href="#" class="ppp-repeatable-row ppp-remove-repeatable" data-type="tweet" style="background: url(<?php echo admin_url('/images/xit.gif'); ?>) no-repeat;<?php echo $hide; ?>">&times;</a>
		</td>

	</tr>
<?php
}

/**
 * Save the items in our meta boxes
 * @param  int $post_id The Post ID being saved
 * @param  object $post    The Post Object being saved
 * @return int          The Post ID
 */
function ppp_tw_save_post_meta_boxes( $post_id, $post ) {

	if ( ! ppp_should_save( $post_id, $post ) ) {
		return;
	}

	$ppp_post_exclude = ( isset( $_REQUEST['_ppp_post_exclude'] ) ) ? $_REQUEST['_ppp_post_exclude'] : '0';

	$ppp_share_on_publish = ( isset( $_REQUEST['_ppp_share_on_publish'] ) ) ? $_REQUEST['_ppp_share_on_publish'] : '0';
	$ppp_share_on_publish_text = ( isset( $_REQUEST['_ppp_share_on_publish_text'] ) ) ? $_REQUEST['_ppp_share_on_publish_text'] : '';
	$ppp_share_on_publish_include_image = ( isset( $_REQUEST['_ppp_share_on_publish_include_image'] ) ) ? $_REQUEST['_ppp_share_on_publish_include_image'] : '';


	update_post_meta( $post_id, '_ppp_share_on_publish', $ppp_share_on_publish );
	update_post_meta( $post_id, '_ppp_share_on_publish_text', $ppp_share_on_publish_text );
	update_post_meta( $post_id, '_ppp_share_on_publish_include_image', $ppp_share_on_publish_include_image );

	$tweet_data = isset( $_REQUEST['_ppp_tweets'] ) ? $_REQUEST['_ppp_tweets'] : array();
	update_post_meta( $post_id, '_ppp_tweets', $tweet_data );

}
add_action( 'save_post', 'ppp_tw_save_post_meta_boxes', 10, 2 ); // save the custom fields

/**
 * Determines if the post should be shared on publish
 * @param  string $old_status The old post status
 * @param  string $new_status The new post status
 * @param  object $post       The Post Object
 * @return void               Shares the post
 */
function ppp_tw_share_on_publish( $new_status, $old_status, $post ) {
	global $ppp_options;

	$from_meta = get_post_meta( $post->ID, '_ppp_share_on_publish', true );
	$from_post = isset( $_POST['_ppp_share_on_publish'] );

	if ( empty( $from_meta ) && empty( $from_post ) ) {
		return;
	}

	// Determine if we're seeing the share on publish in meta or $_POST
	if ( $from_meta && !$from_post ) {
		$ppp_share_on_publish_text = get_post_meta( $post->ID, '_ppp_share_on_publish_text', true );
		$use_media = get_post_meta( $post->ID, '_ppp_share_on_publish_include_image', true );
	} else {
		$ppp_share_on_publish_text = isset( $_POST['_ppp_share_on_publish_text'] ) ? $_POST['_ppp_share_on_publish_text'] : '';
		$use_media = isset( $_POST['_ppp_share_on_publish_include_image'] ) ? $_POST['_ppp_share_on_publish_include_image'] : false;
	}

	$share_content = ( !empty( $ppp_share_on_publish_text ) ) ? $ppp_share_on_publish_text : ppp_tw_generate_share_content( $post->ID, null, false );
	$share_content = apply_filters( 'ppp_share_content', $share_content, array( 'post_id' => $post->ID ) );
	$name = 'sharedate_0_' . $post->ID;
	$media = ppp_post_has_media( $post->ID, 'tw', $use_media );
	$share_link = ppp_generate_link( $post->ID, $name, true );

	$status['twitter'] = ppp_send_tweet( $share_content . ' ' . $share_link, $post->ID, $media );

	if ( isset( $ppp_options['enable_debug'] ) && $ppp_options['enable_debug'] == '1' ) {
		update_post_meta( $post->ID, '_ppp-' . $name . '-status', $status );
	}
}
add_action( 'ppp_share_on_publish', 'ppp_tw_share_on_publish', 10, 3 );

/**
 * Unschedule any tweets when the post is unscheduled
 *
 * @since  2.1.2
 * @param  string $old_status The old status of the post
 * @param  string $new_status The new status of the post
 * @param  object $post       The Post Object
 * @return void
 */
function ppp_tw_unschedule_shares( $new_status, $old_status, $post ) {

	if ( ( $old_status == 'publish' || $old_status == 'future' ) && ( $new_status != 'publish' && $new_status != 'future' ) ) {
		ppp_remove_scheduled_shares( $post->ID );
	}

}
add_action( 'transition_post_status', 'ppp_tw_unschedule_shares', 10, 3 );

/**
 * Returns if the Twitter Cards are enabled
 *
 * @since  2.2
 * @return bool If the user has checked to enable Twitter cards
 */
function ppp_tw_cards_enabled() {
	global $ppp_share_settings;

	$ret = false;

	if ( ! empty( $ppp_share_settings['twitter']['cards_enabled'] ) ) {
		$ret = true;
	}

	return apply_filters( 'ppp_tw_cards_enabled', $ret );
}

/**
 * Output the Twitter Card Meta
 *
 * @since  2.2
 * @return void
 */
function ppp_tw_card_meta() {

	if ( ! is_single() || ! ppp_tw_cards_enabled() ) {
		return;
	}

	global $post, $ppp_options;

	if ( ! array_key_exists( $post->post_type, $ppp_options['post_types'] ) ) {
		return;
	}

	echo ppp_tw_get_cards_meta();
}
add_action( 'wp_head', 'ppp_tw_card_meta', 10 );

/**
 * Generates the Twitter Card Content
 *
 * @since  2.2
 * @return string The Twitter card Meta tags
 */
function ppp_tw_get_cards_meta() {

	$return = '';

	if ( ! is_single() || ! ppp_tw_cards_enabled() ) {
		return $return;
	}

	global $post, $ppp_social_settings;


	if ( empty( $post ) ) {
		return;
	}

	$elements = ppp_tw_default_meta_elements();
	foreach ( $elements as $name => $content ) {
		$return .= '<meta name="' . $name . '" content="' . $content . '" />' . "\n";
	}

	return apply_filters( 'ppp_tw_card_meta', $return );

}

/**
 * Sets an array of names and content for Twitter Card Meta
 * for easy filtering by devs
 *
 * @since  2.2
 * @return array The array of keys and values for the Twitter Meta
 */
function ppp_tw_default_meta_elements() {
	global $post, $ppp_social_settings;

	$elements = array(
		'twitter:card'        => 'summary_large_image',
		'twitter:site'        => $ppp_social_settings['twitter']['user']->screen_name,
		'twitter:title'       => esc_attr( strip_tags( $post->post_title ) ),
		'twitter:description' => esc_attr( strip_tags( ppp_tw_get_card_description() ) )
	);

	$image_url = ppp_post_has_media( $post->ID, 'tw', true );
	if ( $image_url ) {
		$elements['twitter:image:src'] = $image_url;
	}

	return apply_filters( 'ppp_tw_card_elements', $elements );
}

/**
 * Given a post, will give the post excerpt or the truncated post content to fit in a Twitter Card
 *
 * @since  2.2
 * @return string The post excerpt/description
 */
function ppp_tw_get_card_description() {
	global $post;

	if ( ! is_single() || empty( $post ) ) {
		return false;
	}

	$excerpt = $post->post_excerpt;
	$max_len = apply_filters( 'ppp_tw_cart_desc_length', 200 );

	if ( empty( $excerpt ) ) {
		$excerpt_pre = substr( $post->post_content, 0, $max_len );
		$last_space  = strrpos( $excerpt_pre, ' ' );
		$excerpt     = substr( $excerpt_pre, 0, $last_space ) . '...';
	}

	return apply_filters( 'ppp_tw_card_desc', $excerpt );
}
