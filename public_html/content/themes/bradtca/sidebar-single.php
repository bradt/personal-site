<ul id="sidebar" class="single">
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
	<li class="module subscribe">
		<p class="blog"><a href="<?php bloginfo('rss2_url'); ?>" rel="alternate" type="application/rss+xml">Subscribe to Blog</a></p>
		<p class="comments"><a href="<?php echo get_post_comments_feed_link(); ?>" rel="alternate" type="application/rss+xml">Subscribe to Comments</a></p>
	</li>

	<li class="module tags">
		<h4>Tags</h4>
		<?php the_tags('<ul><li>','</li><li>','</li></ul>'); ?>
	</li>
	
	<?php
endwhile; endif;
?>

