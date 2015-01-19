<?php

/*
  Plugin Name: WP SES
  Version: 0.3.56
  Plugin URI: http://wp-ses.com
  Description: Uses Amazon Simple Email Service instead of local mail for all outgoing WP emails.
  Author: Sylvain Deaure
  Author URI: http://www.blog-expert.fr
 */

define('WPSES_VERSION', 0.356);

// refs
// http://aws.amazon.com/fr/
//
// 0.3.48 : Experimental WP Better Email compatibility
// 0.3.46 : Remove notices, updated old code.
// 0.3.42 : Spanish translation
// 0.3.4 : auto activation via WP_SES_AUTOACTIVATE define
// 0.3.1 : Reply_to and global WPMU setup
// 0.3 : Last WP update
// 0.2.2 : Reference language is now english
// 0.2.1 : return-path | stats | Quota | Deactivate plugin | Production mode test
// 0.1.2
// TODO
// stats cache (beware of directory)
// logs of mails sent (inc details ?)
// traiter les erreurs (stocker contenu) pour les re-tenter plus tard ?
// Mailqueue
// limits (check once per hour (or  faster) and stop if near limit)
// blacklist, mail delivery handling
// dashboard integration (main stats without extra page)
// Add error display for test messages
// add attachments (contact form 7) : see https://github.com/daniel-zahariev/php-aws-ses

if (defined('WP_SES_ACCESS_KEY') and defined('WP_SES_SECRET_KEY')) {
    define('WP_SES_RESTRICTED', true);
} else {
    define('WP_SES_RESTRICTED', false);
}

if (is_admin()) {
    // TODO : Ask before activate
    // include_once(WP_PLUGIN_DIR.'/wp-ses/sdrssw.php');
    add_action('init', 'wpses_init');
    add_action('admin_menu', 'wpses_admin_menu');
    register_activation_hook(__FILE__, 'wpses_install');
    register_deactivation_hook(__FILE__, 'wpses_uninstall');
}
require_once (WP_PLUGIN_DIR . '/wp-ses/ses.class.0.8.4.php');

function wpses_init() {
    load_plugin_textdomain('wpses', false, basename(dirname(__FILE__)));
    wpses_admin_warnings();
}

add_filter('wp_mail_from', 'wpses_from', 1);
add_filter('wp_mail_from_name', 'wpses_from_name', 1);

function wpses_install() {
    global $wpdb, $wpses_options;
    if (!get_option('wpses_options')) {
        add_option('wpses_options', array(
            'from_email' => '',
            'return_path' => '',
            'from_name' => 'WordPress',
            'access_key' => '',
            'secret_key' => '',
            'endpoint' => 'email.us-east-1.amazonaws.com',
            'credentials_ok' => 0,
            'sender_ok' => 0,
            'last_ses_check' => 0, // timestamp of last quota check
            'force' => 0,
            'active' => 0, // reset to 0 if not pluggable or config change.
            'version' => '0' // Version of the db
                // TODO: garder liste des ids des demandes associ�es � chaque email.
                // afficher : email, id demande , valid� ?
        ));
        wpses_getoptions();
        //$wpses_options = get_option('wpses_options');
    }
}

function wpses_options() {
    global $wpdb, $wpses_options;
    global $current_user;
    get_currentuserinfo();
    if (!in_array('administrator', $current_user->roles)) {
        //die('Pas admin');
    }
    $autorized = '';
    if (($wpses_options['access_key'] != '') and ($wpses_options['secret_key'] != '')) {
        $autorized = wpses_getverified();
    }
    $senders = (array) get_option('wpses_senders');
    // ajouter dans senders les verified absents
    $updated = false;
    if ('' != $autorized) {
        if (!is_array($autorized)) {
            $authorized = array($autorized);
        }
        foreach ($autorized as $email) {
            if (!array_key_exists($email, $senders)) {
                $senders[$email] = array(
                    -1,
                    TRUE
                );
                $updated = true;
            } else {
                if (!$senders[$email][1]) {
                    // activer ceux qu'on a reçu depuis
                    $senders[$email][1] = true;
                    $updated = true;
                }
            }
        }
        // remove old senders
        foreach ($senders as $email => $info) {
            if ($info[1] and !in_array($email, $autorized)) {
                $senders[$email][1] = false;
                // echo 'remove '.$email.' ';
                $updated = true;
            }
        }
    }

    if ($updated) {
        update_option('wpses_senders', $senders);
    }
    if ((($wpses_options['sender_ok'] != 1) and $wpses_options['force'] != 1) or ($wpses_options['credentials_ok'] != 1)) {
        $wpses_options['active'] = 0;
        update_option('wpses_options', $wpses_options);
    }
    if (($wpses_options['from_email'] != '')) {
        if (!isset($senders[$wpses_options['from_email']])) {
            $senders[$wpses_options['from_email']] = array(-1, false);
        }
        if ($senders[$wpses_options['from_email']][1] === TRUE) { //
            // email exp enregistré non vide et listé, on peut donc supposer que credentials ok et exp ok.
            if ($wpses_options['credentials_ok'] == 0) {
                $wpses_options['credentials_ok'] = 1;
                update_option('wpses_options', $wpses_options);
            }
            if ($wpses_options['sender_ok'] == 0) {
                $wpses_options['sender_ok'] = 1;
                update_option('wpses_options', $wpses_options);
            }
        } else {
            //if ($senders[$wpses_options['from_email']][1] !== TRUE) { //
            $wpses_options['sender_ok'] = 0;
            update_option('wpses_options', $wpses_options);
        }
    }

    if (!empty($_POST['activate'])) {
        $wpses_options['force'] = 0;
        if (1 == $_POST['force']) {
            // bad hack to force plugin activation with IAM credentials
            $wpses_options['sender_ok'] == 1;
            $wpses_options['force'] = 1;
        }
        if (($wpses_options['sender_ok'] == 1) and ($wpses_options['credentials_ok'] == 1)) {
            $wpses_options['active'] = 1;
            update_option('wpses_options', $wpses_options);
            echo '<div id="message" class="updated fade">
							<p>' . __('Plugin is activated and functionnal', 'wpses') . '</p>
							</div>' . "\n";
        }
    }
    if (!empty($_POST['deactivate'])) {
        $wpses_options['active'] = 0;
        update_option('wpses_options', $wpses_options);
        echo '<div id="message" class="updated fade">
							<p>' . __('Plugin de-activated', 'wpses') . '</p>
							</div>' . "\n";
    }
    if (!empty($_POST['save'])) {
        //check_admin_referer();
        //$wpses_options['active'] = trim($_POST['active']);
        if ($wpses_options['from_email'] != trim($_POST['from_email'])) {
            $wpses_options['sender_ok'] = 0;
            $wpses_options['active'] = 0;
        }
        if (!defined('WP_SES_FROM')) {
            $wpses_options['from_email'] = trim($_POST['from_email']);
        }
        if (!defined('WP_SES_RETURNPATH')) {
            $wpses_options['return_path'] = trim($_POST['return_path']);
        }
        if ($wpses_options['return_path'] == '') {
            $wpses_options['return_path'] = $wpses_options['from_email'];
        }
        if (!defined('WP_SES_REPLYTO')) {
            $wpses_options['reply_to'] = trim($_POST['reply_to']);
            if ($wpses_options['reply_to'] == '') {
                $wpses_options['reply_to'] = $wpses_options['from_email'];
            }
        }
        $wpses_options['from_name'] = trim($_POST['from_name']); //
        $wpses_options['endpoint'] = trim($_POST['endpoint']); //
        // TODO si mail diff�re, relancer proc�dure check => resetter sender_ok si besoin

        if (($wpses_options['access_key'] != trim($_POST['access_key'])) or ($wpses_options['secret_key'] != trim($_POST['secret_key']))) {
            $wpses_options['credentials_ok'] = 0;
            $wpses_options['sender_ok'] = 0;
            $wpses_options['active'] = 0;
        }
        if (!WP_SES_RESTRICTED) {
            $wpses_options['access_key'] = trim($_POST['access_key']); //
            $wpses_options['secret_key'] = trim($_POST['secret_key']); //
        }
        // TODO si credentials different, resetter credentials_ok

        update_option('wpses_options', $wpses_options);
        echo '<div id="message" class="updated fade"><p>' . __('Settings updated', 'wpses') . '</p></div>' . "\n";
    }
    wpses_getoptions();
    //$wpses_options = get_option('wpses_options');
    // validation cle amazon
    if (!WP_SES_RESTRICTED) { // server side check.
        // validation email envoi
        if (!empty($_POST['addemail'])) {
            wpses_verify_sender_step1($wpses_options['from_email']);
        }
        // remove verified email
        if (!empty($_POST['removeemail'])) {
            wpses_remove_sender($_POST['email']);
        }
        // envoi mail test
        if (!empty($_POST['testemail'])) {
            wpses_test_email($wpses_options['from_email']);
        }
        // envoi mail test prod
        if (!empty($_POST['prodemail'])) {
            wpses_prod_email($_POST['prod_email_to'], $_POST['prod_email_subject'], $_POST['prod_email_content']);
        }
    }
    include ('admin.tmpl.php');
}

// TODO
function wpses_uninstall() {
    // delete_option('wpses_options');
    // Do not delete, else we loose the version number
    // TODO: add an uninstall link ? Not a big deal since we added very little overhead
}

function wpses_admin_warnings() {
    global $wpses_options;
    if (!function_exists('curl_version')) {

        function wpses_curl_warning() {
            global $wpses_options;
            echo "<div id='wpses-curl-warning' class='updated fade'><p><strong>" . __("WP SES - CURL extension not available. SES Won't work without Curl. Ask your host.", 'wpses') . "</strong></p></div>";
        }

        add_action('admin_notices', 'wpses_curl_warning');
        return;
    }
    $active = $wpses_options['active'];
    if ($active <= 0) {

        function wpses_warning() {
            global $wpses_options;
            echo "<div id='wpses-warning' class='updated fade'><p><strong>" . __("WP SES - Simple Email Service is not fully activated. Please check it's config: ", 'wpses') .
            '<a href="options-general.php?page=wp-ses/wp-ses.php">' . __("Settings &rarr; WP SES", 'wpses') . '</a>.' . "</strong></p></div>";
        }

        add_action('admin_notices', 'wpses_warning');
        return;
    }
}

function wpses_admin_menu() {
    add_options_page('wpses', __('WP SES', 'wpses'), 'manage_options', __FILE__, 'wpses_options');
    // Quota and Stats
    if (!defined('WP_SES_HIDE_STATS') or (false == WP_SES_HIDE_STATS)) {
        add_submenu_page('index.php', 'SES Stats', 'SES Stats', 'manage_options', 'wp-ses/ses-stats.php');
    }
}

function wpses_from($mail_from_email) {
    global $wpses_options;
    $from_email = $wpses_options['from_email'];
    if (empty($from_email)) {
        return $mail_from_email;
    } else {
        return $from_email;
    }
}

function wpses_from_name($mail_from_name) {
    global $wpses_options;
    $from_name = $wpses_options['from_name'];
    if (empty($from_name)) {
        return $mail_from_name;
    } else {
        return $from_name;
    }
}

function wpses_message_step1done() {
    global $WPSESMSG;
    echo "<div id='wpses-warning' class='updated fade'><p><strong>" . __("A confirmation request has been sent. You will receive at the stated email a confirmation request from amazon SES. You MUST click on the provided link in order to confirm your sender Email.<br />SES Answer - ", 'wpses') . $WPSESMSG . "</strong></p></div>";
}

function wpses_getverified() {
    global $wpses_options;
    global $SES;
    wpses_check_SES();
    @ $result = $SES->listVerifiedEmailAddresses();
    if (is_array($result)) {
        return $result['Addresses'];
    } else {
        return NULL;
    }
}

function wpses_check_SES() {
    global $wpses_options;
    global $SES;
    if (!isset($SES)) {
        $SES = new SimpleEmailService($wpses_options['access_key'], $wpses_options['secret_key'], $wpses_options['endpoint']);
    }
}

function wpses_error_handler($level, $message, $file, $line, $context) {
    global $WPSESMSG;
    $WPSESMSG = __('SES Error: ', 'wpses') . $message;
    return (true); //And prevent the PHP error handler from continuing
}

// start email verification (mail from amazon to sender, requesting validation)
function wpses_verify_sender_step1($mail) {
    global $wpses_options;
    global $SES, $WPSESMSG;
    wpses_check_SES();
    $WPSESMSG = '';
    // dans la chaine : Sender - InvalidClientTokenId  si auth pas correct
    // Sender - OptInRequired
    // The AWS Access Key Id needs a subscription for the service: si cl� aws ok, mais pas d'abo au service amazon lors de la verif d'un mail
    // inscription depuis aws , verif phone.
    //Use our custom handler
    //set_error_handler('wpses_error_handler');
    try {
        $rid = $SES->verifyEmailAddress($mail);
        $senders = get_option('wpses_senders');
        if ($rid <> '') {
            $senders[$mail] = array(
                $rid['RequestId'],
                false
            );
            $wpses_options['credentials_ok'] = 1;
            update_option('wpses_options', $wpses_options);
            update_option('wpses_senders', $senders);
        }
        //echo("rid ");
        //print_r($rid);
    } catch (Exception $e) {
        $WPSESMSG = __('Got exception: ', 'wpses') . $e->getMessage() . "\n";
    }
    //restore_error_handler();
    $WPSESMSG .= ' id ' . var_export($rid, true);
    wpses_message_step1done();
    //	add_action('admin_notices', 'wpses_message_step1done'); // no : too late for this !
}

function wpses_remove_sender($mail) {
    global $wpses_options;
    global $SES, $WPSESMSG;
    wpses_check_SES();
    $WPSESMSG = '';
    $rid = $SES->deleteVerifiedEmailAddress($mail);
    $WPSESMSG .= ' id ' . var_export($rid, true);
    echo "<div id='wpses-warning' class='updated fade'><p><strong>" . $mail . '<br />' . __("This email address has been removed from verified senders.", 'wpses') . "</strong></p></div>";
}

function wpses_message_testdone() {
    global $WPSESMSG;
    echo "<div id='wpses-warning' class='updated fade'><p><strong>" . __("Test message has been sent to your sender Email address.<br />SES Answer - ", 'wpses') . $WPSESMSG . "</strong></p></div>";
}

function wpses_test_email($mail) {
    global $wpses_options;
    global $SES, $WPSESMSG;
    wpses_check_SES();
    $WPSESMSG = '';
    $rid = wpses_mail($wpses_options['from_email'], __('WP SES - Test Message', 'wpses'), __("This is WP SES Test message. It has been sent via Amazon SES Service.\nAll looks fine !\n\n", 'wpses') . __('WP SES is a plugin by', 'wpses') . ' http://www.blog-expert.fr/');
    $WPSESMSG .= ' id ' . var_export($rid, true);
    wpses_message_testdone();
}

function wpses_prod_email($mail, $subject, $content) {
    global $wpses_options;
    global $SES, $WPSESMSG;
    wpses_check_SES();
    $WPSESMSG = '';
    $rid = wpses_mail($mail, $subject, $content);
    $WPSESMSG .= ' id ' . var_export($rid, true);
    echo "<div id='wpses-warning' class='updated fade'><p><strong>" . __("Test message has been sent.<br />SES Answer - ", 'wpses') . $WPSESMSG . "</strong></p></div>";
}

// returns msg id
function wpses_mail($to, $subject, $message, $headers = '', $attachments = '') {
    global $SES;
    global $wpses_options;
    global $wp_better_emails;
    // headers can be sent as array, too. convert them to string to avoid further complications.
    if (is_array($headers)) {
        $headers = implode("\r\n", $headers);
    }
    extract(apply_filters('wp_mail', compact('to', 'subject', 'message', 'headers')));
    wpses_check_SES();
    if (isset($wp_better_emails)) {
        // From wpbe plugin, not efficient nor elegant - Will do better next time.
        // Could just call the php filter on a adhoc object, to be less dependant on the implementation of wpbe code.
        $txt = wp_specialchars_decode($message, ENT_QUOTES);
        $txt = $wp_better_emails->set_email_template($txt, 'plaintext_template');
        $txt = apply_filters('wpbe_plaintext_body', $wp_better_emails->template_vars_replacement($txt));
        /** HTML ******************************************************* */
        $html = $wp_better_emails->esc_textlinks($message);
        $html = nl2br(make_clickable($html));
        $html = $wp_better_emails->set_email_template($html);
        $html = apply_filters('wpbe_html_body', $wp_better_emails->template_vars_replacement($html));
    } else {
        $message = preg_replace('/<(http:.*)>/', '$1', $message);
        $message = preg_replace('/<(https:.*)>/', '$1', $message); // bad hack - handle httpS as well.
        $html = $message;
        $txt = strip_tags($html);
        if (strlen($html) == strlen($txt)) {
            $html = ''; // que msg text
        }
        // no html entity in txt.
        $txt = html_entity_decode($txt, ENT_NOQUOTES, 'UTF-8');
    }
    // TODO: option pour que TXT si msg html, ou les deux comme ici par défaut.
    $m = new SimpleEmailServiceMessage();
    // To: may contain comma separated emails. If so, explode and add them all.
    // what to do if more than 50 ? (SES limit)
    if (preg_match('/,/im',$to)) {
        $to=explode(',',$to);
        foreach($to as $toline) {
             $m->addTo($toline);
        }
    } else {
        $m->addTo($to);
    }
    $m->setReturnPath($wpses_options['return_path']);
    $from = $wpses_options['from_name'] . ' <' . $wpses_options['from_email'] . '>';
    if ('' != $wpses_options['reply_to']) {
        if ('headers' == strtolower($wpses_options['reply_to'])) {
            // extract replyto from headers
            $rto = array();
            if (preg_match('/^Reply-To: ([a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4})\b/imsU', $headers, $rto)) {
                // does only support one email for now.
                $m->addReplyTo($rto[1]);
            }
            if (preg_match('/^From: (.*)/im', $headers, $rto)) {
                // Uses "From:" header - was /isU which broke things, see https://wordpress.org/support/topic/gravity-forms-18205-latest-contact-form-7-403-latest-not-working
                $from = $rto[1];
            }
            // Should we handle cc and bcc: from headers too ? Guess so... TODO
        } else {
            $m->addReplyTo($wpses_options['reply_to']);
        }
    }
    $m->setFrom($from);
    $m->setSubject($subject);
    if ($html == '') { // que texte
        $m->setMessageFromString($txt);
    } else {
        $m->setMessageFromString($txt, $html);
    }
    // Attachments
    if ('' != $attachments) {
        if (!is_array($attachments)) {
            $attachments = explode("\n", $attachments);
        }
        // Now we got an array
        foreach ($attachments as $afile) {
            $m->addAttachmentFromFile(basename($afile), $afile);
        }
    }
        
    $res = $SES->sendEmail($m);
    if (is_array($res)) {
        return $res['MessageId'];
    } else {
        return NULL;
    }
}

function wpses_getoptions() {
    global $wpses_options;
    $wpses_options = get_option('wpses_options');
    if (!is_array($wpses_options)) {
        $wpses_options = array();
    }
    if (!array_key_exists('reply_to', $wpses_options)) {
        $wpses_options['reply_to'] = '';
    }
    if (!array_key_exists('force', $wpses_options)) {
        $wpses_options['force'] = '0';
    }
    if (defined('WP_SES_ENDPOINT')) {
        $wpses_options['endpoint'] = WP_SES_ENDPOINT;
    }
    if (!isset($wpses_options['endpoint'])) {
        $wpses_options['endpoint'] = 'email.us-east-1.amazonaws.com';
    }
    if ('' == $wpses_options['endpoint']) {
        $wpses_options['endpoint'] = 'email.us-east-1.amazonaws.com';
    }
    if (defined('WP_SES_ACCESS_KEY')) {
        $wpses_options['access_key'] = WP_SES_ACCESS_KEY;
    }
    if (defined('WP_SES_SECRET_KEY')) {
        $wpses_options['secret_key'] = WP_SES_SECRET_KEY;
    }
    if (defined('WP_SES_RETURNPATH')) {
        if ('' != WP_SES_RETURNPATH) {
            $wpses_options['return_path'] = WP_SES_RETURNPATH;
        }
    }
    if (defined('WP_SES_FROM')) {
        if ('' != WP_SES_FROM) {
            $wpses_options['from_email'] = WP_SES_FROM;
        }
    }
    if (defined('WP_SES_REPLYTO')) {
        if ('' != WP_SES_REPLYTO) {
            $wpses_options['reply_to'] = WP_SES_REPLYTO;
        }
    }
    if (defined('WP_SES_AUTOACTIVATE')) {
        if (WP_SES_AUTOACTIVATE) {
            $wpses_options['active'] = 1;
        }
    }
}

global $wpses_options;
if (!isset($wpses_options)) {
    wpses_getoptions();
}

if ($wpses_options['active'] == 1) {
    if (!function_exists('wp_mail')) {

        function wp_mail($to, $subject, $message, $headers = '', $attachments = '') {
            global $wpses_options;
            $id = wpses_mail($to, $subject, $message, $headers, $attachments);
            if ($id != '') {
                return true;
            } else {
                return false;
            }
        }

    } else {

        function wpses_warningmail() {
            echo "<div id='wpses-warning' class='updated fade'><p><strong>" . __('Another plugin did override wp-mail function. Please de-activate the other plugin if you want WP SES to work properly.', 'wpses') . "</strong></p></div>";
        }

        add_action('admin_notices', 'wpses_warningmail');
        // Desactiver "active" si actif.
        if ($wpses_options['active'] == 1) {
            $wpses_options['active'] = 0;
            update_option('wpses_options', $wpses_options);
        }
    }
    $SES = new SimpleEmailService($wpses_options['access_key'], $wpses_options['secret_key'], $wpses_options['endpoint']);
}

$WPSESMSG = '';