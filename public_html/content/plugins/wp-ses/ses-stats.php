<?php

// TODO: add 15 min cache to stats
// TODO: add chart

if (!isset($wpses_options)) {
    $wpses_options = get_option('wpses_options');
}

if ($wpses_options['credentials_ok'] != 1) {
    $WPSESMSG = __('Amazon API credentials have not been checked.<br />Please go to settings -> WP SES and setup the plugin', 'wpses');
    include ('error.tmpl.php');
}

require_once (WP_PLUGIN_DIR . '/wp-ses/ses.class.0.8.4.php');
$SES = new SimpleEmailService($wpses_options['access_key'], $wpses_options['secret_key']);

if (!is_object($SES)) {
    $WPSESMSG = __('Error initializing SES. Please check your settings.', 'wpses');
    include ('error.tmpl.php');
}

$quota = $SES->getSendQuota();
$quota['SendRemaining'] = $quota['Max24HourSend'] - $quota['SentLast24Hours'];
if ($quota['Max24HourSend'] > 0) {
    $quota['SendUsage'] = round($quota['SentLast24Hours'] * 100 / $quota['Max24HourSend']);
} else {
    $quota['SendUsage'] = 0;
}

$stats = $SES->getSendStatistics();
usort($stats[SendDataPoints], 'wpses_stsort');


include ('stats.tmpl.php');

function wpses_stsort($a, $b) {
    if ($a['Timestamp'] < $b['Timestamp']) {
        return -1;
    }
    return 1;
}

?>