<?php

require_once(realpath('../../../wp-blog-header.php'));

error_reporting(0);

header('Content-type: image/png');
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

if(isset($_SERVER['QUERY_STRING']{1}) && is_numeric($_SERVER['QUERY_STRING'])) {
	$ID = (int) abs(intval($_SERVER['QUERY_STRING']));
	$num = (int) $wpdb->get_var("SELECT comment_count FROM $wpdb->posts WHERE `ID`='$ID' AND `post_status`='publish' AND `post_type`='post' LIMIT 1");
	$text = ($num > 0) ? sprintf($feed_comments_number->options['num_comments_format'], $num) : $feed_comments_number->options['no_comments_format'];
	$feed_comments_number->fcn_draw_image($text);
}

elseif(isset($_GET['sample']) && is_numeric($_GET['sample'])) {
	$num = (int) $_GET['sample'];
	$text = ($num > 0) ? sprintf($feed_comments_number->options['num_comments_format'], $num) : $feed_comments_number->options['no_comments_format'];
	$feed_comments_number->fcn_draw_image($text);
}

?>