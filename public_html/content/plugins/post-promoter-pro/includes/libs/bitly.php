<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Bitly Class
 *
 * Handles all bitly functions
 *
 */
if( !class_exists( 'PPP_Bitly' ) ) {

	class PPP_Bitly {

		var $bitly;

		public function __construct(){
			if ( !isset( $_SESSION ) ) {
			  	session_start();
			}
		}

		/**
		 * Include Twitter Class
		 *
		 * Handles to load twitter class
		 */
		public function ppp_load_bitly() {
				if( !class_exists( 'Bitly' ) ) {
					require_once ( PPP_PATH . '/includes/libs/bitly/bitly.php' );
				}

				ppp_set_social_tokens();

				if ( ! defined( 'bitly_clientid' ) || ! defined( 'bitly_secret' ) ) {
					return false;
				}

				global $ppp_social_settings;

				if ( isset( $ppp_social_settings['bitly'] ) ) {
					define( 'bitly_accesstoken', $ppp_social_settings['bitly']['access_token'] );
					$this->bitly = new Bitly( bitly_clientid, bitly_secret, bitly_accesstoken );
				} else {
					$this->bitly = new Bitly( bitly_clientid, bitly_secret );
				}

				return true;
		}

		public function revoke_access() {
			global $ppp_social_settings;

			unset( $ppp_social_settings['bitly'] );

			update_option( 'ppp_social_settings', $ppp_social_settings );
		}

		/**
		 * Get auth codes for Bitly
		 *
		 */
		public function ppp_get_bitly_auth_url () {
			//load bitly class
			$bitly = $this->ppp_load_bitly();

			//check bitly class is loaded or not
			if( !$bitly ) return false;

			$url = $this->bitly->getAuthUrl(  );
			return $url;
		}

		public function ppp_make_bitly_link( $link = null ) {
			if ( empty( $link ) ) {
				return false;
			}

			$bitly = $this->ppp_load_bitly();

			if ( !$bitly ) {
				return false;
			}

			return $this->bitly->shorten( $link );
		}

		public function ppp_bitly_user_info() {
			$bitly = $this->ppp_load_bitly();

			if ( !$bitly ) {
				return false;
			}

			return $this->bitly->userInfo( null, null, false );
		}

	}

}