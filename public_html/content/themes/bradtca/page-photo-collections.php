<?php /* Template Name: Photo Collections */ ?>

<?php get_header(); ?>

<div id="content" class="page photos photo-collections">

	<?php bt_photos_top(); ?>

	<ul>
	<?php
	$terms = get_terms('photo_collection');
	foreach ($terms as $term) {
		?>
		<li class="collection">
			<a href="<?php echo get_term_link($term); ?>">
			<span class="thumbs">
			<?php
			$r = new WP_Query(array(
				'post_type' => 'photo_set',
				'posts_per_page' => 10,
				'tax_query' => array(
					array(
						'taxonomy' => 'photo_collection',
						'field' => 'id',
						'terms' => $term->term_id
					)
				)
			));
			
			while ($r->have_posts()) :
				$r->the_post();
				the_post_thumbnail('thumbnail');
			endwhile;
			
			wp_reset_postdata();
			?>
			</span>
			<span class="title"><?php echo $term->name; ?></span>
			</a>
		</li>
		<?php
	}
	?>
	</ul>

</div>

<?php get_footer(); ?>
