<?php
global $content_width;
if ( ! isset( $content_width ) )
	$content_width = 684;

add_theme_support( 'automatic-feed-links' );
add_theme_support( 'post-thumbnails' );

function bt_theme_setup() {
	$labels = array(
		'name' => _x('Tweets', 'bradt.ca'),
		'singular_name' => _x('Tweet', 'bradt.ca'),
		'add_new' => _x('Add New', 'bradt.ca'),
		'add_new_item' => __('Add New Tweet'),
		'edit_item' => __('Edit Tweet'),
		'new_item' => __('New Tweet'),
		'view_item' => __('View Tweet'),
		'search_items' => __('Search Tweet'),
		'not_found' =>  __('No tweets found'),
		'not_found_in_trash' => __('No tweets found in Trash'), 
		'parent_item_colon' => '',
		'menu_name' => 'Tweets'
	);
	register_post_type('tweet',array(
		'labels' => $labels,
		'public' => true,
		'has_archive' => 'tweets',
		'rewrite' => array(
			'slug' => 'tweet',
			'with_front' => false
		),
		'supports' => array('title', 'editor', 'author', 'custom-fields', 'revisions', 'thumbnail')
	));

	$labels = array(
		'name' => _x('Portfolio', 'bradt.ca'),
		'singular_name' => _x('Portfolio Item', 'bradt.ca'),
		'add_new' => _x('Add New', 'bradt.ca'),
		'add_new_item' => __('Add New Portfolio Item'),
		'edit_item' => __('Edit Portfolio Item'),
		'new_item' => __('New Portfolio Item'),
		'view_item' => __('View Portfolio Item'),
		'search_items' => __('Search Portfolio Item'),
		'not_found' =>  __('No portfolio items found'),
		'not_found_in_trash' => __('No portfolio items found in Trash'), 
		'parent_item_colon' => '',
		'menu_name' => 'Portfolio'
	);
	register_post_type('portfolio_item',array(
		'labels' => $labels,
		'public' => true,
		'has_archive' => 'portfolio',
		'rewrite' => array(
			'slug' => 'portfolio',
			'with_front' => false
		),
		'supports' => array('title', 'editor', 'author', 'custom-fields', 'revisions', 'thumbnail')
	));

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
	register_taxonomy('portfolio_tag', array('portfolio_item'), array(
		'labels' => $labels,
		'public' => false,
		'show_ui' => true
	));

	$labels = array(
		'name' => _x('Journal', 'bradt.ca'),
		'singular_name' => _x('Journal Entry', 'bradt.ca'),
		'add_new' => _x('Add New', 'bradt.ca'),
		'add_new_item' => __('Add New Journal Entry'),
		'edit_item' => __('Edit Journal Entry'),
		'new_item' => __('New Journal Entry'),
		'view_item' => __('View Journal Entry'),
		'search_items' => __('Search Journal Entry'),
		'not_found' =>  __('No journal entries found'),
		'not_found_in_trash' => __('No journal entries found in Trash'), 
		'parent_item_colon' => '',
		'menu_name' => 'Journal'
	);
	register_post_type('journal_entry',array(
		'labels' => $labels,
		'public' => true,
		'has_archive' => 'journal',
		'taxonomies' => array( 'post_tag' ),
		'rewrite' => array(
			'slug' => 'journal',
			'with_front' => false
		),
		'supports' => array('title', 'editor', 'author', 'custom-fields', 'revisions', 'thumbnail')
	));

	$labels = array(
		'name' => _x('Photo Sets', 'bradt.ca'),
		'singular_name' => _x('Photo Set', 'bradt.ca'),
		'add_new' => _x('Add New', 'bradt.ca'),
		'add_new_item' => __('Add New Photo Set'),
		'edit_item' => __('Edit Photo Set'),
		'new_item' => __('New Photo Set'),
		'view_item' => __('View Photo Set'),
		'search_items' => __('Search Photo Set'),
		'not_found' =>  __('No photo sets found'),
		'not_found_in_trash' => __('No photo sets found in Trash'), 
		'parent_item_colon' => '',
		'menu_name' => 'Photos'
	);
	register_post_type('photo_set',array(
		'labels' => $labels,
		'public' => true,
		'has_archive' => 'photos',
		'rewrite' => array(
			'slug' => 'photo-set',
			'with_front' => false
		),
		'supports' => array('title', 'editor', 'author', 'custom-fields', 'revisions', 'thumbnail')
	));

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
	register_taxonomy('photo_tag', array('attachment', 'photo_set'), array(
		'labels' => $labels,
		'rewrite' => array(
			'slug' => 'photo-tag',
			'with_front' => false
		)
	));

	$labels = array(
		'name' => _x( 'Collections', 'taxonomy general name' ),
		'singular_name' => _x( 'Collection', 'taxonomy singular name' ),
		'search_items' =>  __( 'Search Collections' ),
		'all_items' => __( 'All Collections' ),
		'parent_item' => __( 'Parent Collection' ),
		'parent_item_colon' => __( 'Parent Collection:' ),
		'edit_item' => __( 'Edit Collection' ), 
		'update_item' => __( 'Update Collection' ),
		'add_new_item' => __( 'Add New Collection' ),
		'new_item_name' => __( 'New Collection Name' ),
		'menu_name' => __( 'Collections' ),
	);
	register_taxonomy('photo_collection', 'photo_set', array(
		'labels' => $labels,
		'hierarchical' => true,
		'rewrite' => array(
			'slug' => 'photo-collection',
			'with_front' => false
		)
	));

}
add_action( 'init', 'bt_theme_setup' );

add_editor_style();


function bt_parse_query($query) {
	if (is_admin()) return;

    if (is_post_type_archive('portfolio_item')) {
		$query->set('posts_per_page', -1);
        //$query->set('orderby');
    }
    
    if (is_post_type_archive('photo_set')) {
		$query->set('posts_per_page', 50);
        //$query->set('orderby');
    }
	
	if (is_tax('photo_tag')) {
		$query->set('posts_per_page', 50);
		$query->set('post_status', 'inherit');
		return;
	}

	if (is_tax('photo_collection')) {
		$query->set('posts_per_page', 50);
		return;
	}
}
add_filter('parse_query', 'bt_parse_query');
