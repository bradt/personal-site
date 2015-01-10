<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Facebook Class
 *
 * Handles all facebook functions
 *
 */
if( !class_exists( 'PPP_Facebook' ) ) {

	class PPP_Facebook {

		var $facebook;

		public function __construct(){
			ppp_maybe_start_session();
		}

		/**
		 * Include Facebook Class
		 *
		 * Handles to load facebook class
		 *
		 */
		public function ppp_load_facebook() {


			if( !class_exists( 'Facebook' ) ) {
				require_once ( PPP_PATH . '/includes/libs/facebook/facebook.php' );
			}

			ppp_set_social_tokens();

			$this->facebook = new Facebook( array(
					'appId' => PPP_FB_APP_ID,
					'secret' => PPP_FB_APP_SECRET,
					'cookie' => true
			));

			return true;

		}

		/**
		 * Initializes Facebook API
		 *
		 */
		function ppp_initialize_facebook() {
			//when user is going to logged in in linkedin and verified successfully session will create
			if ( isset( $_REQUEST['fb_access_token'] ) && isset( $_REQUEST['expires_in'] ) ) {
				global $ppp_social_settings;
				$ppp_social_settings = get_option( 'ppp_social_settings' );

				//load linkedin class
				$facebook = $this->ppp_load_facebook();

				//check linkedin class is loaded or not
				if( !$facebook ) return false;

				$data = new stdClass();
				$data->access_token = $_REQUEST['fb_access_token'];

				$expires_in = 60 * 24 * 60 * 60; // days * hours * minutes * seconds
				$data->expires_on = current_time( 'timestamp' ) + $expires_in;

				update_option( '_ppp_facebook_refresh', current_time( 'timestamp' ) + round( $expires_in/1.25 ) );

				// Now that we have a valid auth, get some user info
				$user_info = $this->ppp_get_fb_user( $data->access_token );

				if ( $user_info ) {
					if ( !empty( $user_info->name ) ) {
						$data->name = $user_info->name;
					} else {
						$parsed_name = $user_info->first_name . ' ' . $user_info->last_name;
						$data->name = $parsed_name;
					}
					$data->userid = $user_info->id;
					$data->avatar = $this->ppp_fb_get_profile_picture( array( 'type' => 'square' ), $data->userid );
					$ppp_social_settings['facebook'] = $data;

					update_option( 'ppp_social_settings', $ppp_social_settings );
				}


				$url = remove_query_arg( array( 'fb_access_token' , 'expires_in' ) );
				wp_redirect( $url );
				die();
			}
		}

		/**
		 * Get Facebook User
		 *
		 * Handles to return facebook user id
		 *
		 */
		public function ppp_get_fb_user( $access_token ) {

			//load facebook class
			$facebook = $this->ppp_load_facebook();

			//check facebook class is exis or not
			if( !$facebook ) return false;

			global $ppp_social_settings;
			$user = json_decode( wp_remote_retrieve_body( wp_remote_get( 'https://graph.facebook.com/me?access_token=' . $access_token ) ) );

			return $user;

		}

		public function ppp_get_fb_user_pages( $access_token ) {

			// load facebook class
			$facebook = $this->ppp_load_facebook();

			// check facebook cleast is exists or not
			if( !$facebook ) return false;

			global $ppp_social_settings;
			$facebook_settings = $ppp_social_settings['facebook'];

			if ( !isset( $facebook_settings->available_pages ) ||
				 !isset( $facebook_settings->pages_last_updated ) ||
				 $facebook_settings->pages_last_updated > ( current_time( 'timestamp' ) + WEEK_IN_SECONDS ) ) {

				$all_pages = json_decode( wp_remote_retrieve_body( wp_remote_get( 'https://graph.facebook.com/me/accounts?access_token=' . $access_token ) ) );
				$pages = array();
				if ( !empty( $all_pages ) ) {
					foreach ( $all_pages->data as $page ) {
						if ( in_array( 'CREATE_CONTENT', $page->perms ) ) {
							$pages[] = $page;
						}
					}
				} else {
					$pages = false;
				}


				$pages = (object) $pages;
				$ppp_social_settings['facebook']->available_pages = $pages;
				$ppp_social_settings['facebook']->pages_last_updated = current_time( 'timestamp' ) + WEEK_IN_SECONDS;
				update_option( 'ppp_social_settings', $ppp_social_settings );
			} else {
				$pages = $facebook_settings->available_pages;

			}

			return $pages;
		}

		/**
		 * Access Token
		 *
		 * Getting the access token from Facebook.
		 *
		 */
		public function ppp_fb_getaccesstoken() {

			//load facebook class
			$facebook = $this->ppp_load_facebook();

			//check facebook class is exis or not
			if( !$facebook ) return false;

			return $this->facebook->getAccessToken();
		}

		/**
		 * Get auth url for facebook
		 *
		 */
		public function ppp_get_facebook_auth_url ( $return_url ) {
			$base_url = 'https://postpromoterpro.com/?ppp-social-auth';
			$url  = $base_url . '&ppp-service=fb&ppp-license-key=' . trim( get_option( '_ppp_license_key' ) );
			$url .= '&nocache';
			$url .= '&return_url=' . esc_url( $return_url );

			return $url;
		}

		/**
		 * Check Application Permission
		 *
		 * Handles to check facebook application
		 * permission is given by user or not
		 *
		 */
		public function ppp_check_fb_app_permission( $perm="" ) {

			$data = '1';
			if( !empty( $perm ) ) {
				$userID = $this->ppp_get_fb_user();
				$accToken = $this->ppp_fb_getaccesstoken();
				$url = "https://api.facebook.com/method/users.hasAppPermission?ext_perm=$perm&uid=$userID&access_token=$accToken&format=json";
				$data = json_decode( $this->ppp_get_data_from_url( $url ) );
			}
			return $data;
		}

		/**
		 * User Image
		 *
		 * Getting the the profile image of the connected Facebook user.
		 *
		 */
		public function ppp_fb_get_profile_picture( $args=array(), $user ) {

			if( isset( $args['type'] ) && !empty( $args['type'] ) ) {
				$type = $args['type'];
			} else {
				$type = 'large';
			}
			$url = 'https://graph.facebook.com/' . $user . '/picture?type=' . $type;
			return $url;
		}

		public function ppp_fb_share_link( $link, $message, $image ) {
			global $ppp_social_settings;
			$facebook_settings = $ppp_social_settings['facebook'];

			if ( !isset( $facebook_settings->page ) || strtolower( $facebook_settings->page ) === 'me' ) {
				$account      = 'me';
				$access_token = $facebook_settings->access_token;
			} else {
				$page_info    = explode( '|', $facebook_settings->page );
				$account      = $page_info[2];
				$access_token = $page_info[1];
			}

			$url = 'https://graph.facebook.com/' . $account . '/feed?access_token=' . $access_token;
			$args = array( 'link' => $link, 'message' => $message );
			if ( !empty( $image ) ) {
				$args['picture'] = $image;
			}
			$results = wp_remote_post( $url, array( 'body' => $args ) );

			return $results;
		}

		/**
		 * Get Data From URL
		 *
		 * Handles to get data from url
		 * via CURL
		 *
		 */

		public function ppp_get_data_from_url( $url ) {

			//Comment out the curl code
			/*$ch = curl_init();
			$timeout = 5;
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
			$data = curl_exec( $ch );
			curl_close( $ch );
			return $data;*/

			//Use wp_remote_post and wp_remote_get
			$data	= wp_remote_retrieve_body( wp_remote_get( $url ) );

			return $data;
		}
	}
}
?>
