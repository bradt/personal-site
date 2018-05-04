<?php get_header(); ?>

<section class="front-page">

	<section class="latest-post">

		<h1 class="section-title icon-default">Featured</h1>

		<?php
		global $more;

		$r = new WP_Query( array(
				'post_type' => 'post',
				'posts_per_page' => 6
			) );

		$r->the_post();

		// Stupid global that sets whether or not to show the
		// whole post or just that above <!--more-->
		$more = 0;
		?>

		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

			<header>
				<h1 class="entry-title"><a id="post-<?php the_ID(); ?>" href="<?php the_permalink() ?>"><?php the_title(); ?></a></h1>

				<div class="entry-meta">
					<time datetime="<?php bt_the_datetime(); ?>" pubdate="pubdate"><?php the_time('F j, Y') ?></time>
					<span class="meta-sep">&bullet;</span>
					<span class="comments"><a href="<?php the_permalink(); ?>#disqus_thread"><?php comments_number('No Comments', '1&nbsp;Comment', '%&nbsp;Comments'); ?></a></span>
				</div>
			</header>

			<div class="entry-content">
				<?php echo the_content( '' ); ?>
			</div>

		</article>

		<p class="more"><a href="<?php the_permalink() ?>#more-<?php the_ID(); ?>">Read More &#9658;</a></p>

	</section>

	<section class="recent-posts">

		<h1 class="section-title icon-default">Recent Blog Posts</h1>

		<?php
		while ( $r->have_posts() ) :
			$r->the_post();
			?>

			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

				<header>
					<time datetime="<?php bt_the_datetime(); ?>" pubdate="pubdate" title="<?php the_time( 'F jS, Y' ) ?>"><?php the_time( 'M d' ) ?></time><h1 class="entry-title"><a href="<?php the_permalink() ?>" rel="bookmark"><?php the_title(); ?></a></h1>
				</header>

			</article>

			<?php
		endwhile;
		wp_reset_postdata();
		?>

		<p class="more"><a href="/blog/">More Posts &#9658;</a></p>
	</section>

	<?php if ( function_exists( 'tla_ads' ) ) : ?>

	<section class="sponsors">

		<h1 class="section-title icon-default">Sponsors</h1>

		<?php tla_ads(); ?>

	</section>

	<?php endif; ?>

</section>

<?php get_footer(); ?>
