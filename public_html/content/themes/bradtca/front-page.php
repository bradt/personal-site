<?php get_header(); ?>

<section class="front-page">

	<section class="latest-post">

		<h1 class="section-title icon-default">Featured</h1>

		<?php
		global $more;

		$r = new WP_Query( array(
				'post_type' => 'post',
				'posts_per_page' => 6
			) );

		$r->the_post();

		// Stupid global that sets whether or not to show the
		// whole post or just that above <!--more-->
		$more = 0;
		?>

		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

			<header>
				<h1 class="entry-title"><a id="post-<?php the_ID(); ?>" href="<?php the_permalink() ?>"><?php the_title(); ?></a></h1>

				<div class="entry-meta">
					<time datetime="<?php bt_the_datetime(); ?>" pubdate="pubdate"><?php the_time('F j, Y') ?></time>
					<span class="meta-sep">&bullet;</span>
					<span class="comments"><a href="<?php the_permalink(); ?>#disqus_thread"><?php comments_number('No Comments', '1&nbsp;Comment', '%&nbsp;Comments'); ?></a></span>
				</div>
			</header>

			<div class="entry-content">
				<?php echo the_content( '' ); ?>
			</div>

		</article>

	</section>

	<section class="recent-posts">

		<h1 class="section-title icon-default">Recent Blog Posts</h1>

		<?php
		while ( $r->have_posts() ) :
			$r->the_post();
			?>

			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

				<header>
					<time datetime="<?php bt_the_datetime(); ?>" pubdate="pubdate" title="<?php the_time( 'F jS, Y' ) ?>"><?php the_time( 'M d' ) ?></time><h1 class="entry-title"><a href="<?php the_permalink() ?>" rel="bookmark"><?php the_title(); ?></a></h1>
				</header>

			</article>

			<?php
		endwhile;
		wp_reset_postdata();
		?>

		<p class="more"><a href="/blog/">More Posts &#9658;</a></p>
	</section>

	<?php 
	$tweets = array();
	$twitter_url = 'https://twitter.com/bradt';

	if ( class_exists( 'AKTT' ) && $account = AKTT::default_account() ) {
		$username = $account->social_acct->name();

		$args = array(
			'account' => $username,
			'include_rts' => 0,
			'include_replies' => 0,
			'count' => 5,
			'mentions' => '',
			'hashtags' => '',
		);

		$tweets = AKTT::get_tweets( $args );
	}

	if ( $tweets ) :
	?>

	<section class="tweets">
		
		<h1 class="section-title"><span class="icon-twitter"></span> Latest Tweets</h1>

		<ul>

		<?php
		foreach ( $tweets as $tweet ) :
			?>

			<li>
				<a href="<?php echo $twitter_url; ?>" class="avatar"><img src="https://www.gravatar.com/avatar/e538ca4cb34839d4e5e3ccf20c37c67b?size=100" alt=""></a>

				<div class="desc">

					<p class="name">
						<a href="<?php echo $twitter_url; ?>" class="real">Brad Touesnard</a>
						<a href="<?php echo $twitter_url; ?>" class="username">@bradt</a>
					</a>
			
					<p>
					<?php
					$content = $tweet->post_content;
					if ( isset( $tweet->tweet ) ) {
						$content = $tweet->tweet->link_entities();
					}

					echo wptexturize( $content );

					if ( isset( $tweet->tweet ) ) {
						$reply_id = $tweet->tweet->reply_id();
						if ( !empty( $reply_id ) ) {
							?>
						 	<a href="<?php echo esc_url( AKTT::status_url( $tweet->tweet->reply_screen_name(), $reply_id ) ); ?>" class="aktt_tweet_reply"><?php printf( __( 'in reply to %s', 'twitter-tools' ), esc_html( $tweet->tweet->reply_screen_name() ) ); ?></a>
							<?php
						}
						?>
					 	<a href="<?php echo esc_url( $tweet->tweet->status_url() ); ?>" class="aktt_tweet_time"><?php echo sprintf( __( '%s ago', 'twitter-tools' ), human_time_diff( strtotime( $tweet->post_date_gmt ) ) ); ?></a>
						<?php
					}
					?>
					</p>

				</div>
			</li>

			<?php
		endforeach;
		?>

		</ul>

		<p class="more"><a href="<?php echo $twitter_url; ?>">More Tweets &#9658;</a></p>

	</section>

	<?php endif; ?>

	<?php if ( function_exists( 'tla_ads' ) ) : ?>
	
	<section class="sponsors">

		<h1 class="section-title icon-default">Sponsors</h1>
		
		<?php tla_ads(); ?>

	</section>

	<?php endif; ?>

	<?php
	$r = new WP_Query( array(
		'post_type' => 'attachment',
		'post_status' => 'inherit',
		'posts_per_page' => 5,
		'meta_key' => 'date_taken',
		'orderby' => 'meta_value_num',
		'tax_query' => array( array(
			'taxonomy' => 'photo_tag',
			'field' => 'slug',
			'terms' => 'handpicked'
		) )
	) );

	if ( $r->have_posts() ) :
	?>

	<section class="photos">
		
		<h1 class="section-title icon-default">Photos</h1>
		
		<?php
		while ( $r->have_posts() ) :
			$r->the_post();

			list ( $src, $w, $h ) = wp_get_attachment_image_src( get_the_ID(), 'large' );
			$meta = wp_get_attachment_metadata( get_the_ID() );
			$date_taken = gmdate( 'M j, Y', ( $meta['image_meta']['created_timestamp'] + ( get_option( 'gmt_offset' ) * 3600 ) ) );
			?>

			<figure>
				<a href="<?php the_permalink(); ?>" style="background-image: url(<?php echo $src; ?>);">
					<?php echo wp_get_attachment_image( get_the_ID(), 'large' ); ?>
				</a>
				<figcaption>
					<h1><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>
					<p class="date"><?php echo $date_taken; ?></p>
				</figcaption>
			</figure>

			<?php
		endwhile;
		wp_reset_postdata();
		?>

		<p class="more"><a href="/photo-tag/handpicked/">More Photos &#9658;</a></p>

	</section>

	<?php endif; ?>

</section>

<?php get_footer(); ?>
