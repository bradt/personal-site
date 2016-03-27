<?php
global $content_width;
if ( ! isset( $content_width ) )
	$content_width = 684;

add_theme_support( 'automatic-feed-links' );
add_theme_support( 'post-thumbnails' );

function bt_theme_setup() {
	$labels = array(
		'name' => _x( 'Portfolio', 'bradt.ca' ),
		'singular_name' => _x( 'Portfolio Item', 'bradt.ca' ),
		'add_new' => _x( 'Add New', 'bradt.ca' ),
		'add_new_item' => __( 'Add New Portfolio Item' ),
		'edit_item' => __( 'Edit Portfolio Item' ),
		'new_item' => __( 'New Portfolio Item' ),
		'view_item' => __( 'View Portfolio Item' ),
		'search_items' => __( 'Search Portfolio Item' ),
		'not_found' =>  __( 'No portfolio items found' ),
		'not_found_in_trash' => __( 'No portfolio items found in Trash' ),
		'parent_item_colon' => '',
		'menu_name' => 'Portfolio'
	);
	register_post_type( 'portfolio_item', array(
			'labels' => $labels,
			'public' => true,
			'has_archive' => 'portfolio',
			'rewrite' => array(
				'slug' => 'portfolio',
				'with_front' => false
			),
			'supports' => array( 'title', 'editor', 'author', 'custom-fields', 'revisions', 'thumbnail' )
		) );

	$labels = array(
		'name' => _x( 'Tags', 'taxonomy general name' ),
		'singular_name' => _x( 'Tag', 'taxonomy singular name' ),
		'search_items' =>  __( 'Search Tags' ),
		'popular_items' => __( 'Popular Tags' ),
		'all_items' => __( 'All Tags' ),
		'parent_item' => null,
		'parent_item_colon' => null,
		'edit_item' => __( 'Edit Tag' ),
		'update_item' => __( 'Update Tag' ),
		'add_new_item' => __( 'Add tags to all set photos' ),
		'new_item_name' => __( 'New Tag Name' ),
		'separate_items_with_commas' => __( 'Separate tags with commas' ),
		'add_or_remove_items' => __( 'Add or remove tags' ),
		'choose_from_most_used' => __( 'Choose from the most used tags' ),
		'menu_name' => __( 'Tags' ),
	);
	register_taxonomy( 'portfolio_tag', array( 'portfolio_item' ), array(
			'labels' => $labels,
			'public' => false,
			'show_ui' => true
		) );

	$labels = array(
		'name' => _x( 'Journal', 'bradt.ca' ),
		'singular_name' => _x( 'Journal Entry', 'bradt.ca' ),
		'add_new' => _x( 'Add New', 'bradt.ca' ),
		'add_new_item' => __( 'Add New Journal Entry' ),
		'edit_item' => __( 'Edit Journal Entry' ),
		'new_item' => __( 'New Journal Entry' ),
		'view_item' => __( 'View Journal Entry' ),
		'search_items' => __( 'Search Journal Entry' ),
		'not_found' =>  __( 'No journal entries found' ),
		'not_found_in_trash' => __( 'No journal entries found in Trash' ),
		'parent_item_colon' => '',
		'menu_name' => 'Journal'
	);
	register_post_type( 'journal_entry', array(
			'labels' => $labels,
			'public' => true,
			'has_archive' => 'journal',
			'taxonomies' => array( 'post_tag' ),
			'rewrite' => array(
				'slug' => 'journal',
				'with_front' => false
			),
			'supports' => array( 'title', 'editor', 'author', 'custom-fields', 'revisions', 'thumbnail' )
		) );
}
add_action( 'init', 'bt_theme_setup' );

function bt_enqueue_scripts() {
	if ( WP_DEBUG ) {
		$suffix = '';
	}
	else {
		$suffix = '.min';
	}

	if ( 'journal_entry' == get_post_type() ) {
		wp_enqueue_style( 'bradtca-journal', get_template_directory_uri() . '/assets/css/journal.css', array(), '20140514' );
		wp_enqueue_script( 'bradtca-journal', get_template_directory_uri() . '/assets/js/journal' . $suffix . '.js', array( 'jquery' ), '20140514', true );
		return;
	}

	$path = '/assets/css/style.css';
	$version = filemtime( get_template_directory() . $path );
	wp_enqueue_style( 'bradtca', get_template_directory_uri() . $path, array(), $version );

	$path = '/assets/js/script' . $suffix . '.js';
	$version = filemtime( get_template_directory() . $path );
	wp_enqueue_script( 'bradtca', get_template_directory_uri() . $path, array( 'jquery' ), $version, true );
}
add_action( 'wp_enqueue_scripts', 'bt_enqueue_scripts' );

add_editor_style();


function bt_parse_query( $query ) {
	if ( is_admin() ) return;

	if ( is_post_type_archive( 'journal_entry' ) ) {
		$query->set( 'posts_per_page', 50 );
	}

	if ( is_post_type_archive( 'portfolio_item' ) ) {
		$query->set( 'posts_per_page', -1 );
		//$query->set('orderby');
	}
}
add_filter( 'parse_query', 'bt_parse_query' );

function bt_post_status_new( $new_status, $old_status, $post ) {
    if ( $post->post_type == 'journal_entry' && $new_status == 'publish' && $old_status != $new_status && $old_status != 'private' ) {
        $post->post_status = 'private';
        wp_update_post( $post );
    }
}
add_action( 'transition_post_status', 'bt_post_status_new', 10, 3 );

function bt_ajax_record_journal_entry() {
	if ( !check_ajax_referer( 'record-journal-entry', 'nonce', false ) || !current_user_can( 'export' ) ) {
		die( "Cheatin' eh?" );
	}

	wp_insert_post( array(
		'post_type' => 'journal_entry',
		'post_status' => 'private',
		'post_content' => $_POST['entry-content']
	) );

	if ( isset( $_POST['_wp_http_referer'] ) ) {
		wp_redirect( $_POST['_wp_http_referer'] );
	}
}
add_action( 'wp_ajax_record_journal_entry', 'bt_ajax_record_journal_entry' );