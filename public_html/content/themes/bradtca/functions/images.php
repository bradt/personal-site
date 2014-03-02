<?php
function bt_get_attachments() {
	global $post, $attachments;

	$args = array(
		'post_type' => 'attachment',
		'numberposts' => -1,
		'post_status' => null,
		'post_parent' => $post->ID,
		'orderby' => 'menu_order',
		'order' => 'ASC'
	);
	$attachments = get_posts( $args );

	return $attachments;
}
