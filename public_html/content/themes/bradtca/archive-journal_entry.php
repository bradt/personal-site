<?php get_header( 'journal' ); ?>

<?php if ( current_user_can( 'export' ) ) : ?>

<form action="<?php echo admin_url( 'admin-ajax.php' ); ?>" method="post" class="new-entry">
<?php wp_nonce_field( 'record-journal-entry' ); ?>
<input type="hidden" name="action" value="record_journal_entry" />
<textarea name="entry-content" placeholder="What have you been up to?"></textarea>
<button type="submit">Record</button>
</form>

<?php endif; ?>

<section class="month">

<?php 
$prev_month = '';
$prev_day = '';
while ( have_posts() ) :
	the_post();

	$day = get_the_time( 'd F Y' );

	if ( $prev_day && $day != $prev_day ) {
		echo '</section>';
	}

	$month = get_the_time( 'F Y' );

	if ( $prev_month && $month != $prev_month ) {
		echo '</section><section class="month">';
	}

	if ( $month != $prev_month ) {
		$display_month = '<h1 class="month-year">' . get_the_time( 'F' );
		if ( date( 'Y' ) != get_the_time( 'Y' ) ) {
			$display_month .= get_the_time( ' Y' );
		}
		$display_month .= '</h1>';
		echo $display_month;
		$prev_month = $month;
	}

	$content = get_the_content();
	$content = apply_filters( 'the_content', $content );
	$content = str_replace( ']]>', ']]&gt;', $content );

	$text = wp_strip_all_tags( $content );
	$words_array = preg_split( "/[\n\r\t ]+/", $text, -1, PREG_SPLIT_NO_EMPTY );
	$word_count = count( $words_array );

	$delete_url = admin_url( 'post.php?action=delete' );
	$delete_url = add_query_arg( array(
		'post' => get_the_ID(),
		'_wp_http_referer' => wp_unslash( $_SERVER['REQUEST_URI'] )
	), $delete_url );
	$delete_url = wp_nonce_url( $delete_url, 'delete-post_' . get_the_ID() );

	if ( !$prev_day || $day != $prev_day ) {
		echo '<section class="day-entries">';
	}
	?>

	<article class="journal-entry">
	<div class="wrap">

		<?php if ( $day != $prev_day ) : ?>
		<time datetime="<?php bt_the_datetime(); ?>" pubdate="pubdate"><span class="day"><span class="dom"><?php the_time('j'); ?></span> <span class="dow"><?php the_time('l'); ?></span></span></time>
		<?php else : ?>
		<time datetime="<?php bt_the_datetime(); ?>" pubdate="pubdate" class="tod"><?php the_time( 'h:i A' ); ?></time>
		<?php endif; ?>

		<ul class="actions">
			<li><a class="permalink" href="<?php the_permalink() ?>" rel="bookmark">#</a>
			<?php if ( current_user_can( 'export' ) ) : ?>
			<li><a class="edit" target="_blank" href="<?php echo admin_url( 'post.php?post=' . get_the_ID() . '&action=edit' ); ?>">Edit</a></li>
			<li><a class="delete" href="<?php echo $delete_url; ?>">Delete</a></li>
			<?php endif; ?>
		</ul>

		<?php if ( $title = get_the_title() ) : ?>
		<h1 class="entry-title"><?php echo $title; ?></h1>
		<?php endif; ?>

		<div class="entry-content<?php if ( $word_count <= 100 ) echo ' shorty'; ?>">
			<?php echo $content; ?>
		</div>

	</div>
	</article>

	<?php
	$prev_day = $day;
endwhile; ?>

</section>

<nav class="paging">
	<div class="older"><?php next_posts_link('&#9668; Older'); ?></div>
	<div class="newer"><?php previous_posts_link('Newer &#9658;'); ?></div>
</nav>

<?php get_footer( 'journal' ); ?>
