=== Notiblock ===
Contributors: philhoyt
Tags: block, notification, conditional, dashboard
Requires at least: 6.7
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
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