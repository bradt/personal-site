<?php /* Template Name: Photo Tags */ ?>

<?php get_header(); ?>

<div id="content" class="page photos photo-tags">

	<?php bt_photos_top(); ?>

	<?php
	wp_tag_cloud(array(
		'taxonomy' => 'photo_tag'
	));
	?>

</div>

<?php get_footer(); ?>
