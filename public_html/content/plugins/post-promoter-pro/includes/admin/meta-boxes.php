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
		add_meta_box( 'ppp_schedule_metabox', 'Post Promoter Pro', 'ppp_schedule_callback', $post_type, 'normal', 'low' );
	}
}
add_action( 'add_meta_boxes', 'ppp_register_meta_boxes', 12 );

/**
 * Display the Metabox for Post Promoter Pro
 * @return void
 */
function ppp_schedule_callback() {
	global $post, $ppp_options;

	$ppp_post_exclude = get_post_meta( $post->ID, '_ppp_post_exclude', true );
	$ppp_post_override = get_post_meta( $post->ID, '_ppp_post_override', true );
	$ppp_post_override_data = get_post_meta( $post->ID, '_ppp_post_override_data', true );

	$exclude_style = ( !empty( $ppp_post_exclude ) ) ? 'display: none;' : '';
	$override_style = ( empty( $ppp_post_override ) ) ? 'display: none;' : '';
	?>
	<input type="checkbox" name="_ppp_post_exclude" id="_ppp_post_exclude" value="1" <?php checked( '1', $ppp_post_exclude, true ); ?> />&nbsp;
		<label for="_ppp_post_exclude"><?php _e( 'Do not schedule social media promotion for this post.', 'ppp-txt' ); ?></label>
	<br />
	<div style="<?php echo $exclude_style; ?>" id="ppp-post-override-wrap">
		<input type="checkbox" name="_ppp_post_override" id="_ppp_post_override" value="1" <?php checked( '1', $ppp_post_override, true ); ?> />&nbsp;
		<label for="_ppp_post_override"><?php _e( 'Override Default Text and Times', 'ppp-txt' ); ?></label>
		<div class="post-override-matrix" style="<?php echo $override_style; ?>">
			<?php
			$day = 1;
			while( $day <= 6 ) {
				$enabled = isset( $ppp_post_override_data['day' . $day]['enabled'] ) ? '1' : false;
				$readonly = time() > strtotime( $post->post_date . ' +' . $day . ' day' ) ? true : false;
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
					value="<?php echo ( isset( $ppp_post_override_data['day' . $day]['time'] ) ) ? $ppp_post_override_data['day' . $day]['time'] : $ppp_options['times']['day' . $day]; ?>"
					size="8"
				/>
				<input <?php if ( !$enabled ): ?>disabled<?php endif; ?>
					 <?php if ( !$enabled || $readonly ): ?>readonly<?php endif; ?>
					onkeyup="PPPCountChar(this)"
					class="ppp-share-text"
					type="text"
					placeholder="<?php _e( 'Social Text', 'ppp-txt' ); ?>"
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
			<p><?php _e( 'Do not include links in your text, this will be added automatically.', 'ppp-txt' ); ?></p>
		</div>
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
	$ppp_post_override = ( isset( $_REQUEST['_ppp_post_override'] ) ) ? $_REQUEST['_ppp_post_override'] : '0';
	$ppp_post_override_data = isset( $_REQUEST['_ppp_post_override_data'] ) ? $_REQUEST['_ppp_post_override_data'] : array();

	update_post_meta( $post->ID, '_ppp_post_exclude', $ppp_post_exclude );
	update_post_meta( $post->ID, '_ppp_post_override', $ppp_post_override );
	if ( !empty( $ppp_post_override_data ) ) {
		update_post_meta( $post->ID, '_ppp_post_override_data', $ppp_post_override_data );
	}

	return $post->ID;
}
add_action( 'save_post', 'ppp_save_post_meta_boxes', 1, 2 ); // save the custom fields
