<?php get_header(); ?>

<section class="post-archive">

	<header>
		<h1 class="section-title">Blog</h1>

		<?php if ( is_tag() ) : ?>
		<h2 class="section-subtitle">Posts tagged &#8220;<?php echo strtolower( single_tag_title( '', false ) ); ?>&#8221;</h2>
		<?php elseif ( is_year() ) : ?>
		<h2 class="section-subtitle">Posts published in <?php echo get_the_date( 'Y' ); ?></h2>
		<?php endif ?>
	</header>

	<?php $i = 0; if (have_posts()) : while (have_posts()) : the_post();
		?>

		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

			<header>
				<time datetime="<?php bt_the_datetime(); ?>" pubdate="pubdate"><?php the_time('Y.m.d') ?></time>
				<h1 class="entry-title"><a href="<?php the_permalink() ?>" rel="bookmark"><?php the_title(); ?></a></h1>
			</header>

		</article>

		<?php comments_template(); // Get comments.php template ?>

	<?php $i++; endwhile; else: ?>

		<h2>Not Found</h2>
		<p>Sorry, but you are looking for something that isn't here.</p>

	<?php endif; ?>
	</ul>

	<nav class="paging">
		<div class="older"><?php next_posts_link('&#9668; Older'); ?></div>
		<div class="newer"><?php previous_posts_link('Newer &#9658;'); ?></div>
	</nav>

	<section class="tag-cloud">
		<h1>Browse by Tag</h1>
		<?php wp_tag_cloud( 'number=10&smallest=16&largest=26&orderby=count&order=DESC' ) ?>
	</section>

	<section class="yearly-archive">
		<h1>Browse by Year</h1>
		<ul>
			<?php wp_get_archives( 'type=yearly&show_post_count=1' ); ?>
		</ul>
	</section>

</section>

<?php get_footer(); ?>
