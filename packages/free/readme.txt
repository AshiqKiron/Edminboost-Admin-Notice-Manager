=== Edminboost - Admin Notice Manager ===
Contributors: ashiquzzaman
Tags: admin, admin-notices, dashboard, admin-tools, notices
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.1.1
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

Only users with the `manage_options` capability (typically Administrators) can access plugin settings. On multisite, super admins configure network policy under **Network Admin → Notice Manager** using the `manage_network_options` capability.

= Does the plugin support WordPress multisite? =

Yes. Each site keeps its own notice inbox and statistics. Network administrators can set a multisite policy (per-site, network defaults with optional site overrides, or network-managed), define network default settings, and optionally share auto-rules across all sites.

= Does this plugin send data to external servers? =

Not by default. The plugin does not track users or send site data externally unless you explicitly enable the opt-in webhook integration and provide a webhook URL. Weekly email digests are sent only to addresses you configure using WordPress `wp_mail()`.

= Is the plugin uninstall-safe? =

Yes. When uninstalled, all plugin options are removed from the database.

== Changelog ==

= 1.1.2 =
* Security: block ad redirects intercept post-activation welcome redirects (options/transients, wp_redirect, and known admin_init hooks), keep you on the Plugins screen when appropriate, and accept bare `page` slugs in the blocklist.
* Admin: Billing page with Free vs Pro comparison table (Pro-only differentiators; regex/CSS auto-rules, Client Shield Mode, and block ad redirects stay on both plans).
* Settings: rename the Access & visibility tab to Access.
* Multisite: network admin screens for multisite policy, network default settings, and shared network auto-rules; each site keeps its own inbox and statistics.
* Multisite: site Settings include a Multisite tab; site settings and Quick Status respect network lock and override permissions.
* Settings (White label): agency branding — custom admin menu title, dashboard brand name and logo, hide dashboard brand or Docs link, optional custom Docs URL.

= 1.1.1 =
* Translations: bundled language packs for English, Bangla (Bengali), Hindi, Russian, Spanish, French, Arabic, Chinese (Simplified/Mandarin), and Chinese (Traditional).
* Auto-Rules: optional scheduled rules — limit matching to selected weekdays and hours (site timezone); rules list shows schedule summary and an “Outside schedule” state when the rule is enabled but inactive for the current time.
* Admin UI: responsive layouts for Dashboard, Inbox, Rules, and Settings on phones and tablets; WordPress list-table mobile rows for inbox and rules tables; admin bar notice dropdown clamps to the viewport.
* Notice Inbox: pagination controls with AJAX sort/filter; column alignment; select-all, snooze menu, and URL state (including `orderby=type&order=desc`) stay in sync.
* Notice Inbox: Read disables after use; snooze (1 or 7 days) toggles like Convert to Rule—click Snooze again or the same duration to unsnooze without reloading.
* Auto-Rules: CSS selector rules match notice markup; preset convert/delete stay in sync with the rules list; toggle/save/delete show errors and empty state when the last rule is removed.
* Auto-Rules: delete uses the WordPress admin delete link style and core confirmation dialog.
* Settings (Notifications): choose which weekday the weekly digest email is sent (site timezone, 9:00 AM local); preview shows the next scheduled send time.
* Settings (Notifications): register a weekly WP-Cron interval so the email digest can schedule when enabled; preserve webhook URL when the integration is turned off.
* Settings (Notifications): load digest and webhook live previews when opening the tab (including direct links with `?tab=notifications`).
* Settings (Filtering): custom upsell blacklist phrases suppress matching notices without enabling the Kill All Upsells preset; spam words, whitelist, and filtering lists persist when other settings tabs are saved.
* Admin bar dropdown: panel positioning stays within the viewport and no longer overlaps the admin menu when the bell is on the left side of the toolbar.
* Admin bar bell styles: fixed bell trigger using the wrong Dashicon (now shows the bell icon in the toolbar and settings preview).
* Settings (Admin bar): removed separate “Show notices in the WordPress admin bar” toggle; panel styling and placement settings apply whenever the admin bar panel is active for your display experience.
* Settings (General): removed “Hidden notices in toolbar” option; use the Admin bar tab to control the notice panel.
* Settings (General): removed duplicate Enable plugin toggle; use Dashboard Quick Status to turn interception on or off.
* Plugin admin screens: hide WordPress admin notices (including debug test banners) so dashboard and settings layouts stay clean; live test notices still fire on the main Dashboard home as documented.
* Settings (General): Display experience presets with live layout preview — leave notices on the dashboard, clean dashboard with inbox/admin bar (recommended), or toolbar-only; advanced section retains raw hide/show modes.
* Settings (General): Live preview for update nags and reset hidden notices — sample core/plugin update banners and a hidden notice update as you toggle options or run Reset.
* Settings: Access & visibility is its own tab (role ACL and per-user hiding); the Notices tab covers notice handling only.
* Settings (Admin bar): Additional panel positions — left bar (after site name or updates), top-right first slot, and before or after the Howdy profile menu.
* Settings (Admin bar): Live panel preview on the right — updates as you change style, position, badge, and colors.
* Settings (Admin bar): Additional panel styles — bell icon link to inbox, detailed dropdown, and compact list dropdown.
* Settings (Notifications): Weekly Notice Diet digest — customize subject, opening/closing text, stats and inbox sections, and preview the email before saving.
* Settings (Notifications): Webhook alerts — customize the alert message text with placeholders for source, notice body, type, and site; live preview of alert text and JSON payload below the message field.
* Settings (Access & visibility): ACL and per-user visibility — role-based display/hide plus hide from all, only selected users, or all except selected (user search picker).
* Settings (General): Spam words and whitelist — comma-separated word lists to hide matching notices entirely or always show them on screen.
* Settings (General): Block ad redirects — list admin URLs to block automatic post-install or promotional page loads (optional enable toggle).
* Settings (General): manage admin notices mode (show all, hide all, hide selected, compact panel), disable plugin/core update notifications, and reset hidden notice preferences per user or site-wide.
* Visible admin notices: inline “Hide for me” and “Hide for all” actions (per-user or site-wide hash preferences; “Hide for all” can auto-create a rule).
* Admin bar notification dropdown: full notice preview with “First logged”, “Display notice as normal”, “Hide for me”, and “Hide for all” actions.
* Admin bar beside Howdy: Facebook-style bell icon with red count badge (replaces text “Admin notices” look); notification dropdown uses FB-like panel styling.
* Settings (Notices): Notice handling — standard levels (success, error, warning, info), non-standard no-level notices, and CSS ignore selectors (combined UI with shared mode legend).
* Debug tools: choose notice levels (error, warning, success, info, upsell) before Add to Inbox or Trigger Live Notices.
* Organize Settings into tabbed sections (General, Notices, Access & visibility, Admin bar, Filtering, Security, Notifications, Migration & Tools).
* Add admin bar panel style and position options: notification dropdown (bell icon) and placement beside the Howdy profile menu.
* Add unread indicator options for the bell: number badge, 9+ / 99+ caps, dot, pulsing dot, or hidden (count still available to screen readers).
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
