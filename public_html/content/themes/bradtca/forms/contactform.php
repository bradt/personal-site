<?php
include BT_FORMS . '/phpformwork.php';

class ContactForm extends PHPFormWork {
    var $success;
    var $budget_options = array(
        'too_low' => '$0 - $500',
        'low' => '$500 - $2,000',
        'medium' => '$2,000 - $5,000',
        'high' => '$5,000+'
    );
    var $schedule_options = array(
        '1-4' => '1 - 4 weeks from now',
        '4-8' => '4 - 8 weeks from now',
        '8-12' => '8 - 12 weeks from now',
        '12-16' => '12 - 16 weeks from now',
        'flexible' => 'Flexible'
    );

    function ContactForm() {
        $this->success = false;

        $malicious = array(
            'func' => array( $this, 'malicious' ),
            'msg' => "You can not use any of the following: a linebreak, or the phrases<br />'mime-version', 'content-type', 'bcc:', 'cc:' or 'to:'"
        );

        $fields[] = new PFW_Optionlist( 'what', array(
                'req' => 'Please choose one of the options above.',
                'lbl' => 'What would you like to contact me about?',
                'options' => array(
                    'plugin' => 'WordPress plugin support',
                    'work' => 'Work, job, or contract',
                    'general' => 'Something else',
                    'bot' => 'I am a SPAM bot'
                ),
                'value' => 'bot',
                'validation' => array(
                    array(
                        'func' => array( $this, 'validate_bot' ),
                        'msg' => 'Sorry, I don\'t talk to bots...yet.'
                    ),
                    array(
                        'func' => array( $this, 'validate_what' ),
                        'msg' => 'Please choose another option above.'
                    )
                )
            ) );

        $fields[] = new PFW_Field( 'budget', array(
                'lbl' => 'What is your budget? <small>(Not sure? <a target="_blank" href="/archives/what-does-it-cost-to-build-a-web-site/">Read this</a>.)</small>',
                'validation' => array(
                    array(
                        'func' => array( $this, 'validate_work_required' ),
                        'msg' => 'Please enter your budget.'
                    )
                )
            ) );

        $fields[] = new PFW_Field( 'schedule', array(
                'lbl' => 'When do you need your project completed by?',
                'validation' => array(
                    array(
                        'func' => array( $this, 'validate_work_required' ),
                        'msg' => 'Please enter your project schedule.'
                    )
                )
            ) );

        $fieldsets[] = new PFW_Fieldset( 'question', $fields );

        $fields = array();

        $fields[] = new PFW_Field( 'your_name', array(
                'req' => 'Please enter your name.',
                'lbl' => 'Your Name:',
                'validation' => $malicious
            ) );

        $fields[] = new PFW_Field( 'your_email', array(
                'req' => 'Please enter your email address.',
                'lbl' => 'Your Email:',
                'validation' => array(
                    array(
                        'func' => 'valid_email',
                        'msg' => 'Please enter a valid email address.'
                    ),
                    $malicious
                )
            ) );

        $fields[] = new PFW_Field( 'subject', array(
                'req' => 'Please enter a subject.',
                'lbl' => 'Subject:',
                'validation' => $malicious
            ) );

        $fields[] = new PFW_Textarea( 'message', array(
                'req' => 'Please enter a message.',
                'lbl' => 'Message:'
            ) );

        if ( !isset( $_POST['what'] ) || !in_array( $_POST['what'], array( 'plugin', 'personal' ) ) ) {
            $fieldsets[] = new PFW_Fieldset( 'message-details', $fields );
        }

        parent::PHPFormWork( $fieldsets, get_settings( 'blog_charset' ) );

        if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
            $_POST = array_map( 'stripslashes', $_POST );
            $this->validate();

            if ( empty( $this->errors ) ) {
                $this->sendmail();
                $this->success = true;
            }
        }
    }

    function sendmail() {
        foreach ( $this->fieldsets as $fieldset ) {
            foreach ( $fieldset->fields as $var => $field ) {
                $$var = $field->value;
            }
        }

        $to = get_settings( 'admin_email' );

        if ( 'work' == $what ) {
            $top = '';
            if ( isset( $this->budget_options[$budget] ) ) {
                $top .= "Budget: " . $this->budget_options[$budget] . "\n";
            }
            if ( isset( $this->schedule_options[$schedule] ) ) {
                $top .= "Completion: " . $this->schedule_options[$schedule] . "\n\n";
            }
            $message = $top . $message;
        }

        $message = "From: $your_name &lt;$your_email&gt;\n\n" . $message;

        $message = wordwrap( $message, 80, "\n" );

        $headers = "MIME-Version: 1.0\n";
        $headers .= "List-ID: <contact-form.bradt.ca>\n";
        $headers .= "Content-Type: text/plain; charset=\"" . get_settings( 'blog_charset' ) . "\"\n";
        $headers .= "Reply-To: $your_name <$your_email>\n";

        wp_mail( $to, $subject, $message, $headers );
    }

    function malicious( $input ) {
        $is_malicious = false;
        $bad_inputs = array( "\r", "\n", "%0a", "%0d", "Content-Type:", "bcc:", "to:", "cc:" );
        foreach ( $bad_inputs as $bad_input ) {
            if ( stripos( strtolower( $input ), strtolower( $bad_input ) ) !== false ) {
                $is_malicious = true; break;
            }
        }
        return !$is_malicious;
    }

    function validate_bot( $str ) {
        return 'bot' != $str;
    }

    function validate_what( $str ) {
        return !in_array( $str, array( 'plugin', 'personal' ) );
    }

    function validate_work_required( $str ) {
        return 'work' != $_POST['what'] || ( 'work' == $_POST['what'] && '' != $str );
    }
}
?>
