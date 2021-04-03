<?php get_header(); ?>

<section class="single-post">

	<?php
	$i = 0;
	if (have_posts()) : while (have_posts()) : the_post();
		?>

		<article <?php post_class(); ?>>

			<header>
				<h1 class="entry-title"><a id="post-<?php the_ID(); ?>" href="<?php the_permalink() ?>"><?php the_title(); ?></a></h1>

				<div class="entry-meta">
					<time datetime="<?php bt_the_datetime(); ?>" pubdate="pubdate"><?php the_time('F j, Y') ?></time>
					<span class="meta-sep">&bullet;</span>
					<span class="comments"><a href="<?php the_permalink(); ?>#disqus_thread"><?php comments_number('No Comments', '1&nbsp;Comment', '%&nbsp;Comments'); ?></a></span>
				</div>
			</header>

			<section class="entry-content">
				<?php the_content(); ?>

				<?php bt_related_posts(); ?>

			</section>

		</article>

		<!--
		<?php trackback_rdf(); ?>
		-->

		<?php comments_template(); // Get comments.php template ?>

		<?php
		$args = array(
			'type' => 'pings',
			'status' => 'approve',
			'post_id' => get_the_ID()
		);

		$pings = get_comments( $args);

		if ( $pings ) :
			?>

			<section class="pings">

				<h1>Comments Elsewhere</h1>

				<ul>

				<?php
				foreach ( $pings as $ping ) {
					$parts = parse_url( $ping->comment_author_url );
					printf('<li><a href="%s"><span class="host">%s</span><span class="post-title">%s</span></a></li>', esc_attr( $ping->comment_author_url ), esc_html( $parts['host'] ), esc_html( $ping->comment_author ) );
				}
				?>

				</ul>

			</section>

			<?php
		endif;
		?>

	<?php $i++; endwhile; else: ?>

		<h2 class="center">Not Found</h2>
		<p>Sorry, but you are looking for something that isn't here.</p>

	<?php endif; ?>

</section>

<?php get_footer(); ?>
