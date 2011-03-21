=== Plugin Name ===
Contributors: esserq
Donate link: http://blarrr.com/wordpress-badges-plugin/
Tags: badges, awards, points
Requires at least: 3.1
Tested up to: 3.1
Stable tag: 1.0

A Stack Overflow inspired plugin for WordPress which allows users to acquire badges for contributing website content.

== Description ==

A Stack Overflow inspired plugin for WordPress which allows users to acquire badges for contributing website content. Badges are created and managed through the WordPress Dashboard.

== Installation ==

1. Upload `rh-badges.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Modify badges using the badges menu

== Screenshots ==

1. The Badge management screen
2. The badge condition management screen
3. rhb_list_badges() in action

== Changelog ==

= 1.1 =
* Added 'Latest Badges' widget.
* The original author is now checked for badges instead of the approver.
 
= 1.0 =
* First release.

== Features ==

* Create and edit custom badges and badge conditions.
* Manually award/ revoke badges.
* Automatically check badge conditions and award badges after user creates a new post.
* Dashboard administration.

== Limitations ==

* Does not support OR conditions or nested conditions.
* The current version only supports limited object types: post_tag, post_count and comment_count.
* Conditions cannot be restricted by post type or category (without modifying plugin code).


For more info, visit [blarrr](http://blarrr.com/wordpress-badges-plugin/).
