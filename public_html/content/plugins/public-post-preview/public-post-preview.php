<?php
/**
 * Plugin Name: Public Post Preview
 * Version: 2.2
 * Description: Enables you to give a link to anonymous users for public preview of any post type before it is published.
 * Author: Dominik Schilling
 * Author URI: http://wphelper.de/
 * Plugin URI: http://wpgrafie.de/wp-plugins/public-post-preview/en/
 *
 * Text Domain: ds-public-post-preview
 * Domain Path: /lang
 *
 * License: GPLv2 or later
 *
 * Previously (2009-2011) maintained by Jonathan Dingman and Matt Martz.
 *
 *	Copyright (C) 2012-2013 Dominik Schilling
 *
 *	This program is free software; you can redistribute it and/or
 *	modify it under the terms of the GNU General Public License
 *	as published by the Free Software Foundation; either version 2
 *	of the License, or (at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program; if not, write to the Free Software
 *	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * Don't call this file directly.
 */
if ( ! class_exists( 'WP' ) ) {
	die();
}

/**
 * The class which controls the plugin.
 *
 * Used hooks:
 *  - pre_get_posts
 *  - query_vars
 *  - init
 *  - post_submitbox_misc_actions
 *  - save_post
 *  - posts_results
 *  - wp_ajax_public-post-preview
 *  - admin_enqueue_scripts
 *
 * Inits at 'plugins_loaded' hook.
 *
 */
class DS_Public_Post_Preview {

	/**
	 * Hooks into 'pre_get_posts' to handle public preview, only nn-admin
	 * Hooks into 'add_meta_boxes' to register the meta box.
	 * Hooks into 'save_post' to handle the values of the meta box.
	 * Hooks into 'admin_enqueue_scripts' to register JavaScript.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'load_textdomain' ) );

		if ( ! is_admin() ) {
			add_filter( 'pre_get_posts', array( __CLASS__, 'show_public_preview' ) );

			add_filter( 'query_vars', array( __CLASS__, 'add_query_var' ) );
		} else {
			add_action( 'post_submitbox_misc_actions', array( __CLASS__, 'post_submitbox_misc_actions' ) );

			add_action( 'save_post', array( __CLASS__, 'register_public_preview' ), 20, 2 );

			add_action( 'wp_ajax_public-post-preview', array( __CLASS__, 'ajax_register_public_preview' ) );

			add_action( 'admin_enqueue_scripts' , array( __CLASS__, 'enqueue_script' ) );
		}
	}

	/**
	 * Registers the textdomain.
	 *
	 * @since 2.0.0
	 */
	public static function load_textdomain() {
		return load_plugin_textdomain(
			'ds-public-post-preview',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/lang'
		);
	}

	/**
	 * Registers the JavaScript file for post(-new).php.
	 *
	 * @since 2.0.0
	 */
	public static function enqueue_script( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ) ) )
			return;

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script(
			'ds-public-post-preview',
			plugins_url( "js/public-post-preview$suffix.js", __FILE__ ),
			array( 'jquery' ),
			self::get_plugin_info( 'Version' ),
			true
		);

		wp_localize_script(
			'ds-public-post-preview',
			'DSPublicPostPreviewL10n',
			array(
				'enabled'  => __( 'Enabled!', 'ds-public-post-preview' ),
				'disabled' => __( 'Disabled!', 'ds-public-post-preview' )
			)
		);
	}

	/**
	 * Adds the checkbox to the submit metabox
	 *
	 * @since 2.2.0
	 */
	public static function post_submitbox_misc_actions() {
		$post_types = get_post_types(
			array(
				'public' => true
			)
		);

		$post = get_post();

		if ( ! in_array( $post->post_type, $post_types ) )
			return false;

		if ( ! in_array( $post->post_status, array( 'draft', 'pending', 'future' ) ) )
			return false;

		?>
		<div class="misc-pub-section public-post-preview">
			<?php self::get_checkbox_html( $post ); ?>
		</div>
		<?php

	}

	/**
	 * Prints the checkbox if the is draft, pending or future.
	 *
	 */
	private static function get_checkbox_html( $post ) {
		if ( empty( $post ) )
			$post = get_post();

		wp_nonce_field( 'public_post_preview', 'public_post_preview_wpnonce' );

		$preview_post_ids = self::get_preview_post_ids();
		?>
		<label><input type="checkbox"<?php checked( in_array( $post->ID, $preview_post_ids ) ); ?> name="public_post_preview" id="public-post-preview" value="1" />
		<?php _e( 'Enable public preview', 'ds-public-post-preview' ); ?> <span id="public-post-preview-ajax"></span></label>

		<div id="public-post-preview-link" style="margin-top:6px">
			<label>
				<input type="text" name="public_post_preview_link" class="regular-text" value="<?php echo esc_attr( self::get_preview_link( $post->ID ) ); ?>" style="width:99%" readonly />
				<span class="description"><?php _e( '(Copy and share this link.)', 'ds-public-post-preview' ); ?></span>
			</label>
		</div>
		<?php
	}

	/**
	 * Returns the public preview link.
	 *
	 * The link is the permalink with these parameters:
	 *  - preview, always true (query var for core)
	 *  - _ppp, a custom nonce, see DS_Public_Post_Preview::create_nonce()
	 *
	 * @since  2.0.0
	 *
	 * @param  int    $post_id  The post id.
	 * @return string           The generated public preview link.
	 */
	private static function get_preview_link( $post_id ) {
		return add_query_arg(
			array(
				'preview' => true,
				'_ppp'    => self::create_nonce( 'public_post_preview_' . $post_id ),
			),
			get_permalink( $post_id )
		);
	}

	/**
	 * (Un)Registers a post for a public preview.
	 *
	 * Don't runs on an autosave and ignores post revisions.
	 *
	 * @since  2.0.0
	 *
	 * @param  int    $post_id The post id.
	 * @param  object $post    The post object.
	 * @return bool            Returns false on a failure, true on a success.
	 */
	public static function register_public_preview( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return false;

		if ( wp_is_post_revision( $post_id ) )
			return false;

		if ( empty( $_POST['public_post_preview_wpnonce'] ) || ! wp_verify_nonce( $_POST['public_post_preview_wpnonce'], 'public_post_preview' ) )
			return false;

		$preview_post_ids = self::get_preview_post_ids();
		$preview_post_id  = $post->ID;

		if ( empty( $_POST['public_post_preview'] ) && in_array( $preview_post_id, $preview_post_ids ) )
			$preview_post_ids = array_diff( $preview_post_ids, (array) $preview_post_id );
		elseif (
				! empty( $_POST['public_post_preview'] ) &&
				! empty( $_POST['original_post_status'] ) &&
				'publish' != $_POST['original_post_status'] &&
				'publish' == $post->post_status &&
				in_array( $preview_post_id, $preview_post_ids )
			)
			$preview_post_ids = array_diff( $preview_post_ids, (array) $preview_post_id );
		elseif ( ! empty( $_POST['public_post_preview'] ) && ! in_array( $preview_post_id, $preview_post_ids ) )
			$preview_post_ids = array_merge( $preview_post_ids, (array) $preview_post_id );
		else
			return false; // Nothing changed.

		return self::set_preview_post_ids( $preview_post_ids );
	}

	/**
	 * (Un)Registers a post for a public preview for an AJAX request.
	 *
	 * @since  2.0.0
	 *
	 * @return string Returns '0' on a failure, '1' on success.
	 */
	public static function ajax_register_public_preview() {
		check_ajax_referer( 'public_post_preview' );

		$preview_post_id = (int) $_POST['post_ID'];
		$post = get_post( $preview_post_id );

		if ( ( 'page' == $post->post_type && ! current_user_can( 'edit_page', $preview_post_id ) ) || ! current_user_can( 'edit_post', $preview_post_id ) )
			wp_die( 0 );

		if ( ! in_array( $post->post_status, array( 'draft', 'pending', 'future' ) ) )
			wp_die( 0 );

		$preview_post_ids = self::get_preview_post_ids();

		if ( empty( $_POST['checked'] ) && in_array( $preview_post_id, $preview_post_ids ) )
			$preview_post_ids = array_diff( $preview_post_ids, (array) $preview_post_id );
		elseif ( ! empty( $_POST['checked'] ) && ! in_array( $preview_post_id, $preview_post_ids ) )
			$preview_post_ids = array_merge( $preview_post_ids, (array) $preview_post_id );
		else
			wp_die( 0 );

		$ret = self::set_preview_post_ids( $preview_post_ids );

		if ( ! $ret )
			wp_die( 0 );

		wp_die( 1 );
	}

	/**
	 * Registers the new query var `_ppp`.
	 *
	 * @since  2.1
	 *
	 * @return array List of query variables.
	 */
	public static function add_query_var( $qv ) {
		$qv[] = '_ppp';

		return $qv;
	}

	/**
	 * Registers the filter to handle a public preview.
	 *
	 * Filter will be set if it's the main query, a preview, a singular page
	 * and the query var `_ppp` exists.
	 *
	 * @since  2.0.0
	 *
	 * @param  object $query The WP_Query object.
	 * @return object        The WP_Query object, unchanged.
	 */
	public static function show_public_preview( $query ) {
		if (
			$query->is_main_query() &&
			$query->is_preview() &&
			$query->is_singular() &&
			$query->get( '_ppp' )
		)
			add_filter( 'posts_results', array( __CLASS__, 'set_post_to_publish' ), 10, 2 );

		return $query;
	}

	/**
	 * Checks if a public preview is available and allowed.
	 * Verifies the nonce and if the post id is registered for a public preview.
	 *
	 * @since  2.0.0
	 *
	 * @param  int   $post_id The post id.
	 * @return bool           True if a public preview is allowed, false on a failure.
	 */
	private static function public_preview_available( $post_id ) {
		if ( empty( $post_id ) )
			return false;

		if( ! self::verify_nonce( get_query_var( '_ppp' ), 'public_post_preview_' . $post_id ) )
			wp_die( __( 'The link has been expired!', 'ds-public-post-preview' ) );

		if ( ! in_array( $post_id, get_option( 'public_post_preview', array() ) ) )
			wp_die( __( 'No Public Preview available!', 'ds-public-post-preview' ) );

		return true;
	}

	/**
	 * Sets the post status of the first post to publish, so we don't have to do anything
	 * *too* hacky to get it to load the preview.
	 *
	 * @since 2.0.0
	 *
	 * @param array $posts The post to preview.
	 */
	public static function set_post_to_publish( $posts ) {
		// Remove the filter again, otherwise it will be applied to other queries too.
		remove_filter( 'posts_results', array( __CLASS__, 'set_post_to_publish' ), 10, 2 );

		if ( empty( $posts ) )
			return;

		if ( self::public_preview_available( $posts[0]->ID ) )
			$posts[0]->post_status = 'publish';

		return $posts;
	}

	/**
	 * Get the time-dependent variable for nonce creation.
	 *
	 * @see    wp_nonce_tick()
	 *
	 * @since  2.1
	 *
	 * @return int The time-dependent variable
	 */
	private static function nonce_tick() {
		$nonce_life = apply_filters( 'ppp_nonce_life', 60 * 60 * 48 ); // 48 hours

		return ceil( time() / ( $nonce_life / 2 ) );
	}

	/**
	 * Creates a random, one time use token. Without an UID.
	 *
	 * @see    wp_create_nonce()
	 *
	 * @since  1.0.0
	 *
	 * @param  string|int $action Scalar value to add context to the nonce.
	 * @return string             The one use form token
	 */
	private static function create_nonce( $action = -1 ) {
		$i = self::nonce_tick();

		return substr( wp_hash( $i . $action, 'nonce' ), -12, 10 );
	}

	/**
	 * Verifies that correct nonce was used with time limit. Without an UID.
	 *
	 * @see    wp_verify_nonce()
	 *
	 * @since  1.0.0
	 *
	 * @param  string     $nonce  Nonce that was used in the form to verify
	 * @param  string|int $action Should give context to what is taking place and be the same when nonce was created.
	 * @return bool               Whether the nonce check passed or failed.
	 */
	private static function verify_nonce( $nonce, $action = -1 ) {
		$i = self::nonce_tick();

		// Nonce generated 0-12 hours ago
		if ( substr( wp_hash( $i . $action, 'nonce' ), -12, 10 ) == $nonce )
			return 1;

		// Nonce generated 12-24 hours ago
		if ( substr( wp_hash( ( $i - 1 ) . $action, 'nonce' ), -12, 10 ) == $nonce )
			return 2;

		// Invalid nonce
		return false;
	}

	/**
	 * Returns the post ids which are registered for a public preview.
	 *
	 * @since  2.0.0
	 *
	 * @return array The post ids. (Empty array if no ids are registered.)
	 */
	private static function get_preview_post_ids() {
		return get_option( 'public_post_preview', array() );
	}

	/**
	 * Saves the post ids which are registered for a public preview.
	 *
	 * @since  2.0.0
	 *
	 * @return array The post ids. (Empty array if no ids are registered.)
	 */
	private static function set_preview_post_ids( $post_ids = array( )) {
		return update_option( 'public_post_preview', $post_ids );
	}

	/**
	 * Small helper to get some plugin info.
	 *
	 * @since  2.0.0
	 *
	 * @param  string        $key The key to get the info from, see get_plugin_data().
	 * @return string|bool        Either the value, or if the key doesn't exists false.
	 */
	private static function get_plugin_info( $key = null ) {
		$plugin_data = get_plugin_data( __FILE__);
		if ( array_key_exists( $key, $plugin_data ) )
			return $plugin_data[ $key ];

		return false;
	}

	/**
	 * Delets the option 'public_post_preview' if the plugin will be uninstalled.
	 *
	 * @since 2.0.0
	 */
	public static function uninstall() {
		delete_option( 'public_post_preview' );
	}
}

add_action( 'plugins_loaded', array( 'DS_Public_Post_Preview', 'init' ) );

register_uninstall_hook( __FILE__, array( 'DS_Public_Post_Preview', 'uninstall' ) );
