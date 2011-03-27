=== Plugin Name ===
Contributors: esserq
Donate link: http://blarrr.com/wordpress-badges-plugin/
Tags: badges, awards, points
Requires at least: 3.1
Tested up to: 3.1
Stable tag: 1.2.1

A Stack Overflow inspired plugin for WordPress which allows users to acquire badges for contributing website content.

== Description ==

A Stack Overflow inspired plugin for WordPress which allows users to acquire badges for contributing website content. Badges are created and managed through the WordPress Dashboard.

Key features include:

* Administration page to manage badges and conditions
* A widget to display recently awarded badges
* Function which generates a table of badges
* Manually award and revoke badges
* Automatically check/ award badges after publishing posts/ comments

Contact me if you find any bugs, issues or have a feature request and I will do my best to accommodate. 

More examples and information @ [blarrr.com](http://blarrr.com/wordpress-badges-plugin/)

== Installation ==

1. Upload `rh-badges.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Modify badges using the badges menu

== Screenshots ==

1. The Badge management screen
2. The badge condition management screen
3. rhb_list_badges() in action

== Changelog ==

= 1.2.1 =
* Included CSS with plugin

= 1.2 =
* Added user ID to the filter in both `rhb_list_badges()` and `rhb_get_badges()`.

= 1.1 =
* Added 'Latest Badges' widget.
* The original author is now checked for badges instead of the approver.
 
= 1.0 =
* First release.

== Limitations ==

* Does not support OR conditions or nested conditions.
* The current version only supports limited object types: post_tag, post_count and comment_count.
* Conditions cannot be restricted by post type or category without modifying plugin code.