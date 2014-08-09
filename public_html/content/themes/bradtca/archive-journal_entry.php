<?php get_header( 'journal' ); ?>

<?php if ( current_user_can( 'export' ) ) : ?>

<form action="<?php echo admin_url( 'admin-ajax.php' ); ?>" method="post" class="new-entry">
<?php wp_nonce_field( 'record-journal-entry' ); ?>
<input type="hidden" name="action" value="record_journal_entry" />
<textarea name="entry-content" placeholder="What have you been up to?"></textarea>
<button type="submit">Record</button>
</form>

<?php endif; ?>

<section class="day-entries">

<?php 
$prev_month = '';
$prev_day = '';
while ( have_posts() ) :
	the_post();

	$day = get_the_time( 'd F Y' );

	$month = get_the_time( 'F Y' );
	if ( $month != $prev_month ) {
		$display_month = '<span class="month-year">' . get_the_time( 'F' );
		if ( date( 'Y' ) != get_the_time( 'Y' ) ) {
			$display_month .= get_the_time( ' Y' );
		}
		$prev_month = $month;
	}
	else {
		$display_month = '';
	}

	$content = get_the_content();
	$content = apply_filters( 'the_content', $content );
	$content = str_replace( ']]>', ']]&gt;', $content );

	$text = wp_strip_all_tags( $content );
	$words_array = preg_split( "/[\n\r\t ]+/", $text, -1, PREG_SPLIT_NO_EMPTY );
	$word_count = count( $words_array );
	?>

	<?php if ( $prev_day && $day != $prev_day ) : ?>
	</section><section class="day-entries">
	<?php endif; ?>

	<article class="journal-entry">

		<?php if ( $day != $prev_day ) : ?>
		<time datetime="<?php bt_the_datetime(); ?>" pubdate="pubdate"><span class="day"><span class="dom"><?php the_time('j'); ?></span> <span class="dow"><?php the_time('l'); ?></span></span> <?php echo $display_month; ?></time>
		<?php else : ?>
		<time datetime="<?php bt_the_datetime(); ?>" pubdate="pubdate" class="tod"><?php the_time( 'h:i A' ); ?></time>
		<?php endif; ?>

		<?php if ( $title = get_the_title() ) : ?>
		<h1 class="entry-title"><a href="<?php the_permalink() ?>" rel="bookmark"><?php echo $title; ?></a></h1>
		<?php endif; ?>

		<div class="entry-content<?php if ( $word_count <= 100 ) echo ' shorty'; ?>">
			<?php echo $content; ?>
			<a class="edit" target="_blank" href="<?php echo admin_url( 'post.php?post=' . get_the_ID() . '&action=edit' ); ?>">Edit</a>
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
