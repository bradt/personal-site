<?php
global $content_width;
if ( ! isset( $content_width ) )
	$content_width = 684;

add_theme_support( 'automatic-feed-links' );
add_theme_support( 'post-thumbnails' );

function bt_enqueue_scripts() {
	if ( WP_DEBUG ) {
		$suffix = '';
	}
	else {
		$suffix = '.min';
	}

	$path = '/assets/css/style.css';
	$version = filemtime( get_template_directory() . $path );
	wp_enqueue_style( 'bradtca', get_template_directory_uri() . $path, array(), $version );

	$path = '/assets/js/script' . $suffix . '.js';
	$version = filemtime( get_template_directory() . $path );
	//wp_enqueue_script( 'bradtca', get_template_directory_uri() . $path, array( 'jquery' ), $version, true );
}
add_action( 'wp_enqueue_scripts', 'bt_enqueue_scripts' );

add_editor_style();
