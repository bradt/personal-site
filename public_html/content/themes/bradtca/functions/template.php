<?php
function bt_related_posts() {
	$tags = get_the_terms( get_the_ID(), 'post_tag' );
	if ( !$tags ) return;

	$ids = array();
	foreach ( $tags as $tag ) {
		$ids[] = $tag->term_id;
	}

	$r = new WP_Query( array(
			'post_type' => array( 'post' ),
			'posts_per_page' => 3,
			'post__not_in' => array( get_the_ID() ),
			'tax_query' => array(
				array(
					'taxonomy' => 'post_tag',
					'field' => 'id',
					'operator' => 'IN',
					'terms' => $ids
				)
			)
		) );

	if ( !$r->have_posts() ) return;
?>

	<section class="related">
		<h3>Related Posts</h3>

		<ul>

		<?php
	while ( $r->have_posts() ) :
		$r->the_post();
?>

			<li id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

				<a href="<?php the_permalink(); ?>">
					<?php the_title(); ?>
				</a>

			</li>

			<?php
	endwhile;
?>

		</ul>

	</section>

	<?php
	wp_reset_postdata();
}

function bt_the_excerpt( $maxlength = 0 ) {
	$excerpt = get_the_excerpt();
	$excerpt = str_replace( '[...]', '...', $excerpt );
	if ( $maxlength && strlen( $excerpt ) > $maxlength ) {
		$excerpt = substr( $excerpt, 0, $maxlength-3 ) . '...';
	}
	echo $excerpt;
}

function bt_the_datetime() {
	$timezone = get_the_time( 'O' );
	$timezone = substr( $timezone, 0, 3 ) . ':' . substr( $timezone, 3 );

	the_time( 'Y-m-d\TH:i:s' ); echo $timezone;
}

function bt_the_modified_datetime() {
	$timezone = get_the_modified_time( 'O' );
	$timezone = substr( $timezone, 0, 3 ) . ':' . substr( $timezone, 3 );

	the_modified_time( 'Y-m-d\TH:i:s' ); echo $timezone;
}

function bt_excerpt( $maxlength = 0 ) {
	$excerpt = get_the_excerpt();
	$excerpt = str_replace( '[...]', '...', $excerpt );
	if ( $maxlength && strlen( $excerpt ) > $maxlength ) {
		$excerpt = substr( $excerpt, 0, $maxlength-3 ) . '...';
	}
	echo $excerpt;
}

function bt_pagination() {
	global $wp_rewrite, $wp_query;
	$wp_query->query_vars['paged'] > 1 ? $current = $wp_query->query_vars['paged'] : $current = 1;

	$pagination = array(
		'base' => @add_query_arg('page','%#%'),
		'format' => '',
		'total' => $wp_query->max_num_pages,
		'current' => $current,
		'type' => 'list',
		'prev_next' => false
	);

	if( $wp_rewrite->using_permalinks() ) {
		$pagination['base'] = user_trailingslashit( trailingslashit( remove_query_arg( 's', get_pagenum_link( 1 ) ) ) . 'page/%#%/', 'paged' );
	}

	if( !empty($wp_query->query_vars['s']) )
		$pagination['add_args'] = array( 's' => get_query_var( 's' ) );

	echo paginate_links( $pagination );
}
