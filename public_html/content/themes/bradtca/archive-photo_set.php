<?php get_header(); ?>

<section class="page photos photo-sets">

	<?php bt_photos_top(); ?>
	
	<?php if ( is_tax( 'photo_collection' ) ) : ?>
	<h2 class="subtitle">Collection: <?php echo single_term_title( '', false ); ?></h2>
	<?php endif; ?>
	
	<ul class="photo-sets">
	
	<?php 
	global $bt_widont_off;
	$bt_widont_off = true;
	while (have_posts()) :
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

	<nav class="paging">
		<div class="older"><? next_posts_link('&#9668; Older'); ?></div>
		<div class="newer"><? previous_posts_link('Newer &#9658;'); ?></div>
	</nav>

</section>

<?php get_footer(); ?>
