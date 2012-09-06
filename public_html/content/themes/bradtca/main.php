<?php
/* Template Name: Home */

get_header(); 
?>

<?php
if ( $_GET['s3setup'] ) {
	set_time_limit(0);
	global $wpdb;
	$sql = "SELECT post_id, meta_value FROM wp_postmeta WHERE meta_key = '_wp_attached_file'";
	$results = $wpdb->get_results( $sql );

	foreach ( $results as $result ) {
		$info = array(
			'bucket' => 'uploads.bradt.ca',
			'key' => 'content/uploads/' . $result->meta_value
		);
		$sql = "INSERT INTO wp_postmeta VALUES ( null, %d, 'amazonS3_info', %s )";
		$sql = $wpdb->prepare( $sql, $result->post_id, serialize( $info ) );
		$wpdb->query( $sql );
	}
}
?>

<div class="homepage hp-white">

	<div class="col col-left">
		<div class="post latest-post hentry">
			<?php
			$latest_post = get_posts('numberposts=1');
			$post = $latest_post[0];
			setup_postdata($post);
			global $more; $more = 0;
			$timezone = my_timezone();
			?>
			<h3>Latest Blog Post</h3>
			<h2><a id="post-<?php the_ID(); ?>" href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title(); ?>" class="entry-title"><?php the_title(); ?></a></h2>
			<div class="entry-summary"><?php echo the_content(''); ?></div>
			<p class="read-more"><a href="<?php the_permalink() ?>">read more &raquo;</a></p>
			<p class="tags"><?php the_tags('<b>Tags:</b> ', ' &bull; '); ?></p>
			<p class="date">
				<span class="published" title="<?php the_time('Y-m-d\TH:i:s'); echo $timezone; ?>"><?php the_time('F jS, Y') ?><?php edit_post_link('Edit', ' (', ')'); ?></span>
				<span class="comments"><a href="<?php comments_link(); ?>"><?php comments_number('No Comments', '1&nbsp;Comment', '%&nbsp;Comments'); ?></a></span>
			</p>
			<!-- by <?php the_author() ?> -->
		</div>
		<div class="recent-posts">
			<h3>Recent Blog Posts</h3>
			<ul class="posts">
			<?php
			$recent_posts = get_posts('numberposts=5&offset=1' . $exclude_cats);
			foreach ($recent_posts as $post) :
				setup_postdata($post);
				$timezone = my_timezone();
				?>
				<li class="hentry">
					<a id="post-<?php the_ID(); ?>" href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title(); ?>" class="entry-title"><?php the_title(); ?></a><span class="date published" title="<?php the_time('Y-m-d\TH:i:s'); echo $timezone; ?>"><?php the_time('F jS, Y') ?></span>
				</li>
				<?php
			endforeach;
			?>
			</ul>
			<ul class="actions">
				<li class="browse"><a href="/blog/">Browse all posts &raquo;</a></li>
				<li class="rss icon feed"><a href="<?php bloginfo('rss2_url'); ?>" rel="alternate" type="application/rss+xml">Subscribe (RSS)</a></li>
			</ul>
		</div>
	</div>

	<div class="col col-right">
		<div class="latest-project">
			<h3>Featured Project</h3>
			<?php
			$latest_project = get_posts('post_type=portfolio_item&numberposts=1&meta_key=featured&meta_value=1');
			$post = $latest_project[0];
			setup_postdata($post);
			$attachs = my_get_attachments();
			if ($attachs) {
				list($src, $width, $height) = wp_get_attachment_image_src($attachs[0]->ID, 'homethumb');
			}
			?>
			<a href="<?php the_permalink(); ?>" style="background-image: url(<?php echo $src; ?>);" class="snap">
				<img src="<?php bloginfo('template_url'); ?>/images/home/latest-project.png" alt="" /></a>
			<div class="desc">
				<p class="title"><?php the_title(); ?></p>
				<p class="link"><a href="/portfolio/">More projects &raquo;</a></p>
			</div>
		</div>
		<div class="microblog">
			<h3>Tweets</h3>
			<ul class="posts">
			<?php
			$recent_posts = get_posts('post_type=tweet&numberposts=5');
			foreach ($recent_posts as $post) :
				setup_postdata($post);
				$timezone = my_timezone();
				$time = get_post_time('G', true, $post);
				?>
				<li class="hentry">
					<?php my_microblog_content(); ?>
					<span class="date published" title="<?php the_time('Y-m-d\TH:i:s'); echo $timezone; ?>"><?php echo human_time_diff($time); ?> ago</span>
					<!-- <a id="post-<?php the_ID(); ?>" href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title(); ?>">#</a> -->
				</li>
				<?php
			endforeach;
			?>
			</ul>
			<ul class="actions">
				<li class="browse"><a href="http://twitter.com/bradt">Browse all entries &raquo;</a></li>
				<li class="rss icon feed"><a href="<?php echo my_feed_url('microblog'); ?>" rel="alternate" type="application/rss+xml">Subscribe (RSS)</a></li>
				<li class="icon twitter"><a href="http://twitter.com/bradt">Follow me on Twitter</a></li>
			</ul>
		</div>
		<div class="sponsors">
			<h3>Sponsors</h3>
			<?php tla_ads(); ?>
		</div>
	</div>

</div>


<div class="homepage hp-green">

	<div class="col col-left">
		<?php
		$recent_posts = get_posts('post_type=journal_entry&numberposts=3');
		if (!empty($recent_posts)):
		?>
		<div class="travel">
			<h3>Travel Journal</h3>
			<ul>
			<?php
			foreach ($recent_posts as $post) :
				setup_postdata($post);
				$timezone = my_timezone();
				?>
				<li class="hentry">
					<h4><a id="post-<?php the_ID(); ?>" href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title(); ?>" class="entry-title"><?php the_title(); ?></a></h4>
					<span class="date published" title="<?php the_time('Y-m-d\TH:i:s'); echo $timezone; ?>"><?php the_time('F jS, Y') ?></span>
					<p class="entry-summary">
						<?php my_excerpt(100) ?>
						<a href="<?php the_permalink() ?>" class="read-more">more &raquo;</a>
					</p>
				</li>
				<?php
			endforeach;
			?>
			</ul>
			<ul class="actions">
				<li class="browse"><a href="/journal/">Browse all travel posts &raquo;</a></li>
				<li class="rss icon feed"><a href="<?php echo my_feed_url('travel'); ?>" rel="alternate" type="application/rss+xml">Subscribe (RSS)</a></li>
			</ul>
		</div>
		<?php endif; ?>
		<div class="music">
			<h3>Most Played Albums</h3>
			<h4>(In the past 3 months)</h4>
			<ol class="albums">
			<?php
			$lfm = new LastFmRecords();
			$albums = $lfm->getalbums();
			for ($i = 0; $i < 2; $i++) {
				$album = $albums[$i];
				if ($album['coverimage']['large']) {
					$coverimage = $album['coverimage']['large'];
				}
				elseif ($album['coverimage']['medium']) {
					$coverimage = $album['coverimage']['medium'];
				}
				elseif ($album['coverimage']['small']) {
					$coverimage = $album['coverimage']['small'];
				}
				?>
				<li>
					<div class="cdcover"><?php if (isset($coverimage)) : ?><img src="<?php echo $coverimage; ?>" alt="" width="152" height="152" /><?php endif; ?></div>
					<h5><?php echo $album['title']; ?></h5>
					<p>by <?php echo $album['artist']; ?></p>
				</li>
				<?php
			}
			?>
			</ol>
			<ul class="actions">
				<li class="browse icon lastfm"><a href="http://www.last.fm/user/bradtdotca/charts?rangetype=3month&subtype=albums">Check out my album charts &raquo;</a></li>
			</ul>
		</div>
		<div class="books">
			<h3>Books I'm Reading</h3>
		</div>
	</div>

	<div class="col col-right">
		<div class="photos">
			<div class="wrapper">
				<h3>Handpicked Photos</h3>
				<ul class="recent">
				<?php
				$r = new WP_Query(array(
					'post_type' => 'attachment',
					'post_status' => 'inherit',
					'posts_per_page' => 5,
					'meta_key' => 'date_taken',
					'orderby' => 'meta_value_num',
					'tax_query' => array(array(
						'taxonomy' => 'photo_tag',
						'field' => 'slug',
						'terms' => 'handpicked'
					))
				));
				while ($r->have_posts()) :
					$r->the_post();
					
					list($image_src, $w, $h) = wp_get_attachment_image_src(get_the_ID(), 'photo_home_hd');
					$meta = wp_get_attachment_metadata(get_the_ID());
					$date_taken = gmdate( 'M j, Y', ( $meta['image_meta']['created_timestamp'] + ( get_option( 'gmt_offset' ) * 3600 ) ) );
					?>
					<li>
						<a href="<?php the_permalink(); ?>" style="background-image: url(<?php echo $image_src; ?>);">
							<span class="desc">
								<span class="title"><?php the_title(); ?></span>
								<span class="date"><?php echo $date_taken; ?></span>
							</span>
						</a>
					</li>
					<?php
				endwhile;
				wp_reset_postdata();
				?>
				</ul>
				<ul class="actions">
					<li class="browse"><a href="/photo-tag/handpicked/">Browse all handpicked photos &raquo;</a></li>
					<li class="rss icon feed"><a href="/photos/feed/" rel="alternate" type="application/rss+xml">Subscribe (RSS)</a></li>
				</ul>
			</div>
		</div>
	</div>

</div>

<div id="sitewide-tags">
	<h3>Popular Topics</h3>
	<?php wp_tag_cloud('smallest=10&largest=30&orderby=count&order=DESC') ?>
</div>

<?php get_footer(); ?>
