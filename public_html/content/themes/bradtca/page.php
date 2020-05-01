<?php get_header(); the_post(); ?>

<section class="page">

	<h1 class="page-title"><?php the_title(); ?></h1>

	<div class="entry-meta">
		Last Updated <time datetime="<?php bt_the_modified_datetime(); ?>"><?php the_modified_time('F j, Y') ?></time>
	</div>

	<div class="entry-content">

		<?php the_content("more..."); ?>

		<?php wp_link_pages('<p><strong>Pages:</strong> ', '</p>', 'number'); ?>

	</div>

</section>

<?php get_footer(); ?>
