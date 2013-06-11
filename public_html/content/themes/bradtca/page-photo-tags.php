<?php /* Template Name: Photo Tags */ ?>

<?php get_header(); ?>

<section class="page photos photo-tags">

	<?php bt_photos_top(); ?>

	<?php
	wp_tag_cloud(array(
		'taxonomy' => 'photo_tag',
		'smallest' => 12,
		'largest' => 26,
		'orderby' => 'count',
		'order' => 'DESC'
	));
	?>

</section>

<?php get_footer(); ?>
