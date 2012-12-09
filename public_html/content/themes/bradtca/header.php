<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head profile="http://gmpg.org/xfn/1">
	<title><?php wp_title(''); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo('charset'); ?>" />
	<meta name="generator" content="WordPress <?php bloginfo('version'); ?>" />
	<meta name="google-site-verification" content="-HPbjD9Y0jlfGiwCKijuBuQ3P6hyDw1LyXyY9T6ytjg" />
<?php wp_get_archives('type=monthly&format=link'); ?>
	<link rel="shortcut icon" href="<?php bloginfo('template_url'); ?>/images/favicon-32.png" />
	<link rel="alternate" type="application/rss+xml" title="Blog" href="<?php bloginfo('rss2_url'); ?>" />
	<link rel="alternate" type="application/rss+xml" title="Tweets" href="<?php echo my_feed_url('microblog'); ?>" />
	<link rel="alternate" type="application/rss+xml" title="Photos" href="<?php echo my_feed_url('photos'); ?>" />
	<link rel="alternate" type="application/rss+xml" title="Travel Journal" href="<?php echo my_feed_url('travel'); ?>" />
	<?php if (is_single()) : ?>
	<link rel="alternate" type="application/rss+xml" title="Comments" href="<?php echo get_post_comments_feed_link(); ?>" />
	<?php endif; ?>
	<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
	<?php //if ( !is_naked_day(9) ) : ?>
	<link type="text/css" rel="stylesheet" href="<?php bloginfo('template_url'); ?>/style.css?2012120902" media="screen" />
	<?php //endif; ?>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
	<script type="text/javascript" src="http://balupton.github.com/jquery-syntaxhighlighter/scripts/jquery.syntaxhighlighter.min.js"></script>
	<script type="text/javascript">$.SyntaxHighlighter.init({'wrapLines':false,'stripInitialWhitespace': true});</script>
	<script src="<?php bloginfo('template_url'); ?>/js/default.js?2012120901" type="text/javascript"></script>
	<link rel='openid.server' href='http://bradt.wordpress.com/?openidserver=1' />
	<link rel='openid.delegate' href='http://bradt.wordpress.com/' />
	<link href='http://fonts.googleapis.com/css?family=Source+Sans+Pro:200,400,400italic,900,900italic|Lora:400,700,400italic,700italic|La+Belle+Aurore' rel='stylesheet' type='text/css'>
<?php wp_head(); ?>
</head>

<body<?php if (is_page()): ?> class="page-<?php echo $post->post_name; ?>"<?php endif; ?>>

<a name="top"></a>

<?php /* if ( is_naked_day(9) ) : ?>
<h2>What happened to the design?</h2>
<p>To know more about why styles are disabled on this website visit the
<a href="http://naked.dustindiaz.com" title="Web Standards Naked Day Host Website">
Annual CSS Naked Day</a> website for more information.</p>
<?php endif; */ ?>

<div id="header">
	<div class="top">
		<?php if (!is_page('main-page')) : ?>
		<h1><a href="<?php bloginfo('url'); ?>"><?php bloginfo('name'); ?></a></h1>
		<?php endif; ?>
		<ul class="nav">
			<li><a href="/about/" <?php echo is_page('about') ? ' class="active"' : ''  ?>>About</a></li>
			<li><a href="/blog/" <?php echo (is_home() || is_tax( 'post_tag' ) || ( is_single() && 'post' == get_post_type() ) ) ? ' class="active"' : ''  ?>>Blog</a></li>
			<li><a href="/portfolio/" <?php echo ( is_post_type_archive( 'portfolio_item' ) || ( is_single() && 'portfolio_item' == get_post_type() ) ) ? ' class="active"' : ''  ?>>Portfolio</a></li>
			<li><a href="/wordpress/" <?php echo is_page('wordpress') ? ' class="active"' : ''  ?>>WordPress</a></li>
			<li><a href="/photos/" <?php echo ( is_post_type_archive( 'photo_set' ) || is_tax( 'photo_collection' ) || is_tax( 'photo_tag' ) || is_page( 'photo-tags' ) || is_page( 'photo-collections' ) || is_attachment() || ( is_single() && 'photo_set' == get_post_type() )) ? ' class="active"' : ''  ?>>Photos</a></li>
			<li class="last"><a href="/contact/" <?php echo is_page('contact') ? ' class="active"' : ''  ?>>Contact</a></li>
		</ul>
	</div>

	<?php if (is_page('main-page')) : ?>
	<div class="home">
		<div class="photo"></div>
		<h1>
			<span class="fname">Brad</span> <span class="lname">Touesnard</span>
		</h1>
		<p>
			Founder of <a href="http://wpappstore.com">WP App Store</a>.
			<span>Co-founder</span> of <a href="http://www.zenutech.ca/?ref=bradt.ca">Zenutech web hosting</a>.
			Creative coder living in Halifax,&nbsp;Canada.&nbsp;<a href="/about/" class="more">more &raquo;</a>
		</p>
	</div>
	<?php endif; ?>
</div>

<?php if (!is_page('main-page')) : ?>
<div id="main">
<?php endif; ?>
