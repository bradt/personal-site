<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Twitter Class
 *
 * Handles all twitter functions
 *
 */
if( !class_exists( 'PPP_Twitter' ) ) {

	class PPP_Twitter {

		var $twitter;

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
		public function ppp_load_twitter() {
				if( !class_exists( 'TwitterOAuth' ) ) {
					require_once ( PPP_PATH . '/includes/libs/twitter/twitteroauth.php' );
				}

				ppp_set_social_tokens();

				if ( ! defined( 'PPP_TW_CONSUMER_KEY' ) || ! defined( 'PPP_TW_CONSUMER_SECRET' ) ) {
					return false;
				}

				$this->twitter = new TwitterOAuth( PPP_TW_CONSUMER_KEY, PPP_TW_CONSUMER_SECRET );

				return true;
		}

		public function revoke_access() {
			global $ppp_social_settings;

			unset( $ppp_social_settings['twitter'] );

			update_option( 'ppp_social_settings', $ppp_social_settings );
		}

		/**
		 * Initializes Twitter API
		 *
		 */
		function ppp_initialize_twitter() {

			//when user is going to logged in in twitter and verified successfully session will create
			if ( isset( $_REQUEST['oauth_verifier'] ) && isset( $_REQUEST['oauth_token'] ) ) {

				//load twitter class
				$twitter = $this->ppp_load_twitter();

				//check twitter class is loaded or not
				if( !$twitter ) return false;

				$this->twitter = new TwitterOAuth( PPP_TW_CONSUMER_KEY, PPP_TW_CONSUMER_SECRET, $_SESSION['ppp_twt_oauth_token'], $_SESSION['ppp_twt_oauth_token_secret'] );

				// Request access tokens from twitter
				$ppp_tw_access_token = $this->twitter->getAccessToken($_REQUEST['oauth_verifier']);

				//session for verifier
				$verifier['oauth_verifier'] = $_REQUEST['oauth_verifier'];
				//EDD()->session->set( 'ppp_twt_user_cache', $verifier );

				$_SESSION[ 'ppp_twt_user_cache' ] = $verifier;

				//getting user data from twitter
				$response = $this->twitter->get('account/verify_credentials');

				//if user data get successfully
				if ( $response->id_str ) {

					$data['user'] = $response;
					$data['user']->accessToken = $ppp_tw_access_token;

					$ppp_social_settings['twitter'] = $data;
					update_option( 'ppp_social_settings', $ppp_social_settings );
				}
			}
		}

		public function ppp_verify_twitter_credentials() {
			$twitter = $this->ppp_load_twitter();

			global $ppp_social_settings;
			if ( isset( $ppp_social_settings['twitter'] ) ) {
				$this->twitter = new TwitterOAuth( PPP_TW_CONSUMER_KEY, PPP_TW_CONSUMER_SECRET,
												   $ppp_social_settings['twitter']['user']->accessToken['oauth_token'], $ppp_social_settings['twitter']['user']->accessToken['oauth_token_secret'] );
				$response = $this->twitter->get('account/verify_credentials');
				if ( property_exists( $response, 'errors' ) && count( $response->errors ) > 0 ) {
					foreach ( $response->errors as $error ) {
						if ( $error->code == 89 ) { // Expired or revoked tokens
							unset( $ppp_social_settings['twitter'] );
							update_option( 'ppp_social_settings', $ppp_social_settings );

							return array( 'error' => __( 'Post Promoter Pro has been removed from your Twitter account. Please reauthorize to continue promoting your content.', 'ppp-txt' ) );
						}
					}
				}
			}

			return true;
		}

		/**
		 * Get auth url for twitter
		 *
		 * @param Easy Digital Downloads - Social Login
		 * @since 1.0.0
		 */
		public function ppp_get_twitter_auth_url () {
			//load twitter class
			$twitter = $this->ppp_load_twitter();

			//check twitter class is loaded or not
			if( !$twitter ) return false;

			$request_token = $this->twitter->getRequestToken( admin_url( 'admin.php?page=ppp-social-settings' ) ); // get_permalink( $post->ID )

			// If last connection failed don't display authorization link.
			switch( $this->twitter->http_code ) { //

			  case 200:
				    	$_SESSION['ppp_twt_oauth_token'] = $request_token['oauth_token'];
				    	$_SESSION['ppp_twt_oauth_token_secret'] = $request_token['oauth_token_secret'];

				    	$token = $request_token['oauth_token'];
						$url = $this->twitter->getAuthorizeURL( $token, NULL );
				    	break;
			  default:
					    // Show notification if something went wrong.
					    $url = '';
			}
			return $url;
		}

		public function ppp_tweet( $message = '' ) {
			if ( empty( $message ) ) {
				return false;
			}

			$verify = $this->ppp_verify_twitter_credentials();
			if ( $verify === true ) {
				$args['status'] = $message;
				return $this->twitter->post( 'statuses/update', $args );
			} else {
				return false;
			}
		}

	}

}