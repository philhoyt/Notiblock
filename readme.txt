=== Notiblock ===
Contributors: philhoyt
Tags: block, notification, conditional, dashboard
Requires at least: 6.7
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Conditional notification blocks for WordPress with dashboard widget configuration.

== Description ==

Notiblock adds two blocks to the editor: a conditional wrapper that shows or hides its contents based on a date range, and a message block that displays a globally configured notification. Set the message and date range once in the dashboard widget or settings page, then place the blocks wherever the notification should appear.

* Date-based conditional display with optional "Always show" override.
* Configure the notification message and date range from the dashboard widget or Settings → Notiblock.
* Rich text editor for notification message formatting.
* Place blocks in posts, pages, or templates.

== Installation ==

1. Download the latest `notiblock.zip` from the [GitHub releases page](https://github.com/philhoyt/notiblock/releases).
2. Go to **Plugins → Add New → Upload Plugin** and upload the zip file.
3. Activate through the Plugins screen.
4. Go to **Dashboard → Notiblock Settings** or **Settings → Notiblock** to configure your message and date range.

== Frequently Asked Questions ==

= How do the two blocks work together? =

Notiblock Conditional is the wrapper block that handles the date logic. Notiblock Message goes inside it and displays the globally configured notification. The Message block is only available inside the Conditional block.

= Can I show the notification outside of its date range? =

Yes. Check "Always show (ignore date range)" in the settings to display the notification regardless of dates.

= Can I use multiple Conditional blocks on the same page? =

Yes. Each one displays the same global message and respects the same date settings.

= What happens if no dates are set? =

The notification will not display unless "Always show" is checked.

== Changelog ==

= 1.1.0 =
* Security: Links added in the message editor are now checked, so unsafe URLs are rejected before they are saved.
* Add: Full translation support. All admin and editor text can now be translated, and a notiblock.pot template ships with the plugin.
* Add: Settings are removed when the plugin is deleted, including across every site on a multisite network.
* Fix: Automatic updates were silently unavailable. The update library was missing from released downloads, so the plugin never checked for new versions.
* Fix: On multisite, one site could show another site's notification message and dates.
* Fix: The Message block could be placed on its own, outside a Conditional block, where it displayed regardless of the date range. It is now only available inside a Conditional block, as documented.
* Fix: Adding a link now opens a proper dialog instead of a browser prompt, so it can be used with a keyboard and a screen reader.
* Fix: The message editor now shows a visible outline when focused, and its label is correctly linked to the field.
* Change: Admin scripts and styles no longer load on screens that do not show the settings panel.

= 1.0.0 =
* Replaced TinyMCE editor with a React-based rich text editor.
* Added text alignment controls to the editor toolbar.
* Added Settings → Notiblock admin page alongside the dashboard widget.
* Added REST API endpoint for saving settings from the React app.
* Added GitHub release-based auto-updates via Plugin Update Checker.
* Start date is restricted to today or later.
* End date is restricted to the start date or later.
* Changing the start date past the end date automatically clears the end date.
* Date fields are disabled when "Always show" is enabled.

= 0.1.0 =
* Initial release.