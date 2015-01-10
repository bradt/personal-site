<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

include_once( PPP_PATH . '/includes/twitter-functions.php' );
include_once( PPP_PATH . '/includes/facebook-functions.php' );
include_once( PPP_PATH . '/includes/linkedin-functions.php' );
include_once( PPP_PATH . '/includes/bitly-functions.php' );
include_once( PPP_PATH . '/includes/wpme-functions.php' );

global $ppp_twitter_oauth, $ppp_facebook_oauth, $ppp_bitly_oauth, $ppp_linkedin_oauth;

require_once( PPP_PATH . '/includes/libs/twitter.php');
$ppp_twitter_oauth = new PPP_Twitter();

require_once( PPP_PATH . '/includes/libs/facebook.php');
$ppp_facebook_oauth = new PPP_Facebook();

require_once( PPP_PATH . '/includes/libs/bitly.php' );
$ppp_bitly_oauth = new PPP_Bitly();

require_once( PPP_PATH . '/includes/libs/linkedin.php' );
$ppp_linkedin_oauth = new PPP_Linkedin();
