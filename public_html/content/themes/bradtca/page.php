<?php get_header(); the_post(); ?>

<section class="page">

	<h1 class="page-title"><?php the_title(); ?></h1>

	<div class="entry-content">

		<?php the_content("more..."); ?>

		<?php wp_link_pages('<p><strong>Pages:</strong> ', '</p>', 'number'); ?>

	</div>

</section>

<?php get_footer(); ?>
