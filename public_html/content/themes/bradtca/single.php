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
			
			<!--
			<?php trackback_rdf(); ?>
			-->

			<div class="related">
				<h3>Related Posts</h3>
				<?php wp23_related_posts(); ?>
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
