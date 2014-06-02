<?php

/**
 * Auth a user with bit.ly
 * @return mixed 1 if successful, 0 if general error, 'INVALID_LOGIN' if failed creds
 */
function ppp_get_bitly_auth() {
	global $ppp_bitly_oauth;
	ppp_set_social_tokens();
	$url = 'https://api-ssl.bitly.com/oauth/access_token';
	$username = $_POST['username'];
	$password = $_POST['password'];
	$headers = array( 'Authorization' => 'Basic ' . base64_encode( bitly_clientid . ':' . bitly_secret ) );
	$body = 'grant_type=password&username=' . $username . '&password=' . $password;
	$result = wp_remote_post( $url, array( 'headers' => $headers, 'body' => $body ) );
	$body = wp_remote_retrieve_body( $result );
	$data = json_decode( $body );

	if ( isset( $data->access_token ) ) {
		global $ppp_social_settings;
		$ppp_social_settings['bitly']['access_token'] = $data->access_token;

		$user_data = json_decode( $ppp_bitly_oauth->ppp_bitly_user_info(), true );

		$ppp_social_settings['bitly']['login'] = $user_data['data']['login'];
		$ppp_social_settings['bitly']['avatar'] = $user_data['data']['profile_image'];

		update_option( 'ppp_social_settings', $ppp_social_settings );
		echo 1;
	} elseif ( $body == 'INVALID_LOGIN' ) {
		echo 'INVALID_LOGIN';
	} else {
		echo 0;
	}

	die(); // this is required to return a proper result
}
add_action( 'wp_ajax_ppp_bitly_connect', 'ppp_get_bitly_auth' );