<div class="wrap">
    <div id="icon-options-general" class="icon32"><br /></div>
    <h2><?php _e('WP SES Options', 'wpses') ?></h2>
    <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
        <?php wp_nonce_field('wpses'); ?>


        <h3><?php _e('Plugin State', 'wpses') ?>&nbsp;<input type="submit" name="refresh" value="<?php _e('Refresh', 'wpses') ?>" /></h3>
    </form>  
    <div style="border:1px solid#ccc; padding:10px; float:right; ">
        Don't forget to check online FAQs on <a href="http://wp-ses.com/" target="_blank">WP-SES</a> website.<br />
        We also provide usefull tips on email delivrability<br />and successfull list building.
    </div>
    <ul>
        <?php
        if ($wpses_options['from_email'] != '') {
            echo('<li style="color:#0f0;">');
            _e("Sender Email is set ", 'wpses');
        } else {
            echo('<li style="color:#f00;">');
            _e("Sender Email is not set ", 'wpses');
        }
        ?></li>
        <?php
        if ($wpses_options['credentials_ok'] == 1) {
            echo('<li style="color:#0f0;">');
            _e("Amazon API Keys are valid", 'wpses');
        } else {
            echo('<li style="color:#f00;">');
            _e("Amazon API Keys are not valid, or you did not finalize your Amazon SES registration.", 'wpses');
        }
        ?></li>
        <?php
        if (($wpses_options['from_email'] != '') and ($senders[$wpses_options['from_email']][1])) {
            echo('<li style="color:#0f0;">');
            _e("Sender Email has been confirmed.", 'wpses');
        } else {
            echo('<li style="color:#f00;">');
            _e("Sender Email has not been confirmed yet.", 'wpses');
        }
        ?></li>  	

        <?php
        if ($wpses_options['active'] == 1) {
            echo('<li style="color:#0f0;">');
            _e("Plugin is active.", 'wpses');
            echo("<br /><b>");
            if (!defined('WP_SES_HIDE_STATS') or (false == WP_SES_HIDE_STATS)) {
                _e('You can check your sending limits and stats under Dashboard -> SES Stats', 'wpses');
            }
            echo("</b>");
            ?><form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
                <?php wp_nonce_field('wpses'); ?>
                <p class="submit">
                    <input type="submit" name="deactivate" value="<?php _e('De-activate Plugin', 'wpses') ?>" />
                </p><?php _e('If you want to test further, de-activate the plugin here. Outgoing mails will be delivered by the default wordpress method, but you\'ll still be able to test custom SES email delivery.', 'wpses') ?>
            </form>
            <?php
        } else {
            echo('<li style="color:#f00;">');
            _e("Plugin is not active.", 'wpses');
            ?>
            <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
                <?php wp_nonce_field('wpses'); ?>
                <?php _e("Ignore sender email and force activation.", 'wpses'); ?><input  type="checkbox" name="force" value="1" /><br />
                <small>Check this if you use IAM credentials, have validated sender emails for the SES endpoint you are using, production Email test is OK but you can't activate the plugin.</small>
                <p class="submit">
                    <input type="submit" name="activate" value="<?php _e('Activate plugin', 'wpses') ?>" />
                </p><?php _e('Warning: Activate only if your account is in production mode.<br />One activated, all outgoing emails will go through Amazon SES and will NOT be sent to any email while in sandbox.', 'wpses') ?>
            </form>  		
        <?php } ?></li>


    </ul>
    <h3><?php _e('Sender Email', 'wpses') ?></h3>
    <?php _e('These settings do replace default sender email used by your blog.', 'wpses') ?>
    <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
        <?php wp_nonce_field('wpses'); ?>
        <table class="form-table">
            <tr><th scope="row"><?php _e('Sender Email', 'wpses') ?></th>
                <td>
                    <?php if (!defined('WP_SES_FROM')) { ?>
                        <input type="text" name="from_email" value="<?php echo $wpses_options['from_email']; ?>" />&nbsp;<?php _e('(Has to be a valid Email)', 'wpses') ?>
                        <?php
                    } else {
                        echo('(' . WP_SES_FROM . ') ');
                        _e('From: Email was defined by your admin.', 'wp-ses');
                    }
                    ?>
                </td></tr>
            <tr><th scope="row"><?php _e('Name', 'wpses') ?></th>
                <td><input type="text" name="from_name" value="<?php echo $wpses_options['from_name']; ?>" /></td></tr>
            <tr><th scope="row"><?php _e('Return Path', 'wpses') ?></th>
                <td>
                    <?php if (!defined('WP_SES_RETURNPATH')) { ?>
                        <input type="text" name="return_path" value="<?php echo $wpses_options['return_path']; ?>" />&nbsp;<?php _e('You can specify a return Email (not required).<br />Delivery Status notification messages will be sent to this address.', 'wpses') ?>
                        <?php
                    } else {
                        echo('(' . WP_SES_RETURNPATH . ') ');
                        _e('Return path was defined by your admin.', 'wp-ses');
                    }
                    ?>
                </td></tr>
            <tr><th scope="row"><?php _e('Reply To', 'wpses') ?></th>
                <td>
                    <?php if (!defined('WP_SES_REPLYTO') or ('' == WP_SES_REPLYTO)) { ?>
                        <input type="text" name="reply_to" value="<?php echo $wpses_options['reply_to']; ?>" />&nbsp;<?php _e('You can specify a reply To Email (not required).<br />Replies to your messages will be sent to this address.<br />set to "headers" to extract Reply-to from email headers.', 'wpses') ?>
                        <?php
                    } else {
                        echo('(' . WP_SES_REPLYTO . ') ');
                        _e('Reply To was defined by your admin.', 'wp-ses');
                    }
                    ?>
                </td></tr>
        </table>

        <h3><?php _e("Amazon API Keys", 'wpses') ?></h3>
        <?php if (!WP_SES_RESTRICTED) { ?>
            <div style="border:1px solid#ccc; padding:10px; float:right; ">
                If you already use an Amazon Webservice like S3,<br />
                you can use the very same keys here.
            </div>
            <?php _e('Please insert here your API keys given by the Amazon Web Services.', 'wpses') ?>
            <table class="form-table" style="width:450px; float:left;" width="450">
                <tr><th scope="row"><?php _e('access_key', 'wpses') ?></th>
                    <td><input type="text" name="access_key" value="<?php echo $wpses_options['access_key']; ?>" /></td></tr>
                <tr><th scope="row"><?php _e('secret_key', 'wpses') ?></th>
                    <td><input type="text" name="secret_key" value="<?php echo $wpses_options['secret_key']; ?>" /></td></tr>

                <tr><th scope="row"><?php _e('SES Endpoint', 'wpses') ?></th>
                    <td><select name="endpoint">
                            <option value="email.us-east-1.amazonaws.com" <?php if ('email.us-east-1.amazonaws.com' == $wpses_options['endpoint']) {
            echo 'selected';
        } ?>>US East (N. Virginia) Region</option>
                            <option value="email.us-west-2.amazonaws.com" <?php if ('email.us-west-2.amazonaws.com' == $wpses_options['endpoint']) {
            echo 'selected';
        } ?>>US West (Oregon) Region</option>
                            <option value="email.eu-west-1.amazonaws.com" <?php if ('email.eu-west-1.amazonaws.com' == $wpses_options['endpoint']) {
            echo 'selected';
        } ?>>EU (Ireland) Region</option>
                        </select>
                      </td></tr>
                <tr><th scope="row">&nbsp;</th>
                    <td><?php _e('You\'ll need to validate sender emails for each Endpoint you want to use','wpses') ?></td></tr>

            </table>
<?php } else { // restricted access   ?>
    <?php _e('Amazon Web Services API info has already been filled in by your administrator.', 'wpses') ?>
<?php } ?>
        <input type="hidden" name="action" value="update" />
        <!-- input type="hidden" name="page_options" value="wpses_options" / -->
        <p class="submit" style="clear:both">
            <input type="submit" name="save" value="<?php _e('Save Changes') ?>" />
        </p>
    </form>
    <br />&nbsp;
    <?php if (!defined('WP_SES_HIDE_VERIFIED') or (false == WP_SES_HIDE_VERIFIED)) { ?>
        <h3><?php _e("Confirmed senders", 'wpses') ?></h3>
        <?php _e('Only confirmed senders are able to send an email via SES', 'wpses') ?><br />
    <?php _e('The following senders are known:', 'wpses') ?>
        <br />
                <?php
                //print_r($autorized); 
                //$senders
                ?>
        <div style="width:70%">
            <table class="form-table">
                <tr style="background-color:#ccc; font-weight:bold;"><td><?php _e('Email', 'wpses') ?></td><td><?php _e('Request Id', 'wpses') ?></td><td><?php _e('Confirmed', 'wpses') ?></td><td><?php _e('Action', 'wpses') ?></td></tr>
                <?php
                $i = 0;
                foreach ($senders as $email => $props) {
                    if ($i % 2 == 0) {
                        $color = ' style="background-color:#ddd"';
                    } else {
                        $color = '';
                    }
                    echo("<tr $color>");
                    echo("<td>$email</td>");
                    echo("<td>");
                    print_r($props[0]);
                    echo("</td>");
                    if ($props[1]) {
                        $valide = __('Yes', 'wpses');
                    } else {
                        $valide = __('No', 'wpses');
                    }
                    echo("<td>" . $valide . "</td>");
                    echo("<td>");
                    if ($props[1] and !WP_SES_RESTRICTED) {
                        // remove this email
                        ?>
                        <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
                        <?php wp_nonce_field('wpses'); ?><input type="hidden" name="email" value="<?php echo $email ?>">
                            <!-- div class="submit" -->
                            <input type="submit" name="removeemail" value="<?php _e('Remove', 'wpses') ?>" onclick="return confirm('<?php _e('Effacer cette adresse des expéditeurs confirmés ?', 'wpses') ?>')"/>
                            <!-- /div -->
                        </form>
                        <?php
                    }
                    echo(" </td>");

                    echo("</tr>");
                    $i++;
                }
                ?>
            </table>
        </div>
        <?php } ?>
<?php if (!WP_SES_RESTRICTED) { ?>
        <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
    <?php wp_nonce_field('wpses'); ?>
            <!-- todo : que si email defini, que si pas dans la liste  -->
            <br />
    <?php _e('Add the following email: ', 'wpses') ?><?php echo $wpses_options['from_email']; ?><?php _e(' to senders.', 'wpses') ?>

            <p class="submit">
                <input type="submit" name="addemail" value="<?php _e('Add this Email', 'wpses') ?>" />
            </p>
        </form>
        <br />&nbsp;

        <h3><?php _e('Test Email', 'wpses') ?></h3>
    <?php _e('Click on this button to send a test email (via amazon SES) to the sender email.', 'wpses') ?>
        <br />
        <!-- todo: que si email expediteur valid� -->
        <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
        <?php wp_nonce_field('wpses'); ?>
            <p class="submit">
                <input type="submit" name="testemail" value="<?php _e("Send Test Email", 'wpses') ?>" />
            </p>
        </form>
        <br />&nbsp;
        <h3><?php _e('Production mode test', 'wpses') ?></h3>
    <?php _e('Once Amazon did activate your account into production mode, you can begin to send mail to any address<br />Use the form below to test this before fully activating the plugin on your blog.', 'wpses') ?>
        <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
    <?php wp_nonce_field('wpses'); ?>
            <table class="form-table" >
                <tr><th scope="row"><?php _e('Send email to ', 'wpses') ?></th>
                    <td><input type="text" name="prod_email_to" value="" /></td></tr>
                <tr><th scope="row"><?php _e('Subject', 'wpses') ?></th>
                    <td><input type="text" name="prod_email_subject" value="" /></td></tr>
                <tr><th scope="row"><?php _e('Mail content', 'wpses') ?></th>
                    <td><textarea cols="80" rows="5" name="prod_email_content"></textarea></td></tr>
            </table>
            <p class="submit">
                <input type="submit" name="prodemail" value="<?php _e("Send Full Test Email", 'wpses') ?>" />
            </p>
        </form>

        <?php } ?>
    <br />&nbsp;
        <?php _e('Using WP SES, finding it usefull ? A donation is always welcome', 'wpses') ?> <a href="http://wp-ses.com/donate.html" target="_blank"><b><?php _e('Donate', 'wpses') ?></b></a>
    <br />&nbsp;
    <div style="width:80%">
<?php
if (function_exists('sd_rss_widget')) {
    //	sd_rss_widget(array('num'=>3));
}
?>
    </div>
</div>