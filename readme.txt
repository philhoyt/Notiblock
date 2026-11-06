=== Notiblock ===
Contributors: philhoyt
Tags: block, notification, conditional, dashboard
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.2
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Shows one site-wide notification message between a start and end date, placed with two blocks

== Description ==

Notiblock stores a single notification message and a date range. Two blocks put it on the page:

* Notiblock Conditional is a wrapper. It renders its inner blocks only while the notification is active, and renders nothing otherwise.
* Notiblock Message prints the stored message. It can only be placed inside a Conditional block.

Write the message and set the dates once, in the Notiblock Settings dashboard widget or under Settings > Notiblock. Then add the blocks to any post, page or template. Every copy of the blocks shows the same message and follows the same dates.

The message editor supports bold, italic, links and text alignment. Links are checked when saved; `javascript:` URLs and script tags are stripped.

The notification is active when today falls within the start and end dates (both inclusive, using the site's timezone). Leave one date empty to have no limit on that side. With no dates at all the notification stays hidden unless "Always show (ignore date range)" is ticked.

== Installation ==

1. Download `notiblock.zip` from the GitHub releases page.
2. In the WordPress admin, go to Plugins > Add New Plugin > Upload Plugin and choose the zip.
3. Activate the plugin.
4. Set the message and dates in the Notiblock Settings dashboard widget or under Settings > Notiblock.

Updates are delivered from GitHub releases. The plugin is not listed on WordPress.org.

== Frequently Asked Questions ==

= How do the two blocks work together? =

Notiblock Conditional decides whether anything renders, based on the dates. Notiblock Message goes inside it and prints the message. You can put other blocks inside the Conditional block too; they show and hide with the notification.

= Can I show the notification outside its date range? =

Yes. Tick "Always show (ignore date range)" in the settings.

= What happens if no dates are set? =

Nothing is shown unless "Always show" is ticked. Set only a start date to show from that day on, or only an end date to show until that day.

= Can I use several Conditional blocks on one site? =

Yes. They all show the same message and follow the same dates. There is one notification per site.

= How does it work on multisite? =

By default each site has its own message and dates. To share one notification across the network, add `define( 'NOTIBLOCK_NETWORK_WIDE', true );` to `wp-config.php` or return true from the `notiblock_use_network_settings` filter. The settings page then moves to Network Admin > Settings > Notiblock.

= Who can change the settings? =

Administrators (`manage_options`), or network administrators in network-wide mode. Anyone who can edit posts can read the settings, which the block editor needs to preview the message.

== Screenshots ==

1. The Notiblock Settings dashboard widget with the message editor, date fields and current status.

== Changelog ==

= 1.1.2 =
* Fixed the update checker so it always installs the release zip, not whichever file GitHub lists first.
* Rewrote the readme files in plain English.

= 1.1.1 =
* Tested against WordPress 7.1 and rebuilt with the current block editor toolchain.

= 1.1.0 =
* Links added in the message editor are now checked, and unsafe URLs are rejected before they are saved.
* Added translation support for all admin and editor text, with a `notiblock.pot` template in the plugin.
* Settings are now removed when the plugin is deleted, including on every site of a multisite network.
* Fixed automatic updates, which were unavailable because the update library was missing from released zips.
* Fixed one site showing another site's message and dates on multisite.
* Fixed the Message block being placeable outside a Conditional block, where it ignored the date range. It is now only available inside a Conditional block.
* Fixed the link dialog in the message editor, which used a browser prompt; it is now a proper dialog that works with a keyboard and a screen reader.
* Fixed the message editor's missing focus outline and its label association.
* Admin scripts and styles no longer load on screens without the settings panel.

= 1.0.0 =
* Replaced the TinyMCE editor with a React rich text editor.
* Added text alignment controls to the editor toolbar.
* Added a Settings > Notiblock page alongside the dashboard widget.
* Added a REST endpoint for saving settings from the settings app.
* Added GitHub release updates through Plugin Update Checker.
* The start date can no longer be set in the past.
* The end date can no longer be set before the start date; moving the start date past the end date clears the end date.
* Date fields are disabled while "Always show" is ticked.

= 0.1.0 =
* Initial release.
