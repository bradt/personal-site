<?php
/*
Template Name: Archives
*/
?>

<?php get_header(); ?>

<div id="content" class="archives">

	<?php $i = 0; if (have_posts()) : while (have_posts()) : the_post(); ?>

		<div class="post">

			<h1><?php the_title(); ?></h1>
								
			<div class="section by-date">					
				<h2>By Date</h2>
			
				<ul>
					<?php wp_get_archives('type=monthly&show_post_count=1'); ?>
				</ul>
			</div>
			
			<div class="section by-tag">
				<h2>By Tag</h2>
			
				<div class="tag-cloud">
					<?php wp_tag_cloud(''); ?>
				</div>
			</div>

			<div class="section feeds">
				<h2>Feeds</h2>
				
				<ul>
					<li><a href="/feed/">Blog Posts</a></li>
					<li><a href="/feed/comments/">Bog Comments</a></li>
					<li><a href="http://ma.gnolia.com/atom/lite/people/bradt">Bookmarks</a></li>
				</ul>			
			</div>

			<div class="section old-pages">
				<h2>Old Pages</h2>
				
				<ul>
					<li><a href="/portfolio/">Portfolio</a></li>
					<li><a href="/documents/">Documents</a></li>
					<li><a href="/voteforrory/">Rory Vote-O-Matic Add-on for Firefox</a></li>
				</ul>			
			</div>
		</div>

	<?php $i++; endwhile; endif; ?>

</div>

<?php get_footer(); ?>
