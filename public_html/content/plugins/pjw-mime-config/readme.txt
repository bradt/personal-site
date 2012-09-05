=== PJW Mime Config  ===
Tags: upload, mime, security
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=paypal%40ftwr%2eco%2euk&item_name=Peter%20Westwood%20WordPress%20Plugins&no_shipping=1&cn=Donation%20Notes&tax=0&currency_code=GBP&bn=PP%2dDonationsBF&charset=UTF%2d8
Contributors: westi
Tested up to: 2.9
Requires at least: 2.8
Stable tag: 1.00

== Description ==
This plugin allows you to configure extra mime-types for support by the inline-uploader.

A new options page is added as Options ... Mime-types which allows you to add/delete the extra mime-types.

By default the following extra mime-types are registered: audio/ac3, audio/MPA and video/x-flv.

With version 0.90 of the plugin you are now able to upload a file containing a long list of mime types as an easy way to register multiple mime-types.
The file format is "mime/type extension" for example like this:

	audio/ac3 ac3
	audio/MPA mpa
	video/x-flv flv

== Installation ==

1. Upload to your plugins folder, usually `wp-content/plugins/`
2. Activate the plugin on the plugin screen.
3. Navigate to Settings ... Mime-types to add/delete mime-types.

== Changelog ==

= v1.00 =
* Tidy up of old code and removal of support for very old WordPress versions.
* Update of look and feel to match recent WordPress versions

= v0.90 = 
* Add support for upload of a list of mime types in a file
