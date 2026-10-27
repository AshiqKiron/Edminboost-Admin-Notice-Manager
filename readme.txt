=== Edminboost - Admin Notice Manager ===
Contributors: ashiquzzaman
Tags: admin, admin-notices, dashboard, admin-tools, notices
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Manage, organize, and control WordPress admin notices from one central dashboard.

== Description ==

Edminboost Admin Notice Manager helps site administrators take control of WordPress admin notices. Reduce clutter, review notices in one place, and configure how notices appear across the admin area.

= Features =

* Dashboard with intercept metrics, upsell preset, and live feed
* Notice Inbox with search, filters, snooze, and bulk actions
* Auto-Rules engine with keyword, regex, and CSS selector matching
* Critical Safety Guard and Client Shield Mode for role-based filtering
* Weekly email digest and opt-in webhook alerts (Slack/Discord)
* JSON rules export/import and legacy competitor importer
* Hybrid PHP + JS notice interception
* Translation-ready with a dedicated text domain
* Uninstall-safe — removes all plugin options on uninstall

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Notice Manager** in the admin sidebar to get started.

== Frequently Asked Questions ==

= What does this plugin do? =

It intercepts, organizes, and filters WordPress admin notices from a dedicated dashboard with inbox, auto-rules, and analytics.

= Who can access the plugin settings? =

Only users with the `manage_options` capability (typically Administrators) can access plugin settings.

= Does this plugin send data to external servers? =

Not by default. The plugin does not track users or send site data externally unless you explicitly enable the opt-in webhook integration and provide a webhook URL. Weekly email digests are sent only to addresses you configure using WordPress `wp_mail()`.

= Is the plugin uninstall-safe? =

Yes. When uninstalled, all plugin options are removed from the database.

== Changelog ==

= 1.1.1 =
* Settings (General): ACL — per role, choose whether dashboard notifications are displayed or hidden.
* Settings (General): Spam words and whitelist — comma-separated word lists to hide matching notices entirely or always show them on screen.
* Settings (General): Block ad redirects — list admin URLs to block automatic post-install or promotional page loads (optional enable toggle).
* Settings (General): manage admin notices mode (show all, hide all, hide selected, compact panel), disable plugin/core update notifications, show hidden notices in the admin toolbar, and reset hidden notice preferences per user or site-wide.
* Visible admin notices: inline “Hide for me” and “Hide for all” actions (per-user or site-wide hash preferences; “Hide for all” can auto-create a rule).
* Admin bar notification dropdown: full notice preview with “First logged”, “Display notice as normal”, “Hide for me”, and “Hide for all” actions.
* Admin bar beside Howdy: Facebook-style bell icon with red count badge (replaces text “Admin notices” look); notification dropdown uses FB-like panel styling.
* Settings (General): Hiding notifications visibility — hide from all users, only selected users, or all users except selected accounts (user search picker).
* Settings (General): configure Standard admin notices (success, error, warning, information) and Non-Standard notices (no level + CSS ignore selectors).
* Debug tools: choose notice levels (error, warning, success, info, upsell) before Add to Inbox or Trigger Live Notices.
* Organize Settings into tabbed sections (General, Upsell Blacklist, Security, Notifications, Migration & Tools).
* Add admin bar panel style and position options: notification dropdown (bell icon) and placement beside the Howdy profile menu.
* Add red indicator option for the dropdown bell: number badge or dot-only.
* Add customizable panel colors (dot/badge, badge text, accent, link hover, open bell highlight).
* Add Default WordPress Notices section on Auto-Rules with one-click convert and a converted-state button tooltip.
* Inbox and preset Convert to Rule buttons show a converted tooltip on hover; state persists after refresh and can be undone with another click.
* Inbox column headers use WordPress list-table styles; sorting updates via AJAX without a full page reload.
* Add custom upsell blacklist phrases in Settings (one per line), merged with the built-in preset list.
* Add WP_DEBUG-only test notice generator on the Dashboard (seed inbox or trigger live interception).
* Rename Dashboard live feed to “Live Blocked Feed” with “View more” linking to Notice Inbox; store up to 100 feed preview entries.

= 1.1.0 =
* Add Quick Status toggle on the Dashboard to enable or disable notice interception; inactive by default on new installs.
* Add Dashboard wireframe with metrics, upsell preset toggle, bar chart, and live feed.
* Add Notice Inbox with search, filter tabs, bulk actions, and row actions.
* Add Auto-Rules page with CRUD modal and hit tracking.
* Expand Settings with Safety Guard, Client Shield, digest email, webhook, and import/export tools.
* Add hybrid PHP + JS notice interception engine.
* Add AJAX handlers for toggles, inbox actions, and rule management.
* Add weekly digest cron and opt-in webhook alerts.

= 1.0.0 =
* Initial release scaffold.
