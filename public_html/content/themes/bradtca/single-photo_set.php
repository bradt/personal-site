<?php get_header(); the_post(); ?>

<div id="content" class="page photos photo-set">

	<?php bt_photos_top(); ?>
	
	<h2 class="subtitle"><?php the_title(); ?></h2>
	
	<?php if ( get_the_content() ) : ?>
	<div class="desc copy">
		<?php the_content(); ?>
	</div>
	<?php endif; ?>
	
	<div class="photo-list">
	<?php
	query_posts(array(
		'post_type' => 'attachment',
		'post_status' => 'inherit',
		'posts_per_page' => -1,
		'post_parent' => get_the_ID(),
		//'meta_key' => 'date_taken',
		//'orderby' => 'meta_value_num',
		'order' => 'ASC'
	));
	
	while (have_posts()) :
		the_post();
		echo wp_get_attachment_link(get_the_ID(), 'thumbnail', true);
	endwhile;
	?>
	</div>

</div>

<?php get_footer(); ?>
