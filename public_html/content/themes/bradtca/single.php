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
						Free Tips On How To Build And<br />
						Launch Your First Software Product
					</h1>
					<p class="desc">
						I used to be a full-time employee, then I went freelance,
						and now I run a successful product company. I've learned
						a lot along that path and will share failures and
						successes with you via email.
					</p>
					<form action="//bradt.us8.list-manage.com/subscribe/post?u=3ae56658d135818e5b69adcbf&amp;id=483204597a" method="post">
						<div class="field email">
							<input type="email" name="EMAIL" placeholder="Email Address" />
						</div>
						<div class="field name" style="display: none;">
							<input type="text" name="FNAME" placeholder="First Name" />
						</div>
						<div style="position: absolute; left: -5000px;"><input type="text" name="b_3ae56658d135818e5b69adcbf_483204597a" tabindex="-1" value=""></div>
						<button type="submit">Send Me Free Business Tips</button>
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
