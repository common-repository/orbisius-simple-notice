=== Orbisius Simple Notice ===
Contributors: lordspace,orbisius
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7APYDVPBCSY9A
Tags: wp,orbisius,notice,alert,hellobar,hello bar,beforesite, heads up, heads up bar, headsup, headsupbar, notification, notification bar, popup, Toolbar
Requires at least: 3.6
Tested up to: 5.6
Stable tag: 1.1.2
Requires PHP: 5.6
License: GPLv2 or later

This plugin allows you to show a simple notice to alert your users about server maintenance, new product launches etc.

== Description ==

This plugin allows you to show a simple notice to alert your users about server maintenance, new product launches etc.

= Features / Benefits =
* Enter text an the message will be shown to your users.
* For logged in users the notice will be shifted by 28px (because WP admin bar is obstructing the notice)
* Rich text editor to enter notice text
* Use nice color pickers to select the colors for notice text, background and link color (if any).
* Supports text and HTML
* The notice can be shown on top of all content or to push the content down
* You can choose to show the notice on all pages/posts or on the home page only
* Optionally show a close button. When a notice is closed/dismissed it won't be shown again until the message is changed or more than 2 days have passed.
* Change the font size of the box

== Demo ==

Live demo powered by <a href="https://wpdemo.net/?utm_sourceorbisius-simple-notice" target="_blank" title="WordPress demo site">WPDemo.net</a>
<a href="https://wpdemo.net/try/plugin/orbisius-simple-notice" target="_blank" title="[new window]">https://wpdemo.net/try/plugin/orbisius-simple-notice</a>

= Support =
> The support is handled here: <a href="https://github.com/orbisius/orbisius-simple-notice/issues" target="_blank" title="[new window]">https://github.com/orbisius/orbisius-simple-notice/issues</a>
> Please do NOT use the WordPress forums or other places to seek support as we might not see it on time.

= Author =

Svetoslav Marinov (Slavi) | <a href="https://orbisius.com" title="Custom Web Development, e-commerce, Plugin Development, Ontario, Canada" target="_blank">Custom Plugin, Web & SaaS app Development by Orbisius.com</a>

== Upgrade Notice ==
n/a

== Screenshots ==
1. The Notice shown by the plugin
2. Plugin's Settings Page (part 1)
2. Plugin's Settings Page (part 2)

== Installation ==

1. Unzip the package, and upload `orbisius-simple-notice` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= How to use this plugin? =
Just install the plugin and activate it. The feedback text appear in the public area

= How to remove the powered by? =

If you don't want to give us credit you can do that from the options or by using the filter below. Add this to your functions.php or custom plugin
add_filter('orbisius_simple_notice_filter_powered_by', '__return_false');

== Changelog ==

= 1.1.2 =
* Added z-index so the links in the message are clickable.
* Made sure that links have underline

= 1.1.1 =
* Fixed links in the readme file.

= 1.1.0 =
* Added demo link
* Added Requires PHP tag in the readme
* Tested with WP 5.6
* Switched all links to https
* Removed some external sharing sites

= 1.0.9 =
* Changed non-ssl links to protocol relative ones
* Removed some tracking ids from some sharing widgets.
* Moved preview higher so the user doesn't have to scroll to see it.

= 1.0.8 =
* Tested with WP 4.6

= 1.0.7 =
* Do not render when it's ajax call
* Tested with WP 4.1

= 1.0.6 =
* Replacing any shortcodes used in the notice box.

= 1.0.5 =
* Tested with WP 4.0.1
* Fixed a weird bug that wasn't allowing admins to insert links.
* Fixed typos
* Changed 'Save' -> 'Save Changes' so it's more easily clickable.

= 1.0.4 =
* Showing some instructions only if WP >= 3.5
* Fixed typos

= 1.0.3 =
* Added a box that allows the user to select font size

= 1.0.2 =
* Added the old color picker for WordPress installs older than 3.5
* Added an option the notice to push the content down or to stay on top
* Added a little i icon on the left as a powered by option + option to enable/disable it.
* Added an option for the user to select to show the notice on all pages or on the home page only
* The notice can be closed/dismissed
* Updated screenshots

= 1.0.1 =
* Added a color picker for text, text background color and for links.
* Added preview in the settings page.
* Added uninstall.php to cleanup settings.

= 1.0.0 =
* Initial release
