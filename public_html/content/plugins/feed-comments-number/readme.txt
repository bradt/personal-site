=== Plugin Name ===
Contributors: jclay06
Donate link: http://josh.thespiffylife.com/about/
Tags: comments, feed, rss
Requires at least: 2.3
Tested up to: 2.7
Stable tag: 0.2

Add an image that displays the number of comments to your feed items.

== Description ==

This plugin will display an image showing the number of comments on a post at the bottom of each feed item (RSS|Atom|etc) that links to the comments section of that post.
You are able to choose which colors to use for the background of the image and the color of the text as well.
The reason you would want to use an image instead of plain text is because RSS feeds are generally only updated if the build-date or modification-date components are changed, which does not happen if someone leaves a new comment. This will allow the comment number to update dynamically, regardless of when it's been first viewed by a user.

You're able to set the colors of the text and the background in the standard 6 character hex format.
You may also supply formats for what will show if there are zero comments on your post or more.
I just added the option to include the image link in the feed excerpts as well. (called by `the_excerpt_rss` function)

Two Font files are included with the plugin, Arial and SenateBold. You can set which of the two you would like to use, or optionally upload your own .ttf font file to use to render the text on the image.

This requires the GD Image library extension in PHP to function. To check whether or not your server has this use the `<?php php_info(); ?>` function and it should be listed.

== Installation ==

1. Upload the `feed-comments-number` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Enjoy!
4. You can optionally set the appropriate colors in your 'Settings' => 'Feed Comments Number' menu in WordPress as well as upload your own .ttf font file.

== Screenshots ==

1. This is an example of what will be shown if your post has no comments on it.
2. This is an example of what will be shown if your post has 3 comments on it.
3. This is a screenshot of the plugin options.