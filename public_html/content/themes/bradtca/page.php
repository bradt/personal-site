<?php get_header(); the_post(); ?>

<div id="content" class="page">

	<h1><?php the_title(); ?></h1>

	<div class="post">

		<?php the_content("more..."); ?>
		
		<?php link_pages('<p><strong>Pages:</strong> ', '</p>', 'number'); ?>

		<?php edit_post_link('Edit'); ?>

	</div>

</div>

<?php get_footer(); ?>
