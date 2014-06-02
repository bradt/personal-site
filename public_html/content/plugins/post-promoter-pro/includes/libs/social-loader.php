<?php
global $ppp_twitter_oauth, $ppp_bitly_oauth;

require_once( PPP_PATH . '/includes/libs/twitter.php');
$ppp_twitter_oauth = new PPP_Twitter();

require_once( PPP_PATH . '/includes/libs/bitly.php' );
$ppp_bitly_oauth = new PPP_Bitly();