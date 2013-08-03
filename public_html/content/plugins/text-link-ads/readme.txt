=== Text Link Ads ===
Contributors: tlaplugins
Tags: ads, text link ads
Requires at least: 2.0.2
Tested up to: 2.8.4
Stable tag: 3.9.1

Text Link Ads Sell Ads on your website. Make more money by setting text based ads.

== Description ==

Features

* Ads that can be placed anywhere on a page, including the footer.
* Predictable revenue. Get paid a flat rate per month per ad sold.
* Full editorial control. Approve or deny ads as they are sold or allow us to do the work.
* Blog friendly. Install our simple plugin and we take care of the rest. Just sit back and collect your monthly earnings.
* This plugin works with the MU installs.

Join over 40,000 publishers monetizing their sites. Connect with our large and growing client base while maintaining editorial control over ads placed on your site. Get paid instantly on the 1st of each month with no fees or hassles.

This plugin requires a valid and approved site key. You must sign up and submit your blog to [Text link Ads](http://www.text-link-ads.com 'Text Link Ads')

== Installation ==

To install a plugin, please follow the list of instructions below.

1. Upload the plugin to the "wp-content/plugins" folder in your WordPress directory.
2: If you have an old textlinkads folder, please remove that from your plugins as the name has changed to text-link-ads
3. Activate the Plugin:
* Access the Plugin Panel in your Admin Panel.
* Scroll down through the list of Plugins to find the newly installed Plugin.
* Click on the Activate link to turn the Plugin on.
4. If you downloaded the plugin from [www.text-link-ads.com](http://www.text-link-ads.com 'text link ads') as a logged in publisher you are all set, otherwise you will need to:
* Go to [www.text-link-ads.com](http://www.text-link-ads.com 'text link ads') and submit your site to receive a site key.
* Once you have the site key you can enter it from the Settings -> Text Link Ads and click Save.
* You will receive notification once your site has been accepted or denied from the marketplace. From there all you need to do is wait for ads to start rolling in.
4: Add the Text Link Ads Widget in Appearance -> Widgets to the location you will want your ads to appear. or include <?php tla_ads(); ?> in the template you want the ads to appear.
5: Go to Settings -> Text Link Ads Settings and add your Ad Keys and URLs for the pages you have submitted to the Text Link Ads marketplace

== Frequently Asked Questions ==

= Where Can I get Information about this plugin? =

Click [HERE](http://www.text-link-ads.com/r/knowledge_base/category/tla_installation/ "knowledge base of plugin issues")

== Screenshots ==

1. Backend Settings.
2. Widget Settings

== Changelog ==

= 3.9.10 =
* removed site wide ads option 

= 3.9.3 =
* uses JSON format (if supported) by default to save bandwidth

= 3.5.0 =
* added checks for file_get_contents, and curl before using it as a method for backwards compat

= 3.4.9 =
* resolved issue with is_front_page and non blog sites

= 3.4.8 =
* added privacy by creating a .htaccess file after installation or if not to write to the plugin dir provided info on settings page on howto do it.

= 3.4.7 =
* fixed url and results in default set

= 3.4.6 =
* fixed fatal error see http://wordpress.org/support/topic/314454?replies=5#post-1223863
* update readme

= 3.4.5 =
* added settings system
* added widget system
* integrated the options to allow for different ads fetching methods ( asked because different hosting providers are closing different ports
* removed legacy code
* integrated the styles tags back in so that users can modify them WARNING do not incorporate nofollow tags here.

= 3.4.1 =
* added readme support.
* added back in mclude for cache
* improved default key for generic plugin download

