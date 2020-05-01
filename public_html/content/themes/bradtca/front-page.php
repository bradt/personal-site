<?php get_header(); ?>

<section class="front-page">

	<section class="bio">

		<header>
			<h1 class="site-title"><?php the_title(); ?></h1>
		</header>

		<section class="entry-content">
			<?php the_content(); ?>
		</section>

	</section>

</section>

<?php get_footer(); ?>
