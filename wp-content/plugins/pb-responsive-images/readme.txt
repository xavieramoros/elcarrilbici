=== PB Responsive Images ===
Contributors: phenomblue, spacemanspud
Donate link: http://phenomblue.com/
Tags: responsive images, polyfill, images
Requires at least: 3.0
Tested up to: 3.5.2
Stable tag: 1.4.1
License: GPLv3 or later
License URI: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)

Adds support for the proposed responsive image format in post content, and helper functions for theme authors.

== Description ==

The PB Responsive Images plugin automatically reformats all images in the post content into a format similar to the picture tag proposed by the <a href="http://www.w3.org/community/respimg/" target="_blank">Responsive Images Community Group on w3.org</a>.

The default configuration provides the necessary image sizes for the Twenty Eleven theme for reference; you'll have to customize the configuration to fit your theme best. Eace image is reformatted based on standard CSS media queries and SLIR image query pairs, giving a huge amount of flexibility in the variety of query combinations. For example, with a media query of `(min-width:500)` and a resize query of `w700`, a version of the image at most 700 pixels wide will display only when the screen is greater than 500 pixels wide. Additionally, the plugin provides shortcodes that allow you to customize the queries used per image, and helper functions that can be used to customize the queries used per post or per layout.

For additional information or help on implementing this plugin, use the "Help" tab on the upper right of the plugin screen, which has detailed explanations on each portion of the plugin. If that doesn't help, please post your questions to <a href="http://wordpress.org/support/plugin/pb-responsive-images" target="_blank">the forums</a> on wordpress.org.

== Installation ==

1. Upload `pb-responsive-images` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Modify the relevant media queries and resize parameters to match your theme - see the plugin's contextual help section for details.
4. Place `<?php RIP::get_picture($image,$formats); ?>` in your templates, as necessary - see the plugin's contextual help section for details.

== Frequently Asked Questions ==

= No questions, yet! Is that a good or a bad thing? =

== Screenshots ==

1. The configuration page

== Changelog ==

= 1.4.1 =
* Bugfix for WordPress installed in separate directory

= 1.4 =
* Fixes for IE8 compatibility
* Fixes for warnings in debug mode
* IIS compatibilty fixes
* Bug fixes for serving out of a WordPress subdirectory install

= 1.3 =
* Bug fixes for cross browser compatibility
* Fixed javascript warning that occasionally occurs in IE7/8

= 1.2 =
* Removed content formatting for rss feeds
* Performance enhancements in the options object
* Streamlined the image creation pipeline
* Added ability to bypass slir and provide specific images per media query
* Corrected html output - now valid html5 syntax

= 1.1 =
* Adding support for relative and root-relative links
* Adding support for IIS, and the bugs related to Windows hosting

= 1.0 =
* Initial public release!

== Upgrade Notice ==

= 1.4.1 =
Bugfix for WordPress installed in separate directory

= 1.4 =
Bug fixes for IE and IIS compatibility, and WordPress debug warnings, and serving out of a WordPress subdirectory install.

= 1.3 =
Bug fixes for older IE browser compatibility, and specific image high res media queries.

= 1.2 =
Bug fixes for rss feeds, syntax, and performance
Additional functionality added to RIP::get_picture - you can now provide specific images per Media Query. Check the help section for details.

= 1.1 =
Adds support for relative and root-relative links
Adds support for IIS, and the bugs related to Windows hosting. Thanks to Edward Brey for the web.config code!

= 1.0 =
Initial public release!