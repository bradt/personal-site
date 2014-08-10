<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<meta name="viewport" content="width=device-width" />

<title><?php wp_title( '' ); ?></title>

<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />

<link rel="shortcut icon" href="<?php bloginfo('template_url'); ?>/images/favicon-32.png" />

<link href='http://fonts.googleapis.com/css?family=Source+Sans+Pro:200,400,400italic,900,900italic|Lora:400,700,400italic,700italic' rel='stylesheet' type='text/css'>

<?php wp_head(); ?>
</head>

<body <?php body_class( $post->post_name . ' journal' ); ?>>

<div class="container">

<h1 class="page-title">Journal</h1>
