=== Plugin Name ===
Contributors: SylvainDeaure
Donate link: http://wp-ses.com/donate.html
Tags: email,ses,amazon,webservice,delivrability,newsletter,autoresponder,mail,wp_mail,smtp,service
Requires at least: 3.0.0
Tested up to: 4.1
Stable tag: trunk

WP-SES redirects all outgoing WordPress emails through Amazon Simple Email Service (SES) for maximum email delivrability.

== Description ==

WP-SES redirects All outgoing WordPress emails through Amazon Simple Email Service (SES) instead of local wp_mail function.
This ensures high email delivrability, email trafic statistics and a powerful managed infrastructure.

This plugin is functionnal and I use it on several websites.
WPMU features are so far experimental.

Current features are:

*	Ability to adjust WordPress Default Sender Email and Name
*	Validation of Amazon API Credentials
*	Request confirmation for sender Emails
*	Test message within Amazon Sandbox mode
*	Full integration as seamless replacement for wp_mail internal function
*	Dasboard panel with Quota and statistics
*	Ability to customize return path for delivery failure notifications
*       Custom Reply-To or from Headers
*       Default config values for centralised WPMU setups
*       SES Endpoint selection     
*       Mails with attachments (Compatible with Contact Form 7)  

See full features at http://wp-ses.com/features.html

Roadmap

*	Graphical SES Statistics
*	Full featured Error management
*	Control of sending rate
*	Notice for volume limits
*	Bounce and blacklist management
*       Some kind of Logging for better debug


You can read more about Amazon SES here : http://aws.amazon.com/ses/
This plugin uses the Amazon Simple Email Service PHP class at http://sourceforge.net/projects/php-aws-ses/

== Installation ==

First, install like any other plugin:

1. Upload and activate the plugin
2. The setting are in settings / WP SES

Then, proceed to the settings:

1. Fill the email address and name to use as the sender for all emails
2. Via the amazon SES console, ask to add the sender email as a confirmed sender
3. Click on the link you got by email from Amazon SES
4. Fill in Amazon API credentials and same sender email
5. Save changes (Important !)
6. Refresh the plugin, send a test email
7. If ok, ask Amazon to go out of sandbox into production mode
7. Once in production mode, you can use the top button to activate the plugin.
8. From the plugin, you can manage and validate other senders.

== Frequently Asked Questions ==

= Where can I find support for the plugin ? =

Please use our main website http://wp-ses.com/faq.html for all support related questions.

= What are the pre-requisites ? =

*	A WP3+ Self hosted WordPress Blog
*	PHP5 and Curl PHP extension
*	An Amazon Web Service account
*	Validate your SES service

= Can you help me about... (an Amazon concern) =

We are not otherwise linked to Amazon or Amazon Services.
Please direct your specific Amazon questions to the Amazon support.

= How to setup default values for a WPMU install ? =

Please, DO test your setting without this.
Then, when all works as expected, fill in the config file.

Edit the wp-config.php file, and add what you want to define. Here is a complete setup, some defines are optionnal.

// WP-SES defines  

// Amazon Access Key  
define('WP_SES_ACCESS_KEY','blablablakey');

// Amazon Secret Key  
define('WP_SES_SECRET_KEY','blablablasecret');

// From mail (optional) must be an amazon SES validated email  
// hard coded email, leave empty or comment out to allow custom setting via panel  
define('WP_SES_FROM','me@....');

// Return path for bounced emails (optional)  
// hard coded email, leave empty or comment out to allow custom setting via panel  
define('WP_SES_RETURNPATH','return@....');

// ReplyTo (optional) - This will get the replies from the recipients.  
// hard coded email, or 'headers' for using the 'replyto' from the headers.   
// Leave empty or comment out to allow custom setting via panel  
define('WP_SES_REPLYTO','headers');

// Hide list of verified emails (optional)  
define('WP_SES_HIDE_VERIFIED',true);

// Hide SES Stats panel (optional)  
define('WP_SES_HIDE_STATS',true);

// Auto activate the plugin for all sites (optional)  
define('WP_SES_AUTOACTIVATE',true);

When using defines to hardcode your setting, don't forget to define the SES endpoints, too :

define('WP_SES_ENDPOINT', 'email.us-east-1.amazonaws.com');  
OR  
define('WP_SES_ENDPOINT', 'email.us-west-2.amazonaws.com');  
OR  
define('WP_SES_ENDPOINT', 'email.eu-west-1.amazonaws.com');  

== Screenshots ==

1. the settings screen of WP-SES plugin.

== Changelog ==

= 0.3.56 =
* fixed sender name format
* fixed regexp for some header recognition
* now supports comma separated emails in to: header

= 0.3.54 =
* bad ses lib include fixed
* Added "force plugin activation" for some use case with IAM credentials

= 0.3.52 =
* Warning if Curl not installed
* Attachments support for use with Contact Form (finally !)
* Notice fixed

= 0.3.50 =
* Notice fixed, setup documentation slightly tweaked

= 0.3.48 =
* Experimental "WP Better Email" Plugin compatibility

= 0.3.46 =
* Maintenance release - fixes some notices and old code.

= 0.3.45 =
* Maintenance release - fixes some notices.

= 0.3.44 =
* Added Amazon SES Endpoint selection. EU users can now select EU region.

= 0.3.42 =
* Added Spanish translation, thanks to Andrew of webhostinghub.com

= 0.3.4 =
* Auto activation via WP_SES_AUTOACTIVATE define, see FAQ.

= 0.3.2 =
* Tweaked header parsing thanks to bhansson

= 0.3.1 =
* Added Reply-To
* Added global WPMU setup (To be fully tested)

= 0.2.9 =
* Updated SES access class
* WP 3.5.1 compatibility
* Stats sorting
* Allow Removal of verified e-mail address
* Added wp_mail filter
* "Forgotten password" link is now ok.
* Various bugfixes

= 0.2.2 =
Reference Language is now English.  
WP SES est fourni avec les textes en Francais.

= 0.2.1 =
Added some functions

* SES Quota display
* SES Statistics
* Can set email return_path
* Full email test form
* Can partially de-activate plugin for intensive testing.

= 0.1.2 =
First public Beta release

* Functionnal version
* Internationnal Version
* fr_FR and en_US locales

= 0.1 =
* Proof of concept

== Upgrade Notice ==

= 0.2.9 =
Pre-release, mainly bugfixes, before another update.

= 0.2.2 =
All default strings are now in english.

= 0.2.1 =
Quota and statistics Integration

= 0.1.2 =
First public Beta release


