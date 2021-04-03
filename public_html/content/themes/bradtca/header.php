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

<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&display=swap" rel="stylesheet">

<?php wp_head(); ?>

<script async src="https://www.googletagmanager.com/gtag/js?id=UA-315453-4"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'UA-315453-4');
</script>

</head>

<body <?php body_class( $post->post_name ); ?>>

<div class="container">

<header class="site">
	<?php if ( ! is_front_page() ) : ?>
	<a href="/" title="Go to the homepage">
	<?php endif; ?>
		<img src="https://www.gravatar.com/avatar/e538ca4cb34839d4e5e3ccf20c37c67b?size=<?php echo is_front_page() ? '400' : '100'; ?>" alt="" class="avatar">
	<?php if ( ! is_front_page() ) : ?>
	</a>
	<?php endif; ?>
</header>
