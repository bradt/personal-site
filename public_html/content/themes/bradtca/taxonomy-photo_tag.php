<?php get_header(); ?>

<div id="content" class="page photos photo-tag">

	<?php bt_photos_top(); ?>

	<h2 class="subtitle">Tag: <?php echo single_term_title( '', false ); ?></h2>
	
	<div class="photo-list">
	<?php
	while (have_posts()) :
		the_post();
		echo wp_get_attachment_link(get_the_ID(), 'thumbnail', true);
	endwhile;
	?>
	</div>

	<div id="controls">
		<div class="prev"><? previous_posts_link('&laquo; Previous'); ?></div>
		<div class="next"><? next_posts_link('Next &raquo;'); ?></div>
	</div>

</div>

<?php get_footer(); ?>

