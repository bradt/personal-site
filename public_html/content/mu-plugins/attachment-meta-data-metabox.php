<?php
add_action( 'add_meta_boxes_attachment', function() {
	add_meta_box( 'attach-debug', 'Attachment Debugging', function( $post, $metabox ) {
		echo '<pre>';
		print_r( array(
			'_wp_attached_file' => get_post_meta( $post->ID, '_wp_attached_file', true ),
			'_wp_attachment_metadata' => get_post_meta( $post->ID, '_wp_attachment_metadata', true ),
			'_wp_attachment_backup_sizes' => get_post_meta( $post->ID, '_wp_attachment_backup_sizes', true ),
		) );
		echo '</pre>';
	} );
} );
