<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<meta name="viewport" content="width=device-width" />

<title><?php wp_title( '' ); ?></title>

<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />

<meta name="google-site-verification" content="-HPbjD9Y0jlfGiwCKijuBuQ3P6hyDw1LyXyY9T6ytjg" />

<link rel="shortcut icon" href="<?php bloginfo('template_url'); ?>/images/favicon-32.png" />

<link rel='openid.server' href='https://bradt.wordpress.com/?openidserver=1' />
<link rel='openid.delegate' href='https://bradt.wordpress.com/' />

<link href='https://fonts.googleapis.com/css?family=Source+Sans+Pro:200,400,400italic,900,900italic|Lora:400,700,400italic,700italic' rel='stylesheet' type='text/css'>

<?php wp_head(); ?>
</head>

<body <?php body_class( $post->post_name ); ?>>

<div class="container">

<div class="content">

<header class="site">
	<a href="/">
		<img src="https://www.gravatar.com/avatar/e538ca4cb34839d4e5e3ccf20c37c67b?size=200" alt="" class="avatar">
		<?php if ( ! is_front_page() ) : ?><h1 class="site-title">Brad Touesnard</h1><?php endif; ?>
	</a>
</header>
