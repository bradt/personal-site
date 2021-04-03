<?php get_header(); ?>

<section class="post-archive">

	<header>
		<h1 class="section-title">Search</h1>
		<h2 class="section-subtitle">Results for &#8220;<?php echo esc_html( get_search_query() ); ?>&#8221;</h2>
	</header>

	<?php $i = 0; if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>

		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

			<header>
				<h1 class="entry-title"><a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a></h1>
			</header>

			<div class="entry-summary">
				<p><?php bt_the_excerpt( 160 ); ?>
			</div>

		</article>

	<?php $i++; endwhile; else: ?>

		<h2>Not Found</h2>
		<p>Sorry, but no content matches what you've searched.</p>

	<?php endif; ?>
	</ul>

	<nav class="search-paging">
		<span class="previous"><?php previous_posts_link( '&#9668;' ); ?></span>
		<?php bt_pagination(); ?>
		<span class="next"><?php next_posts_link( '&#9658;' ); ?></span>
	</nav>

</section>

<?php get_footer(); ?>
