<?php

/**
 * Register the metaboxes for Post Promoter Pro
 * @return void
 */
function ppp_register_meta_boxes() {
	global $post, $ppp_options;

	if ( !isset( $ppp_options['post_types'] ) || !is_array( $ppp_options['post_types'] ) ) {
		return;
	}

	foreach ( $ppp_options['post_types'] as $post_type => $value ) {
		add_meta_box( 'ppp_schedule_metabox', 'Post Promoter Pro', 'ppp_schedule_callback', $post_type, 'normal', 'high' );
	}
}
add_action( 'add_meta_boxes', 'ppp_register_meta_boxes', 12 );

/**
 * Display the Metabox for Post Promoter Pro
 * @return void
 */
function ppp_schedule_callback() {
	global $post, $ppp_options;
	$default_text = !empty( $ppp_options['default_text'] ) ? $ppp_options['default_text'] : __( 'Social Text', 'ppp-txt' );

	$ppp_post_exclude = get_post_meta( $post->ID, '_ppp_post_exclude', true );

	$ppp_share_on_publish = get_post_meta( $post->ID, '_ppp_share_on_publish', true );
	$ppp_share_on_publish_text = get_post_meta( $post->ID, '_ppp_share_on_publish_text', true );

	$ppp_post_override = get_post_meta( $post->ID, '_ppp_post_override', true );
	$ppp_post_override_data = get_post_meta( $post->ID, '_ppp_post_override_data', true );

	$exclude_style = ( !empty( $ppp_post_exclude ) ) ? 'display: none;' : '';
	$override_style = ( empty( $ppp_post_override ) ) ? 'display: none;' : '';
	?>
	<p>
	<?php $disabled = ( $post->post_status === 'publish' && time() > strtotime( $post->post_date ) ) ? true : false; ?>
	<input <?php if ( $disabled ): ?>disabled<?php endif; ?> type="checkbox" name="_ppp_share_on_publish" id="ppp_share_on_publish" value="1" <?php checked( '1', $ppp_share_on_publish, true ); ?> />&nbsp;
		<label for="ppp_share_on_publish"><?php _e( 'Share this post at the time of publishing?', 'ppp-txt' ); ?></label>
		<p id="ppp_share_on_publish_text" style="display: <?php echo ( $ppp_share_on_publish ) ? '' : 'none'; ?>">
				<input
				<?php if ( $disabled ): ?>disabled readonly<?php endif; ?>
				onkeyup="PPPCountChar(this)"
				class="ppp-share-text"
				type="text"
				placeholder="<?php echo $default_text; ?>"
				name="_ppp_share_on_publish_text"
				<?php if ( isset( $ppp_share_on_publish_text ) ) {?>value="<?php echo htmlspecialchars( $ppp_share_on_publish_text ); ?>"<?php ;}?>
			/><span class="ppp-text-length"></span>
		</p>
	</p>
	<input type="checkbox" name="_ppp_post_exclude" id="_ppp_post_exclude" value="1" <?php checked( '1', $ppp_post_exclude, true ); ?> />&nbsp;
		<label for="_ppp_post_exclude"><?php _e( 'Do not schedule social media promotion for this post.', 'ppp-txt' ); ?></label>
	<br />
	<div style="<?php echo $exclude_style; ?>" id="ppp-post-override-wrap">
		<input type="checkbox" name="_ppp_post_override" id="_ppp_post_override" value="1" <?php checked( '1', $ppp_post_override, true ); ?> />&nbsp;
		<label for="_ppp_post_override"><?php _e( 'Override Default Text and Times', 'ppp-txt' ); ?></label>
		<div class="post-override-matrix" style="<?php echo $override_style; ?>">
			<?php
			$day = 1;
			while( $day <= ppp_share_days_count() ) {
				$enabled = isset( $ppp_post_override_data['day' . $day]['enabled'] ) ? '1' : false;
				$readonly = time() > strtotime( $post->post_date . ' +' . $day . ' day' ) ? true : false;
				if ( $post->post_status !== 'publish' && $post->post_status != 'future' ) {
					$readonly = false;
				}
				?>
				<p>
				<label for="day<?php echo $day; ?>"><?php printf( __( 'Day %s', 'ppp-txt' ), $day ); ?></label>&nbsp;
				<input <?php if ( $readonly ): ?>disabled<?php endif; ?> type="checkbox" class="ppp-share-enable-day" value="1" name="_ppp_post_override_data[day<?php echo $day; ?>][enabled]" <?php checked( '1', $enabled, true ); ?>/>&nbsp;
				<input <?php if ( !$enabled ): ?>disabled<?php endif; ?>
					 <?php if ( !$enabled || $readonly ): ?>readonly<?php endif; ?>
					id="day<?php echo $day; ?>"
					type="text"
					placeholder="<?php _e( 'Time', 'ppp-txt' ); ?>"
					name="_ppp_post_override_data[day<?php echo $day; ?>][time]"
					class="share-time-selector"
					value="<?php echo ( isset( $ppp_post_override_data['day' . $day]['time'] ) ) ? $ppp_post_override_data['day' . $day]['time'] : ppp_get_day_default_time( $day ); ?>"
					size="8"
				/>
				<input <?php if ( !$enabled ): ?>disabled<?php endif; ?>
					 <?php if ( !$enabled || $readonly ): ?>readonly<?php endif; ?>
					onkeyup="PPPCountChar(this)"
					class="ppp-share-text"
					type="text"
					placeholder="<?php echo $default_text; ?>"
					id="day<?php echo $day; ?>"
					name="_ppp_post_override_data[day<?php echo $day; ?>][text]"
					<?php if ( isset( $ppp_post_override_data['day' . $day]['text'] ) ) {?>value="<?php echo htmlspecialchars( $ppp_post_override_data['day' . $day]['text'] ); ?>"<?php ;}?>
				/>
				<span class="ppp-text-length"></span>
				</p>
				<?php
				$day++;
			}
			?>
		</div>
		<p><?php _e( 'Do not include links in your text, this will be added automatically.', 'ppp-txt' ); ?></p>
	</div>
	<?php
}

/**
 * Save the items in our meta boxes
 * @param  int $post_id The Post ID being saved
 * @param  object $post    The Post Object being saved
 * @return int          The Post ID
 */
function ppp_save_post_meta_boxes( $post_id, $post ) {
	global $ppp_options;

	if ( !isset( $ppp_options['post_types'] ) || !is_array( $ppp_options['post_types'] ) || !array_key_exists( $post->post_type, $ppp_options['post_types'] ) ) {
		return;
	}

	$ppp_post_exclude = ( isset( $_REQUEST['_ppp_post_exclude'] ) ) ? $_REQUEST['_ppp_post_exclude'] : '0';

	$ppp_share_on_publish = ( isset( $_REQUEST['_ppp_share_on_publish'] ) ) ? $_REQUEST['_ppp_share_on_publish'] : '0';
	$ppp_share_on_publish_text = ( isset( $_REQUEST['_ppp_share_on_publish_text'] ) ) ? $_REQUEST['_ppp_share_on_publish_text'] : '';

	$ppp_post_override = ( isset( $_REQUEST['_ppp_post_override'] ) ) ? $_REQUEST['_ppp_post_override'] : '0';
	$ppp_post_override_data = isset( $_REQUEST['_ppp_post_override_data'] ) ? $_REQUEST['_ppp_post_override_data'] : array();

	update_post_meta( $post->ID, '_ppp_post_exclude', $ppp_post_exclude );

	update_post_meta( $post->ID, '_ppp_share_on_publish', $ppp_share_on_publish );
	update_post_meta( $post->ID, '_ppp_share_on_publish_text', $ppp_share_on_publish_text );

	update_post_meta( $post->ID, '_ppp_post_override', $ppp_post_override );

	// Fixes a bug when all items are unchecked from being checked, removed if statement
	update_post_meta( $post->ID, '_ppp_post_override_data', $ppp_post_override_data );

	return $post->ID;
}
add_action( 'save_post', 'ppp_save_post_meta_boxes', 10, 2 ); // save the custom fields
