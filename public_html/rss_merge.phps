<?php
/*
Template Name: Aggregate
*/
?>

<?php get_header(); ?>

<?php get_sidebar(); ?>

<div id="content">

	<?php
	/*
	** RSS Merge v0.2.0
	** Author: Brad Touesnard - http://brad.touesnard.com
	*/

	// include lastRSS
	include "./lastRSS.php";

	// if this script is not used as a wordpress include
	if (!function_exists('_e')) {
		function _e($str) { echo $str; }
	}

	// List of RSS2 URLs
	$rss_list = array(
			'http://brad.touesnard.com/feed/rss2/',
			'http://www.pierregrandmaison.com/?feed=rss2',
			'http://blog.seanmcg.com/wp-rss2.php',
			'http://www.notry.net/feed/rss2/',
			'http://www.lucsirois.com/wordpress/wp-rss2.php',
			'http://blog.chadlindstrom.ca/feed/rss2/'
	);

	// Create lastRSS object
	$rss = new lastRSS;

	// Set cache dir and cache time limit (5 seconds)
	// (don't forget to chmod cahce dir to 777 to allow writing)
	$rss->cache_dir = './temp';
	$rss->cache_time = 1200;

	$rss->CDATA = 'content';


	// Get all rss files
	$i = 0;
	foreach ($rss_list as $url) {
		if (!($rs[$i] = $rss->get($url))) {
			echo "Sorry: It's not possible to reach RSS file $url\n<br />";
		}
		$i++;
	}

	?>

	<div class="post">
	<div class="title" id="post-rssmerge"><span><img src="<?php bloginfo('template_url'); ?>/images/icon_post.gif" alt="" /></span> <a href="/aggregate/" rel="bookmark" title="View Details: RSS Merge v0.2.0">RSS Merge v0.2.0</a></div>

	<blockquote>
	<b>Description</b><br />
	<p>Typically, printing several RSS feeds from various blogs on a single page is 
	known as aggregation. This script not only aggregates RSS feeds however, it also 
	merges and sorts them by date using the merge sort algorithm. The result is a 
	blog of postings from several blogs.</p>

	<b>Requirements</b><br />
	<ol>
		<li>You will need to include the RSS parser class known as 
		<a href="http://lastrss.webdot.cz">lastRSS</a>.
		<a href="http://lastrss.webdot.cz/lastRSS.phps">Click here to download it 
		from their web site.</a></li>
		<li>Each RSS feed must be in RSS2 format. That is, each <code>item</code> must 
		have a <code>pubDate</code> child element.</li>
	</ol>

	<b>Recommendations</b><br />
	<p>This script was written to make use of the Wordpress stylesheet and can be 
	integrated into your current Wordpress blog. 
	<a href="http://brad.touesnard.com/archives/2005/01/10/45/">Click here for 
	detailed instructions.</a></p>

	<b>Download</b><br />
	<ul>
		<li><a href="http://brad.touesnard.com/rss_merge.phps">rss_merge.phps</a></li>
		<li><a href="http://lastrss.webdot.cz/lastRSS.phps">lastRSS.phps</a>
		(from <a href="http://lastrss.webdot.cz">lastrss.webdot.cz</a>)</li>
	</ul>

	<b>Changes</b><br />
	<p>
		v0.2.0 (2005-02-25)
		<ul>
			<li>Added support for <i>content:encoded</i> tag to display complete posts.</li>
		</ul>
	</p>

	<b>Author</b><br />
	<p>Brad Touesnard - <a href="http://brad.touesnard.com">http://brad.touesnard.com</a></p>

	<b>License</b><br />
	<p>This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License (GPL) as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.</p>

	<p>This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the <a href="http://www.gnu.org/copyleft/gpl.html">GNU General Public License</a> for more details.</p>

	<b>Demo</b><br />
	<p>Below is a merging of the RSS feeds from the following blogs:</p>
	<ul>
		<?php
		for ($i = 0; $i < count($rs); $i++) {
			printf("\t<li><a href=\"%s\">%s</a></li>", $rs[$i]['link'], $rs[$i]['title']);
		}
		?>
	</ul>
	</blockquote>
	</div>

	<?php

	$sort = array();
	for ($i = 0; $i < count($rs); $i++) {
		for ($j = 0; $j < count($rs[$i]['items']); $j++) {
			$item = $rs[$i]['items'][$j];
			$item_time = strtotime($item['pubDate']);
			$sort[$i][$j] = array($item_time,$i,$j);
		}
	}

	// merge sorted arrays
	$array1 = $sort[0];
	for ($i = 1; $i < count($sort); $i++) {
		$merged = array();
		$array2 = $sort[$i];
		
		$ptr1 = 0;
		$ptr2 = 0;
		while ($ptr1 < count($array1) && $ptr2 < count($array2)) {
			if ($array1[$ptr1][0] > $array2[$ptr2][0]) {
				$merged[] = $array1[$ptr1++];
			}
			else {
				$merged[] = $array2[$ptr2++];
			}
		}

		while ($ptr1 < count($array1)) {
			$merged[] = $array1[$ptr1++];
		}

		while ($ptr2 < count($array2)) {
			$merged[] = $array2[$ptr2++];
		}

		$array1 = $merged;
	}

	// Print posts
	$date = '0/0/0000';
	for ($i = 0; $i < count($merged); $i++) {
		$x = $merged[$i][1];
		$y = $merged[$i][2];
		$item = $rs[$x]['items'][$y];
		$item_time = strtotime($item['pubDate']);
		$item_date = date('n/j/Y', $item_time);

		if ($item_date != $date) {
			$date = $item_date;
			//echo "<h2>$date</h2>";
		}

		?>
		<div class="post">
		<div class="title"><a href="<?=$item['link']?>" rel="bookmark" title="Permanent Link: <?=$item['title']?>"><?=$item['title']?></a></div>
		<div class="date"><?php echo date('l j F Y', $item_time) ?> at <?php echo date('g:i a', $item_time) ?></div>

		<?php
		if ($item['content:encoded']) {
			$content = $item['content:encoded'];
			$content = preg_replace('/(src|href)=("|\')\\//i', '$1=$2'.$rs[$x]['link'].'/', $content);
			echo $content;
		}
		else {
			echo '<p>', $item['description'], '</p>';
		}
		?>

		<div class="comments">
			<div class="meta">
				<a href="<?=$item['comments']?>" class="comments_link">Comments</a>
				- <?php _e("Posted in"); ?> <a href="<?=$item['guid']?>"><?=$item['category']?></a> &#8212; <a href="<?=$rs[$x]['link']?>"><?=$rs[$x]['title']?></a>
			</div>
		</div>
		
		</div>
		<?php
	}
	?>

	<?php comments_template(); // Get wp-comments.php template ?>

</div>

<?php get_footer(); ?>