<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<meta name="viewport" content="width=device-width" />
<title><?php wp_title( '' ); ?></title>
<meta name="viewport" content="width=640">
<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
<meta name="google-site-verification" content="-HPbjD9Y0jlfGiwCKijuBuQ3P6hyDw1LyXyY9T6ytjg" />
<link rel="shortcut icon" href="<?php bloginfo('template_url'); ?>/images/favicon-32.png" />
<link type="text/css" rel="stylesheet" href="<?php bloginfo('template_url'); ?>/assets/css/style.css?2013060601" media="screen" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
<script src="<?php bloginfo('template_url'); ?>/assets/js/script.js?2013020901" type="text/javascript"></script>
<link rel='openid.server' href='http://bradt.wordpress.com/?openidserver=1' />
<link rel='openid.delegate' href='http://bradt.wordpress.com/' />
<link href='http://fonts.googleapis.com/css?family=Source+Sans+Pro:200,400,400italic,900,900italic|Lora:400,700,400italic,700italic' rel='stylesheet' type='text/css'>

<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

<div class="container">

<header class="site">
	<a href="/" class="avatar"><img src="https://www.gravatar.com/avatar/e538ca4cb34839d4e5e3ccf20c37c67b?size=200" alt=""></a>

	<div class="col-2">

		<p class="bio">
			I'm <a href="/about/">Brad Touesnard</a>, creative&nbsp;web&nbsp;developer
			and founder&nbsp;of&nbsp;<a href="http://deliciousbrains.com/?utm_source=bradt.ca&utm_medium=web&utm_content=homelink&utm_campaign=author-bio" title="A company building super awesome products for WordPress">Delicious Brains</a>.
		</p>

		<nav class="site">
			<?php bt_site_nav(); ?>
		</nav>

		<nav class="social">
			<ul>
				<li><a class="icon-twitter" href="http://twitter.com/bradt" title="Twitter"></a></li>
				<li><a class="icon-gplus" href="https://plus.google.com/103798016457548612622" title="Google+"></a></li>
				<li><a class="icon-github" href="https://github.com/bradt" title="Gihub"></a></li>
				<li><a class="icon-wordpress" href="http://profiles.wordpress.org/bradt" title="WordPress.org"></a></li>
				<li><a class="icon-email" href="/contact/" title="Email"></a></li>
			</ul>
		</nav>
	</div>
</header>

<div class="content">
