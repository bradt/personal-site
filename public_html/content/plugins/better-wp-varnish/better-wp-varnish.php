<?php
/*
	Plugin Name: Better WP Varnish
	Plugin URI: http://bit51.com/software/better-wp-varnish/
	Description: A better solution for clearing Varnish cache with WordPress
	Version: 100.0.0.1
	Text Domain: better-wp-varnish
	Domain Path: /languages
	Author: Bit51.com
	Author URI: http://bit51.com
	License: GPLv2
	Copyright 2012 Bit51.com (email: info@bit51.com)
*/


//Require common Bit51 library
require_once( plugin_dir_path( __FILE__ ) . 'lib/bit51/bit51.php' );

if ( ! class_exists( 'bit51_bwpv' )) {

	class bit51_bwpv extends Bit51 {
	
		public $pluginversion 	= '0001'; //current plugin version
	
		//important plugin information
		public $hook 			= 'better-wp-varnish';
		public $pluginbase		= 'better-wp-varnish/better-wp-varnish.php';
		public $pluginname		= 'Better WP Varnish';
		public $homepage		= 'http://bit51.com/software/share-center-pro/';
		public $supportpage 	= 'http://wordpress.org/support/plugin/better-wp-varnish';
		public $wppage 			= 'http://wordpress.org/extend/plugins/better-wp-varnish/';
		public $accesslvl		= 'manage_options';
		public $paypalcode		= 'EVXJ8EN6YSYFY';
		public $plugindata 		= 'bit51_bwpv_data';
		public $primarysettings	= 'bit51_bwpv';
		public $settings		= array(
			'bit51_bwpv_options' 	=> array(
				'bit51_bwpv' 			=> array(
					'callback' 				=> 'bwpv_val_options',
					'enabled'				=> '0',
					'address'				=> '127.0.0.1',
					'port'					=> '80',
					'timeout'				=> '5'
				)
			)
		);

		function __construct() {

			global $bwpvoptions, $bwpvdata;
		
			//set path information
			
			if ( ! defined( 'BWPV_PP' ) ) {
				define( 'BWPV_PP', plugin_dir_path( __FILE__ ) );
			}
			
			if ( ! defined( 'BWPV_PU' ) ) {
				define( 'BWPV_PU', plugin_dir_url( $this->pluginbase, __FILE__ ) );
			}
		
			//require admin page
			require_once( plugin_dir_path( __FILE__ ) . 'inc/admin.php' );
			new bwpv_admin( $this );
			
			//require setup information
			require_once( plugin_dir_path( __FILE__ ) . 'inc/setup.php' );
			register_activation_hook( __FILE__, array( 'bwpv_setup', 'on_activate' ) );
			register_deactivation_hook( __FILE__, array( 'bwpv_setup', 'on_deactivate' ) );
			register_uninstall_hook( __FILE__, array( 'bwpv_setup', 'on_uninstall' ) );

			$bwpvoptions = get_option( $this->primarysettings );
			$bwpvdata = get_option( $this->plugindata );
			
			if ( $bwpvdata['version'] != $this->pluginversion ) {
				new bwpv_setup( 'activate' );
			}

			if ( $bwpvoptions['enabled'] == 1 ) {

				add_action(	'edit_post', array( &$this, 'purgePost' ), 99 );
				add_action(	'edit_post', array(  &$this, 'purgeCommon' ), 99 );
				add_action(	'comment_post', array( &$this, 'purgeComment' ), 99 );
				add_action(	'edit_comment', array( &$this, 'purgeComment' ), 99 );
				add_action(	'trashed_comment', array( &$this, 'purgeComment' ), 99 );
				add_action(	'untrashed_comment', array( &$this, 'purgeComment' ), 99 );
				add_action(	'deleted_comment', array( &$this, 'purgeComment' ), 99 );
				add_action(	'deleted_post', array( &$this, 'purgePost' ), 99 );
				add_action(	'deleted_post', array( &$this, 'purgeCommon' ), 99 );

				add_action( 'wp_before_admin_bar_render', array( &$this, 'adminBar' ) );				

			}

		}

		function adminBar() {

			global $wp_admin_bar, $post;

			if ( current_user_can( $this->accesslvl ) ) {

				$nonce = wp_create_nonce( 'bwpv-nonce' );

				$wp_admin_bar->add_menu( array(
					'parent' => false, // use 'false' for a root menu, or pass the ID of the parent menu
					'id' => 'bwpv', // link ID, defaults to a sanitized title value
					'title' => __( 'Varnish', $this->hook ), // link title
					'href' => admin_url( 'options-general.php?page=better-wp-varnish' ), // name of file
					'meta' => false // array of any of the following options: array( 'html' => '', 'class' => '', 'onclick' => '', target => '', title => '' );
				) );

				if ( isset( $post->ID ) && $post->ID ) {
					$id = $post->ID;
				} else {
					$id = false;
				}
	
				if ( $id !== false ) {
					$wp_admin_bar->add_menu( array(
						'parent' => 'bwpv', // use 'false' for a root menu, or pass the ID of the parent menu
						'id' => 'bwpv-cp', // link ID, defaults to a sanitized title value
						'title' => __( 'Clear This Page', $this->hook ), // link title
						'href' => admin_url( 'options-general.php?page=better-wp-varnish&flush=current&id=' . $id . '&_wpnonce=' . $nonce ), // name of file
						'meta' => false // array of any of the following options: array( 'html' => '', 'class' => '', 'onclick' => '', target => '', title => '' );
					) );
				}
	
				$wp_admin_bar->add_menu( array(
					'parent' => 'bwpv', // use 'false' for a root menu, or pass the ID of the parent menu
					'id' => 'bwpv-ca', // link ID, defaults to a sanitized title value
					'title' => __( 'Clear All', $this->hook ), // link title
					'href' => admin_url( 'options-general.php?page=better-wp-varnish&flush=all&id=' . ( $id === false ? 'opts' : $id ) . '&_wpnonce=' . $nonce ), // name of file
					'meta' => false // array of any of the following options: array( 'html' => '', 'class' => '', 'onclick' => '', target => '', title => '' );
				) );

			}

		}


		/**
		 * Purge all content from cache
		 * 
		 **/
		function purgeAll() {

			$errorHandler = __( 'WordPress Core File Writing ignored.', $this->hook );
			
			if ( $this->purgeVarnish( '(.*)' ) == true ) {

				echo '<div id="message" class="updated"><p><strong>' . __( 'Cache Succeddfully Cleared', $this->hook ) . '</strong></p></div>';

			} else {

				echo '<div id="message" class="error"><p>' . __( 'ERROR: Could not clear cache. Contact your server administrator if this error persists.', $this->hook ) . '</p></div>';

			}

		}

		/**
		 * Purge common content from cache
		 * 
		 **/
		function purgeCommon() {

			$success = $this->purgeVarnish( '/' );
			$success = $this->purgeVarnish( '(.*)/feed/(.*)' );
			$success = $this->purgeVarnish( '(.*)/trackback/(.*)' );
			$success = $this->purgeVarnish( '/page/(.*)' );

			if ( $success == true ) {

				echo '<div id="message" class="updated"><p><strong>' . __( 'Cache Succeddfully Cleared', $this->hook ) . '</strong></p></div>';

			} else {

				echo '<div id="message" class="error"><p>' . __( 'ERROR: Could not clear cache. Contact your server administrator if this error persists.', $this->hook ) . '</p></div>';

			}

		}

		/**
		 * Purges the given post from varnish
		 * 
		 * @param $postid int post id
		 **/
		function purgePost( $postid ) {

			
			$url = get_permalink( $postid );
			$link = str_replace( home_url(), '', $url );
			
			$success = $this->purgeVarnish( $link );
			$success = $this->purgeVarnish( $link . 'page/(.*)' );

			if ( $success == true ) {

				echo '<div id="message" class="updated"><p><strong>' . __( 'Cache Succeddfully Cleared', $this->hook ) . '</strong></p></div>';

			} else {

				echo '<div id="message" class="error"><p>' . __( 'ERROR: Could not clear cache. Contact your server administrator if this error persists.', $this->hook ) . '</p></div>';

			}

		}

		/**
		 * Purges the given comment from varnish
		 * 
		 * @param $commentit int comment id
		 **/
		function purgeComment( $commentid ) {
			
			$comment = get_comment( $commentid );
			$approved = $comment->comment_approved;

			$success = true;

			if ( $approved == 1 || $approved == 'trash') {

				$approved = $comment->comment_post_ID;

				$success = $this->purgeVarnish( '/\\\?comments_popup=' . $approved );
				$success = $this->purgeVarnish( '/\\\?comments_popup=' . $approved . '&(.*)' );

			}

			if ( $success == true ) {

				echo '<div id="message" class="updated"><p><strong>' . __( 'Cache Succeddfully Cleared', $this->hook ) . '</strong></p></div>';

			} else {

				echo '<div id="message" class="error"><p>' . __( 'ERROR: Could not clear cache. Contact your server administrator if this error persists.', $this->hook ) . '</p></div>';

			}

		}

		/**
		 * Purges the given url from varnish
		 * 
		 * @param $target string url of the file to purge
		 * @return bool true for success, false for failure
		 **/
		function purgeVarnish( $target ) {

			global $bwpvoptions;

			@ini_set( 'auto_detect_line_endings', true );

			$host = parse_url( get_site_url(), PHP_URL_HOST );

			$out = 'PURGE ' . $target . ' HTTP/1.0' . PHP_EOL;
			$out .= 'Host: ' . $host . PHP_EOL;
			$out .= 'Connection: Close' . PHP_EOL . PHP_EOL;

			$sock = fsockopen( $bwpvoptions['address'], $bwpvoptions['port'], $errno, $errstr, $bwpvoptions['timeout'] );

			if ( $sock ) {
				
				fwrite( $sock, $out );
				$result = fread( $sock, 256 );
				fclose( $sock );

			}

			if ( preg_match( '/200 OK/', $result ) || preg_match( '/200 Purged/', $result ) ) { 
				return true;
			} else {
				return false;
			}

		}

	}

}

//create plugin object
global $bit51bwpv; 
$bit51bwpv = new bit51_bwpv();
