<?php get_header(); ?>

<section class="single-post">

	<header>
		<h1><?php echo ( 'journal_entry' == get_post_type() ) ? 'Travel Journal' : 'Blog'; ?></h1>
	</header>

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

				<?php if ( 'journal_entry' != get_post_type() ) : ?>

				<?php bt_related_posts(); ?>

				<section class="subscribe">
					<h1>
						Advanced WordPress Development<br />
						(Free Pro Tips Delivered Via Email)
					</h1>
					<p class="desc">
						Learn from my many years of 
						experience (since 2004) working with WordPress, from hosting, to developing themes and plugins.
						I'll share my best tips and techniques with you via email.
					</p>
					<form action="http://deliciousbrains.createsend.com/t/t/s/tdhrly/" method="post">
						<div class="field email">
							<input type="email" name="cm-tdhrly-tdhrly" id="cm-tdhrly-tdhrly" placeholder="Email Address" />
						</div>
						<div class="field name" style="display: none;">
							<input type="text" name="cm-name" id="post-subscribe-name" placeholder="Your Full Name" />
						</div>
						<button type="submit">Sign Up</button>
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

</section>

<?php get_footer(); ?>
