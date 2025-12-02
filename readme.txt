=== Notiblock ===
Contributors:      The WordPress Contributors
Tags:              block, notification, conditional, dashboard
Tested up to:      6.7
Requires at least: 6.7
Requires PHP:      7.4
Stable tag:        0.1.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Conditional notification blocks with dashboard widget configuration. Display time-sensitive messages that automatically show or hide based on date ranges.

== Description ==

Notiblock is a WordPress block plugin that allows you to create conditional notification messages that display on your site based on date settings. Perfect for announcements, promotions, maintenance notices, or any time-sensitive content.

**Key Features:**

* **Conditional Display:** Show or hide notifications based on start and end dates
* **Dashboard Widget:** Easy configuration through a dedicated dashboard widget
* **Always Show Option:** Override date restrictions when needed
* **Rich Text Editor:** Format your notification messages with the built-in editor
* **Block-Based:** Modern block editor integration with custom block category
* **Flexible Placement:** Place notification blocks anywhere in your content

**How It Works:**

1. Configure your notification message and date range in the Dashboard widget
2. Add the "Notiblock Conditional" block to any post, page, or template
3. The notification automatically displays when the current date is within your specified range
4. Use the "Always show" option to display notifications regardless of dates

**Use Cases:**

* Site-wide announcements
* Limited-time promotions
* Maintenance notices
* Holiday messages
* Event notifications
* Seasonal content

== Installation ==

1. Upload the `notiblock` folder to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Dashboard → Notiblock Settings to configure your notification message
4. Add the "Notiblock Conditional" block to any post, page, or template where you want the notification to appear

== Frequently Asked Questions ==

= How do I configure a notification? =

Go to your WordPress Dashboard and look for the "Notiblock Settings" widget. Enter your message, set start and end dates, and save. The notification will automatically display when the current date is within your specified range.

= Can I show the notification always, regardless of dates? =

Yes! Check the "Always show (ignore date range)" checkbox in the dashboard widget settings. This will display the notification regardless of the date settings.

= Where can I place the notification blocks? =

You can place the "Notiblock Conditional" block anywhere in your content - posts, pages, template parts, or even in widget areas that support blocks. The notification will only display when the date conditions are met (or if "Always show" is enabled).

= What happens if I don't set any dates? =

If no dates are set and "Always show" is not checked, the notification will not display. You must either set a date range or enable "Always show" for the notification to appear.

= Can I use multiple notification blocks on the same page? =

Yes, you can add multiple "Notiblock Conditional" blocks to the same page. They will all display the same global message configured in the dashboard widget.

= What if the end date is before the start date? =

The notification will not display. Make sure your end date is after your start date for the notification to work correctly.

= Can I customize the styling of the notification? =

Yes! The blocks use standard WordPress block classes that can be styled with your theme's CSS. The main wrapper class is `.wp-block-notiblock-conditional` and the message block uses `.wp-block-notiblock-message`.

== Screenshots ==

1. Dashboard widget for configuring notification settings
2. Notiblock Conditional block in the block editor
3. Notification displayed on the frontend

== Changelog ==

= 0.1.0 =
* Initial release
* Conditional notification block with date-based display logic
* Dashboard widget for configuration
* Message block for displaying global notification content
* Custom block category registration
* REST API endpoint for editor preview
* Support for "Always show" override option

== Upgrade Notice ==

= 0.1.0 =
Initial release of Notiblock. Install and activate to start using conditional notifications.
