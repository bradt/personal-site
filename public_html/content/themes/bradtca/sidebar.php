<ul id="sidebar" class="single">
	
	<li class="module subscribe">
		
		<?php if (is_category('travel') || in_category('travel')) : ?>
		<p class="blog"><a href="<?php bloginfo('rss2_url'); ?>" rel="alternate" type="application/rss+xml">Subscribe to Travel Journal</a></p>
		<?php else: ?>
		<p class="blog"><a href="<?php bloginfo('rss2_url'); ?>" rel="alternate" type="application/rss+xml">Subscribe to Blog</a></p>
		<?php endif; ?>
		
		<?php if (is_single()) : ?>
		<p class="comments"><a href="<?php echo get_post_comments_feed_link(); ?>" rel="alternate" type="application/rss+xml">Subscribe to Comments</a></p>
		<?php endif; ?>
		
	</li>
	
	<li class="module tags">
		<h4>Tags</h4>
		<?php
		if (is_single()) :
			the_post();
			the_tags('<ul><li>','</li><li>','</li></ul>');
		else :
			?>
			<div class="tag-cloud">
				<?php wp_tag_cloud(array(
					'orderby' => 'count',
					'order' => 'DESC'
				)); ?>
			</div>
			<?php
		endif;
		?>
	</li>
	
	<li class="module archives">
		<h4>Archives</h4>
		<ul>
			<?php wp_get_archives('type=yearly&show_post_count=1'); ?>
		</ul>
	</li>
	
</ul>

