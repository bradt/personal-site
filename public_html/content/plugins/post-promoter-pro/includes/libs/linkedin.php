<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Linkedin Class
 *
 * Handles all linkedin functions
 *
 */
if( !class_exists( 'PPP_Linkedin' ) ) {

	class PPP_Linkedin {

		var $linkedin;

		public function __construct(){
			ppp_maybe_start_session();
		}

		/**
		 * Include Linkedin Class
		 *
		 * Handles to load linkedin class
		 */
		public function ppp_load_linkedin() {

				if( !class_exists( 'LinkedIn' ) ) {
					require_once ( PPP_PATH . '/includes/libs/linkedin/linkedin_oAuth.php' );
				}


				ppp_set_social_tokens();

				if ( ! defined( 'LINKEDIN_KEY' ) || ! defined( 'LINKEDIN_SECRET' ) ) {
					return false;
				}

				global $ppp_social_settings;
				$config = array( 'appKey' => LINKEDIN_KEY, 'appSecret' => LINKEDIN_SECRET );
				if ( isset( $ppp_social_settings['linkedin']->access_token ) ) {
					$config['accessToken'] = $ppp_social_settings['linkedin']->access_token;
				}

				if ( !$this->linkedin ) {
					$this->linkedin = new LinkedIn( $config );
				}

				return true;
		}

		/**
		 * Initializes Linkedin API
		 *
		 */
		function ppp_initialize_linkedin() {
			//when user is going to logged in in linkedin and verified successfully session will create
			if ( isset( $_REQUEST['li_access_token'] ) && isset( $_REQUEST['expires_in'] ) ) {
				global $ppp_social_settings;
				$ppp_social_settings = get_option( 'ppp_social_settings' );

				//load linkedin class
				$linkedin = $this->ppp_load_linkedin();

				//check linkedin class is loaded or not
				if( !$linkedin ) return false;

				$data = new stdClass();
				$data->access_token = $_REQUEST['li_access_token'];

				$expires_in = (int) $_REQUEST['expires_in'];
				$data->expires_on = current_time( 'timestamp' ) + $expires_in;

				update_option( '_ppp_linkedin_refresh', current_time( 'timestamp' ) + round( $expires_in/1.25 ) );

				$ppp_social_settings['linkedin'] = $data;
				update_option( 'ppp_social_settings', $ppp_social_settings );

				// Now that we have a valid auth, get some user info
				$user_info = json_decode( $this->ppp_linkedin_profile() );

				$ppp_social_settings['linkedin']->firstName = $user_info->firstName;
				$ppp_social_settings['linkedin']->lastName  = $user_info->lastName;
				$ppp_social_settings['linkedin']->headline  = $user_info->headline;

				update_option( 'ppp_social_settings', $ppp_social_settings );

				$url = remove_query_arg( array( 'li_access_token' , 'expires_in' ) );
				wp_redirect( $url );
				die();
			}
		}

		/**
		 * Get auth url for linkedin
		 *
		 */
		public function ppp_get_linkedin_auth_url ( $return_url ) {
			$base_url = 'https://postpromoterpro.com/?ppp-social-auth';
			$url  = $base_url . '&ppp-service=li&ppp-license-key=' . trim( get_option( '_ppp_license_key' ) );
			$url .= '&nocache';
			$url .= '&return_url=' . esc_url( $return_url );

			return $url;
		}

		/**
		 * Share somethign on linkedin
		 */
		public function ppp_linkedin_share( $args ) {
			if ( empty( $args ) ) {
				return false;
			}

			$this->ppp_load_linkedin();
			global $ppp_social_settings;
			$url = 'https://api.linkedin.com/v1/people/~/shares?oauth2_access_token=' . $ppp_social_settings['linkedin']->access_token;
			$share = array(
						'content' => array(
							'title' => $args['title'],
							'description' => $args['description'],
							'submitted-url' => $args['submitted-url']
						),
						'visibility' => array(
							'code' => 'anyone'
						)
						);

			if ( $args['submitted-image-url'] !== false ) {
				$share['content']['submitted-image-url'] = $args['submitted-image-url'];
			}


			$headers = array( 'x-li-format' => 'json', 'Content-Type' => 'application/json' );
			$body = json_encode( $share );

			return wp_remote_retrieve_body( wp_remote_post( $url, array( 'headers' => $headers, 'body' => $body ) ) );
		}

		public function ppp_linkedin_profile() {

			$this->ppp_load_linkedin();
			global $ppp_social_settings;
			$url = 'https://api.linkedin.com/v1/people/~?oauth2_access_token=' . $ppp_social_settings['linkedin']->access_token;

			$headers = array( 'x-li-format' => 'json', 'Content-Type' => 'application/json' );

			return wp_remote_retrieve_body( wp_remote_get( $url, array( 'headers' => $headers ) ) );
		}
	}

}
