<?php get_header(); ?>

<section class="page photos photo-tag">

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

	<nav class="paging">
		<div class="older"><? next_posts_link('&#9668; Older'); ?></div>
		<div class="newer"><? previous_posts_link('Newer &#9658;'); ?></div>
	</nav>

</section>

<?php get_footer(); ?>

