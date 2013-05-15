<?php get_header(); ?>

<div id="content" class="single">

	<h1><?php echo ( 'journal_entry' == get_post_type() ) ? 'Travel Journal' : 'Blog'; ?></h1>

	<?php $i = 0; if (have_posts()) : while (have_posts()) : the_post();
		$timezone = get_the_time('O');
		$timezone = substr($timezone, 0, 3) . ':' . substr($timezone, 3);
		?>
		
		<div class="post hentry">
				
			<h2><a id="post-<?php the_ID(); ?>" href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title(); ?>" class="entry-title"><?php the_title(); ?></a></h2>
			<div class="date"><span class="published" title="<?php the_time('Y-m-d\TH:i:s'); echo $timezone; ?>"><?php the_time('F jS, Y \a\t g:ia') ?></span><span class="comments"> | <a href="<?php comments_link(); ?>"><?php comments_number('No Comments', '1&nbsp;Comment', '%&nbsp;Comments'); ?></a></span><!-- by <?php the_author() ?> --><?php edit_post_link('Edit', ' (', ')'); ?></div>

			<div class="entry-content">
				<?php the_content("more..."); ?>
			</div>

			<?php if ( has_tag( array( 'wordpress', 'web-development' ) ) ) : ?>
			<div class="wpmdb-promo">
				<h3>WP Migrate DB Pro - <em>Get 20% Off!</em></h3>
				<p>
					Way back in 2009, I released a free plugin called
					<a href="http://wordpress.org/extend/plugins/wp-migrate-db/">WP&nbsp;Migrate&nbsp;DB</a>
					which became pretty popular in recent years. So, I decided to develop a Pro version of this
					plugin which eliminates the manual work of migrating a WP database.
					It allows you to copy your db from one WP install to another with a single-click in your dashboard.
					Especially handy for syncing a local development database with a live site.
					<a href="http://deliciousbrains.com/wp-migrate-db-pro/?utm_source=bradt.ca&utm_medium=web&utm_campaign=bradtcapromo">Learn more &raquo;</a>
					<br /><strong>Get 20% off</strong> <span>&mdash; Coupon code: <a href="http://deliciousbrains.com/wp-migrate-db-pro/?utm_source=bradt.ca&utm_medium=web&utm_campaign=bradtcapromo">BRADTCA20</a>.</span>
				</p>
			</div>
			<?php endif; ?>
			
			<!--
			<?php trackback_rdf(); ?>
			-->

			<div class="related">
				<h3>Related Posts</h3>
				<?php wp23_related_posts(); ?>
			</div>

			<div class="author">
				<img src="https://www.gravatar.com/avatar/e538ca4cb34839d4e5e3ccf20c37c67b?size=200" alt="">
				<h3>About the Author</h3>
				<p>
					Brad is founder of <a href="http://deliciousbrains.com/?utm_source=bradt.ca&utm_medium=web&utm_content=homelink&utm_campaign=author-bio">Delicious Brains</a>, a 
					company building super awesome products for WordPress, including 
					<a href="http://deliciousbrains.com/wp-migrate-db-pro/?utm_source=bradt.ca&utm_medium=web&utm_content=wpmdblink&utm_campaign=author-bio">WP Migrate DB Pro</a>, 
					a huge time saving tool for WordPress developers.
				</p>
			</div>

		</div>

		<?php comments_template(); // Get comments.php template ?>

	<?php $i++; endwhile; else: ?>

		<h2 class="center">Not Found</h2>
		<p>Sorry, but you are looking for something that isn't here.</p>

	<?php endif; ?>

	<div id="next_prev_nav">

		<?php posts_nav_link('<span id="previous_nav">&lt;&lt; Previous</span>&nbsp;&nbsp;', '', '<span id="next_nav">Next &gt;&gt;</span>'); ?>
	
	</div>

</div>

<?php get_sidebar(); ?>

<?php get_footer(); ?>
