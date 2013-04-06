<?php


/**
 * This is copied directly from WPMU wp-includes/wpmu-functions.php
 */
if (!function_exists('validate_email')) {
	function validate_email($email, $check_domain = true) {
		if (ereg('^[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+' . '@' .
			'[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.' .
			'[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+$', $email)) {
			if ($check_domain && function_exists('checkdnsrr')) {
				list (, $domain) = explode('@', $email);

				if (checkdnsrr($domain . '.', 'MX') || checkdnsrr($domain . '.', 'A')) {
					return true;
				}
				return false;
			}
			return true;
		}
		return false;
	} // End of validate_email() function definition
}

if (!function_exists('has_HTML')) {
	function has_HTML($str) {
		if (strlen($str) != strlen(strip_tags($str))) {
			return true;
		} else {
			return false;
		}
	}
}
?>