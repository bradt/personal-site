<?php get_header( 'blog' ); ?>

<section class="single-post">

	<header>
		<h1><?php echo ( 'journal_entry' == get_post_type() ) ? 'Travel Journal' : 'Blog'; ?></h1>
	</header>

	<?php $i = 0; if (have_posts()) : while (have_posts()) : the_post();
		$timezone = get_the_time('O');
		$timezone = substr($timezone, 0, 3) . ':' . substr($timezone, 3);
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

				<?php if ( 'journal_entry' != get_post_type() ) : ?>

				<?php bt_related_posts(); ?>

				<section class="subscribe">
					<h1>Get my blog posts in your inbox!</h1>
					<form action="http://bradt.createsend.com/t/r/s/jkfdth/" method="post">				
						<div class="field">
							<label for="post-subscribe-name">Your Name</label>
							<input type="text" name="cm-name" id="post-subscribe-name" placeholder="John Doe" />
						</div>
						<div class="field email">
							<label for="post-subscribe-email">Your Email</label>
							<input type="email" name="cm-jkfdth-jkfdth" id="jkfdth-jkfdth" placeholder="john.doe@gmail.com" />
						</div>
						<button type="submit">Subscribe</button>
					</form>

					<p class="rss">Prefer RSS? <a href="<?php bloginfo('rss2_url'); ?>" rel="alternate" type="application/rss+xml">Subscribe to my news feed</a></p>
				</section>

				<?php endif; ?>

			</section>

		</article>
				
		<!--
		<?php trackback_rdf(); ?>
		-->

		<?php comments_template(); // Get comments.php template ?>

	<?php $i++; endwhile; else: ?>

		<h2 class="center">Not Found</h2>
		<p>Sorry, but you are looking for something that isn't here.</p>

	<?php endif; ?>

	<div id="next_prev_nav">

		<?php posts_nav_link('<span id="previous_nav">&lt;&lt; Previous</span>&nbsp;&nbsp;', '', '<span id="next_nav">Next &gt;&gt;</span>'); ?>
	
	</div>

</div>

<?php get_footer( 'blog' ); ?>
