<?php
// Forms
define('BT_FORMS', TEMPLATEPATH . '/forms');

// Plugins
define('BT_PLUGINS', TEMPLATEPATH . '/plugins');
require BT_PLUGINS . '/si-widont.php';

// Functions
define('BT_FUNCTIONS', TEMPLATEPATH . '/functions');
require BT_FUNCTIONS . '/images.php';
require BT_FUNCTIONS . '/template.php';
require BT_FUNCTIONS . '/theme-setup.php';

// Widgets
define('BT_WIDGETS', TEMPLATEPATH . '/widgets');

function my_get_attachments() {
	global $post, $attachments;

	$args = array(
		'post_type' => 'attachment',
		'numberposts' => -1,
		'post_status' => null,
		'post_parent' => $post->ID,
		'orderby' => 'menu_order',
		'order' => 'ASC'
		); 
	$attachments = get_posts($args);

	return $attachments;
}

function my_feed_url($key) {
	$feedburner = 'http://feeds2.feedburner.com/bradtca';
	$my_feeds = array(
		'blog' => $feedburner . '/posts',
		'microblog' => $feedburner . '/microblog',
		'photos' => $feedburner . '/photos',
		'travel' => $feedburner . '/travel'
		);

	if (isset($my_feeds[$key])) {
		return $my_feeds[$key];
	}
	else {
		return false;
	}
}

function is_naked_day($d) {
	$start = date('U', mktime(-12, 0, 0, 04, $d, date('Y')));
	$end = date('U', mktime(36, 0, 0, 04, $d, date('Y')));
	$z = date('Z') * -1;
	$now = time() + $z; 
	if ( $now >= $start && $now <= $end ) {
		return true;
	}
	return false;
}

function my_excerpt($maxlength = 0) {
	$excerpt = get_the_excerpt();
	$excerpt = str_replace('[...]', '...', $excerpt);
	if ($maxlength && strlen($excerpt) > $maxlength) {
		$excerpt = substr($excerpt, 0, $maxlength-3) . '...';
	}
	echo $excerpt;
}

function my_timezone() {
	$timezone = get_the_time('O');
	$timezone = substr($timezone, 0, 3) . ':' . substr($timezone, 3);
	return $timezone;
}

function my_exclude_cats() {
	return '&cat=-245,-246,-164';
}

function my_microblog_content() {
	$content = get_the_excerpt();
	$content = preg_replace('@(https?://)([^\s]+)@i', '<a href="$1$2">$1$2</a>', $content);
	echo $content;
}

$plugin_showcase_dir = realpath(ABSPATH. '/../ps/plugins/');
global $plugin_showcase_dir;

function custom_rss_title($title) {
	if (in_category('portfolio'))
		return 'Portfolio: ' . $title;
	else
		return $title;
}
add_filter('the_title_rss', 'custom_rss_title');

function bt_tiny_mce_before_init( $init ) {
    // Command separated string of extended elements
	$ext = 'iframe[*]';

    // Add to extended_valid_elements if it alreay exists
	if ( isset( $init['extended_valid_elements'] ) ) {
		$init['extended_valid_elements'] .= ',' . $ext;
	} else {
		$init['extended_valid_elements'] = $ext;
	}

	return $init;
}
add_filter('tiny_mce_before_init', 'bt_tiny_mce_before_init');

// Use custom post type for Twitter Tools plugin
add_filter( 'aktt_do_tweet_post', 'bt_aktt_do_tweet_post' );

function bt_aktt_do_tweet_post( $post_data ) {
	unset( $post_data['post_category'] );
	$post_data['post_type'] = 'tweet';
	return $post_data;
}
