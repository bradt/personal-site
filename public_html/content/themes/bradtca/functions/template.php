<?php
function bt_photos_top() {
    ?>
	<h1 class="page-title">Photos</h1>
	
	<nav class="photo-nav">
		<a href="/photos/"<?php echo is_post_type_archive('photo_set') ? 'class="current"' : ''; ?>>Sets</a>
		<span class="meta-sep">&bullet;</span>
		<a href="/photo-collections/"<?php echo (is_page('photo-collections') || is_tax('photo_collection')) ? 'class="current"' : ''; ?>>Collections</a>
		<span class="meta-sep">&bullet;</span>
		<a href="/photo-tags/"<?php echo (is_page('photo-tags') || is_tax('photo_tag')) ? 'class="current"' : ''; ?>>Tags</a>
	</nav>
    <?php    
}

function bt_related_posts() {
	$tags = get_the_terms( get_the_ID(), 'post_tag' );
	if (!$tags) return;
	
	$ids = array();
	foreach ($tags as $tag) {
		$ids[] = $tag->term_id;
	}

	$r = new WP_Query(array(
		'post_type' => array('post'),
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
	));
	
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

function bt_site_nav() {
	?>
	<ul>
		<li><a href="/about/" <?php echo is_page('about') ? ' class="active"' : ''  ?>>About</a></li>
		<li><a href="/blog/" <?php echo ( is_home() || is_tax( 'post_tag' ) || ( is_single() && 'post' == get_post_type() ) ) ? ' class="active"' : ''  ?>>Blog</a></li>
		<li><a href="/portfolio/" <?php echo ( is_post_type_archive( 'portfolio_item' ) || ( is_single() && 'portfolio_item' == get_post_type() ) ) ? ' class="active"' : ''  ?>>Portfolio</a></li>
		<li class="last"><a href="/contact/" <?php echo is_page('contact') ? ' class="active"' : ''  ?>>Contact</a></li>
	</ul>
	<?php		
}


function bt_the_excerpt($maxlength = 0) {
	$excerpt = get_the_excerpt();
	$excerpt = str_replace('[...]', '...', $excerpt);
	if ($maxlength && strlen($excerpt) > $maxlength) {
		$excerpt = substr($excerpt, 0, $maxlength-3) . '...';
	}
	echo $excerpt;
}

function bt_the_datetime() {
	$timezone = get_the_time( 'O' );
	$timezone = substr( $timezone, 0, 3 ) . ':' . substr( $timezone, 3 );
	
	the_time('Y-m-d\TH:i:s'); echo $timezone;
}

function my_excerpt($maxlength = 0) {
	$excerpt = get_the_excerpt();
	$excerpt = str_replace('[...]', '...', $excerpt);
	if ($maxlength && strlen($excerpt) > $maxlength) {
		$excerpt = substr($excerpt, 0, $maxlength-3) . '...';
	}
	echo $excerpt;
}
