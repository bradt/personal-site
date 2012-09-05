<?php get_header(); ?>

<div id="content" class="page photos photo-sets">

	<?php bt_photos_top(); ?>
	
	<?php if (is_tax('photo_collection')) : ?>
	<h2 class="subtitle">Collection: <?php echo single_term_title( '', false ); ?></h2>
	<?php endif; ?>
	
	<ul class="photo-sets">
	
	<?php while (have_posts()) :
		the_post();
		?>

		<li class="photo-set">
			
			<a href="<?php the_permalink(); ?>">
				<?php the_post_thumbnail('thumbnail'); ?>
				<span class="title"><?php the_title(); ?></span>
			</a>

		</li>

	<?php endwhile; ?>

	</ul>

	<div id="controls">
		<div class="prev"><? previous_posts_link('&laquo; Previous'); ?></div>
		<div class="next"><? next_posts_link('Next &raquo;'); ?></div>
	</div>

</div>

<?php get_footer(); ?>
