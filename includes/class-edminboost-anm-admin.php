<?php
/**
 * Admin area — menus, settings pages, asset enqueuing.
 *
 * Purpose: Register admin UI via admin_menu and Settings API.
 *
 * @package EdminBoost\AdminNoticeManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin handler.
 */
class EDMINBOOST_ANM_Admin {

	/**
	 * Admin page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = EDMINBOOST_ANM_PLUGIN_SLUG;

	/**
	 * Legacy activation redirect transient (pre–per-user redirect).
	 *
	 * @var string
	 */
	const ACTIVATION_REDIRECT_LEGACY_TRANSIENT = 'edminboost_anm_activation_redirect';

	/**
	 * Register the admin menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		$menu_title = EDMINBOOST_ANM_Settings::get_admin_menu_title();

		add_menu_page(
			$menu_title,
			$menu_title,
			EDMINBOOST_ANM_Settings::CAPABILITY,
			self::PAGE_SLUG,
			array( $this, 'render_dashboard_page' ),
			'dashicons-megaphone',
			73
		);

		add_submenu_page(
			self::PAGE_SLUG,
			__( 'Dashboard', EDMINBOOST_ANM_TEXT_DOMAIN ),
			__( 'Dashboard', EDMINBOOST_ANM_TEXT_DOMAIN ),
			EDMINBOOST_ANM_Settings::CAPABILITY,
			self::PAGE_SLUG,
			array( $this, 'render_dashboard_page' )
		);

		add_submenu_page(
			self::PAGE_SLUG,
			__( 'Notice Inbox', EDMINBOOST_ANM_TEXT_DOMAIN ),
			__( 'Notice Inbox', EDMINBOOST_ANM_TEXT_DOMAIN ),
			EDMINBOOST_ANM_Settings::CAPABILITY,
			self::PAGE_SLUG . '-inbox',
			array( $this, 'render_inbox_page' )
		);

		add_submenu_page(
			self::PAGE_SLUG,
			__( 'Auto-Rules', EDMINBOOST_ANM_TEXT_DOMAIN ),
			__( 'Auto-Rules', EDMINBOOST_ANM_TEXT_DOMAIN ),
			EDMINBOOST_ANM_Settings::CAPABILITY,
			self::PAGE_SLUG . '-rules',
			array( $this, 'render_rules_page' )
		);

		add_submenu_page(
			self::PAGE_SLUG,
			__( 'Settings', EDMINBOOST_ANM_TEXT_DOMAIN ),
			__( 'Settings', EDMINBOOST_ANM_TEXT_DOMAIN ),
			EDMINBOOST_ANM_Settings::CAPABILITY,
			self::PAGE_SLUG . '-settings',
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			self::PAGE_SLUG,
			__( 'Billing', EDMINBOOST_ANM_TEXT_DOMAIN ),
			__( 'Billing', EDMINBOOST_ANM_TEXT_DOMAIN ),
			EDMINBOOST_ANM_Settings::CAPABILITY,
			self::PAGE_SLUG . '-billing',
			array( $this, 'render_billing_page' )
		);
	}

	/**
	 * Register settings via the Settings API.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			EDMINBOOST_ANM_Settings::SETTINGS_GROUP,
			EDMINBOOST_ANM_Settings::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'EDMINBOOST_ANM_Settings', 'sanitize' ),
				'default'           => EDMINBOOST_ANM_Settings::get_defaults(),
			)
		);

		if ( EDMINBOOST_ANM_Multisite::is_active() ) {
			EDMINBOOST_ANM_Multisite::seed_network_options();

			register_setting(
				EDMINBOOST_ANM_Multisite::NETWORK_POLICY_GROUP,
				EDMINBOOST_ANM_Multisite::NETWORK_OPTION,
				array(
					'type'              => 'array',
					'sanitize_callback' => array( 'EDMINBOOST_ANM_Multisite', 'sanitize_policy' ),
					'default'           => EDMINBOOST_ANM_Multisite::get_policy_defaults(),
					'show_in_rest'      => false,
					'capability'        => EDMINBOOST_ANM_Multisite::NETWORK_CAPABILITY,
				)
			);
		}
	}

	/**
	 * Register network admin menus (multisite).
	 *
	 * @return void
	 */
	public function register_network_menu() {
		if ( ! EDMINBOOST_ANM_Multisite::is_active() ) {
			return;
		}

		add_menu_page(
			__( 'Notice Manager', EDMINBOOST_ANM_TEXT_DOMAIN ),
			__( 'Notice Manager', EDMINBOOST_ANM_TEXT_DOMAIN ),
			EDMINBOOST_ANM_Multisite::NETWORK_CAPABILITY,
			EDMINBOOST_ANM_Multisite::NETWORK_PAGE_SLUG,
			array( $this, 'render_network_policy_page' ),
			'dashicons-megaphone',
			73
		);

		add_submenu_page(
			EDMINBOOST_ANM_Multisite::NETWORK_PAGE_SLUG,
			__( 'Multisite policy', EDMINBOOST_ANM_TEXT_DOMAIN ),
			__( 'Multisite policy', EDMINBOOST_ANM_TEXT_DOMAIN ),
			EDMINBOOST_ANM_Multisite::NETWORK_CAPABILITY,
			EDMINBOOST_ANM_Multisite::NETWORK_PAGE_SLUG,
			array( $this, 'render_network_policy_page' )
		);

		add_submenu_page(
			EDMINBOOST_ANM_Multisite::NETWORK_PAGE_SLUG,
			__( 'Network defaults', EDMINBOOST_ANM_TEXT_DOMAIN ),
			__( 'Network defaults', EDMINBOOST_ANM_TEXT_DOMAIN ),
			EDMINBOOST_ANM_Multisite::NETWORK_CAPABILITY,
			EDMINBOOST_ANM_Multisite::NETWORK_SETTINGS_PAGE_SLUG,
			array( $this, 'render_network_defaults_page' )
		);

		add_submenu_page(
			EDMINBOOST_ANM_Multisite::NETWORK_PAGE_SLUG,
			__( 'Network Auto-Rules', EDMINBOOST_ANM_TEXT_DOMAIN ),
			__( 'Network Auto-Rules', EDMINBOOST_ANM_TEXT_DOMAIN ),
			EDMINBOOST_ANM_Multisite::NETWORK_CAPABILITY,
			EDMINBOOST_ANM_Multisite::NETWORK_PAGE_SLUG . '-rules',
			array( $this, 'render_network_rules_page' )
		);
	}

	/**
	 * Register network Settings API options.
	 *
	 * @return void
	 */
	public function register_network_settings() {
		if ( ! EDMINBOOST_ANM_Multisite::is_active() ) {
			return;
		}

		EDMINBOOST_ANM_Multisite::seed_network_options();

		register_setting(
			EDMINBOOST_ANM_Multisite::NETWORK_POLICY_GROUP,
			EDMINBOOST_ANM_Multisite::NETWORK_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'EDMINBOOST_ANM_Multisite', 'sanitize_policy' ),
				'default'           => EDMINBOOST_ANM_Multisite::get_policy_defaults(),
				'show_in_rest'      => false,
				'capability'        => EDMINBOOST_ANM_Multisite::NETWORK_CAPABILITY,
			)
		);

		register_setting(
			EDMINBOOST_ANM_Multisite::NETWORK_SETTINGS_GROUP,
			EDMINBOOST_ANM_Multisite::NETWORK_SETTINGS_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'EDMINBOOST_ANM_Settings', 'sanitize_network_settings' ),
				'default'           => EDMINBOOST_ANM_Settings::get_defaults(),
				'show_in_rest'      => false,
				'capability'        => EDMINBOOST_ANM_Multisite::NETWORK_CAPABILITY,
			)
		);
	}

	/**
	 * Redirect to the dashboard after activation.
	 *
	 * @return void
	 */
	public function maybe_activation_redirect() {
		if ( ! current_user_can( EDMINBOOST_ANM_Settings::CAPABILITY ) ) {
			return;
		}

		$user_id = get_current_user_id();
		$key     = self::get_activation_redirect_transient_key( $user_id );
		$legacy  = get_transient( self::ACTIVATION_REDIRECT_LEGACY_TRANSIENT );

		if ( ( '' === $key || false === get_transient( $key ) ) && false === $legacy ) {
			return;
		}

		self::clear_activation_redirect_transients( $user_id );

		if ( isset( $_GET['activate-multi'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
		exit;
	}

	/**
	 * Transient key for a one-time post-activation redirect.
	 *
	 * @param int $user_id User ID (0 = current user).
	 * @return string Empty when no user is available.
	 */
	public static function get_activation_redirect_transient_key( $user_id = 0 ) {
		$user_id = $user_id > 0 ? (int) $user_id : get_current_user_id();

		if ( $user_id < 1 ) {
			return '';
		}

		return 'edminboost_anm_activation_redirect_' . $user_id;
	}

	/**
	 * Queue a redirect to the plugin dashboard for the activating user.
	 *
	 * @return void
	 */
	public static function set_activation_redirect_transient() {
		$key = self::get_activation_redirect_transient_key();

		if ( '' !== $key ) {
			set_transient( $key, 1, 30 );
		}

		delete_transient( self::ACTIVATION_REDIRECT_LEGACY_TRANSIENT );
	}

	/**
	 * Clear activation redirect flags for a user (and legacy global flag).
	 *
	 * @param int $user_id User ID (0 = current user).
	 * @return void
	 */
	public static function clear_activation_redirect_transients( $user_id = 0 ) {
		$key = self::get_activation_redirect_transient_key( $user_id );

		if ( '' !== $key ) {
			delete_transient( $key );
		}

		delete_transient( self::ACTIVATION_REDIRECT_LEGACY_TRANSIENT );
	}

	/**
	 * Enqueue admin styles on plugin screens.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_styles( $hook_suffix ) {
		if ( ! self::is_plugin_screen( $hook_suffix ) && ! EDMINBOOST_ANM_Multisite::is_network_plugin_screen( $hook_suffix ) ) {
			return;
		}

		$style_deps = array();
		if ( false !== strpos( (string) $hook_suffix, self::PAGE_SLUG . '-inbox' )
			|| false !== strpos( (string) $hook_suffix, self::PAGE_SLUG . '-rules' )
			|| false !== strpos( (string) $hook_suffix, self::PAGE_SLUG . '-settings' ) ) {
			wp_enqueue_style( 'list-tables' );
			$style_deps[] = 'list-tables';
		}

		wp_enqueue_style( 'dashicons' );
		$style_deps[] = 'dashicons';

		wp_enqueue_style( 'wp-components' );
		$style_deps[] = 'wp-components';

		wp_enqueue_style(
			'edminboost-anm-admin',
			EDMINBOOST_ANM_PLUGIN_URL . 'admin/css/edminboost-anm-admin.css',
			$style_deps,
			EDMINBOOST_ANM_VERSION
		);

		wp_add_inline_style( 'edminboost-anm-admin', EDMINBOOST_ANM_Notices::get_plugin_screen_notice_hide_css() );
	}

	/**
	 * Enqueue admin scripts on plugin screens.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_scripts( $hook_suffix ) {
		if ( ! self::is_plugin_screen( $hook_suffix ) && ! EDMINBOOST_ANM_Multisite::is_network_plugin_screen( $hook_suffix ) ) {
			return;
		}

		wp_enqueue_script( 'wp-components' );

		wp_enqueue_script(
			'edminboost-anm-admin',
			EDMINBOOST_ANM_PLUGIN_URL . 'admin/js/edminboost-anm-admin.js',
			array( 'wp-components', 'wp-element' ),
			EDMINBOOST_ANM_VERSION,
			true
		);

		wp_localize_script(
			'edminboost-anm-admin',
			'edminboostAnmData',
			self::get_localized_data()
		);
	}

	/**
	 * Localized script data for admin JS.
	 *
	 * @return array
	 */
	public static function get_localized_data() {
		$notice_panel_position_layouts = array();
		foreach ( EDMINBOOST_ANM_Settings::get_notice_panel_position_slugs() as $position_slug ) {
			$notice_panel_position_layouts[ $position_slug ] = EDMINBOOST_ANM_Settings::get_notice_panel_preview_layout( $position_slug );
		}

		$rules_context = 'site';
		if ( EDMINBOOST_ANM_Multisite::is_network_plugin_screen() ) {
			$hook = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
			if ( $hook && false !== strpos( $hook->id, EDMINBOOST_ANM_Multisite::NETWORK_PAGE_SLUG . '-rules' ) ) {
				$rules_context = 'network';
			}
		}

		return array(
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'edminboost_anm_admin' ),
			'pageSlug' => self::PAGE_SLUG,
			'rulesContext' => $rules_context,
			'multisite'    => array(
				'isMultisite'          => EDMINBOOST_ANM_Multisite::is_active(),
				'policy'               => EDMINBOOST_ANM_Multisite::get_policy(),
				'siteCanEditSettings'  => EDMINBOOST_ANM_Multisite::site_can_edit_settings(),
				'siteCanEditRules'     => EDMINBOOST_ANM_Multisite::site_can_edit_rules(),
				'networkSettingsUrl'   => EDMINBOOST_ANM_Multisite::is_active() ? EDMINBOOST_ANM_Multisite::get_network_admin_url() : '',
			),
			'noticePanelPositionLayouts' => $notice_panel_position_layouts,
			'i18n'     => array(
				'saved'           => __( 'Settings saved.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'confirmDelete'       => __( 'Are you sure you want to delete this rule?', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'confirmDeleteTitle'  => __( 'Delete rule', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'confirmDeleteButton' => _x( 'Delete', 'verb', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'cancel'              => __( 'Cancel', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'ruleSaved'       => __( 'Rule saved.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'ruleDeleted'     => __( 'Rule deleted.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'importSuccess'   => __( 'Import completed.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'importError'     => __( 'Import failed.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'actionSuccess'   => __( 'Action completed.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'actionError'     => __( 'Action failed.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'createRule'      => __( 'Create New Filter Rule', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'editRule'        => __( 'Edit Filter Rule', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'rulesEmpty'      => __( 'No auto-rules yet. Add a rule or convert an inbox notice to get started.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'statusActive'    => __( 'Active', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'statusInactive'  => __( 'Inactive', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'presetActiveBlacklist' => __( 'Active blacklist: %d phrases', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'seedSuccess'     => __( 'Added %1$d test notices to the inbox (%2$d skipped as duplicates).', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'seedError'       => __( 'Could not seed test notices.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'triggerError'    => __( 'Could not trigger live test notices.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'selectNoticeLevels' => __( 'Select at least one notice level.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'viewInbox'       => __( 'View Inbox', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'convertedToRule'  => __( 'Converted to rule', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'undoConversion'   => __( 'Converted to rule. Click again to undo.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'undoSnooze'       => __( 'Snoozed. Click again to unsnooze.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'alreadyConverted' => __( 'Already converted to a rule.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'userSearchShort'  => __( 'Type at least 2 characters to search users.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'userSearchEmpty'  => __( 'No users found.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'removeUser'       => __( 'Remove user', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'resetHiddenSuccess' => __( 'Hidden notices were reset.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'resetHiddenError'   => __( 'Could not reset hidden notices.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'resetHiddenConfirmAll' => __( 'Reset hidden notice preferences for every user on this site?', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'digestPreviewLoading'  => __( 'Loading preview…', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'digestPreviewError'    => __( 'Could not load digest preview.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'digestPreviewAtGlance' => __( 'At a glance', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'digestPreviewRecent'   => __( 'Recent intercepted notices', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'digestPreviewNoNotices'=> __( 'No intercepted notices in the inbox yet.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'digestPreviewBlocked'  => __( 'Blocked this month', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'digestPreviewRules'    => __( 'Active auto-rules', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'digestPreviewNextSendDisabled' => __( 'Enable the digest to schedule sends.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'webhookPreviewLoading' => __( 'Loading preview…', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'webhookPreviewError'   => __( 'Could not load webhook preview.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'webhookPreviewSample'  => __( 'Sample notice', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'webhookPreviewInbox'   => __( 'Based on your latest inbox notice', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'noticePanelPreviewNotices'  => __( 'Notices', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'noticePanelPreviewAdminNotices' => __( 'Admin notices', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'noticePanelPreviewSeeAll'   => __( 'See all notifications', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'noticePanelPreviewHowdy'    => __( 'Howdy, Admin', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'noticePanelPreviewSiteName' => __( 'My Site', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'noticePanelSample1'         => __( 'There is a new version of Akismet Anti-spam available. View version 5.3.3 details or update now.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'noticePanelSample1Source'   => __( 'Akismet Anti-spam', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'noticePanelSample2'         => __( 'Enjoying WPForms? Please leave us a review on WordPress.org — it only takes a minute.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'noticePanelSample2Source'   => __( 'WPForms', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'noticePanelSample3'         => __( 'Your license key expires in 14 days. Renew now to keep receiving updates and support.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'noticePanelSample3Source'   => __( 'Advanced Custom Fields', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'noticePanelSampleDetailed1' => __( 'Your license will expire in 3 days. Renew now to keep receiving updates and support.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'noticePanelSampleDetailed2' => __( 'Thanks for using Yoast SEO! Could you take a moment to leave a five-star review on WordPress.org?', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'noticePanelSampleDetailed2Source' => __( 'Yoast SEO', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'noticePanelPreviewClose'    => __( 'Close panel', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'noticePanelPreviewDismiss'  => __( 'Dismiss from panel', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'noticePanelSampleFirstLogged' => sprintf(
					/* translators: %s: sample date/time for panel preview only */
					__( 'First logged: %s', EDMINBOOST_ANM_TEXT_DOMAIN ),
					__( 'Mar 15, 2024 10:30 am', EDMINBOOST_ANM_TEXT_DOMAIN )
				),
				'noticePanelPreviewDisplayNormal' => __( 'Display notice as normal', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'noticePanelPreviewHideForMe'     => __( 'Hide for me', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'noticePanelPreviewHideForAll'    => __( 'Hide for all', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'displayExperiencePreviewCaptionShowAll'       => __( 'Notices stay on the dashboard. Inbox and admin bar stay available for optional use.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'displayExperiencePreviewCaptionHideSelected'  => __( 'Matching notices leave the dashboard and appear in the inbox and admin bar panel.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'displayExperiencePreviewCaptionHideAll'       => __( 'Every notice is suppressed on the dashboard unless a rule allows it through.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'displayExperiencePreviewDashboardTitle'       => __( 'Dashboard', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'displayExperiencePreviewDashboardClear'       => __( 'Dashboard notice area is clear.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'displayExperiencePreviewHideAllDashboard'     => __( 'No notices shown on the dashboard.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'displayExperiencePreviewInboxHint'            => __( 'Matching notices appear in the inbox and admin bar panel above.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'displayExperiencePreviewNoticeUpdate'         => __( 'WordPress 6.7 is available. Please update now.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'displayExperiencePreviewNoticeLicense'        => __( 'Please activate your SEO plugin license to receive updates.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'displayExperiencePreviewNoticeForm'           => __( 'You have a new form submission waiting in your inbox.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'generalOptionsPreviewNoticePlugin'            => __( 'There are updates available for 3 plugins. View details or update now.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'generalOptionsPreviewNoticeHidden'            => __( 'Thanks for using Contact Form 7! Please leave us a review on WordPress.org.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'generalOptionsPreviewHiddenBadge'             => __( 'Hidden for you', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'generalOptionsPreviewCaptionHideCore'         => __( 'The WordPress core update nag is hidden from the dashboard.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'generalOptionsPreviewCaptionHidePlugin'       => __( 'Plugin and theme update nags are hidden from the dashboard.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'generalOptionsPreviewCaptionHideBoth'         => __( 'Core and plugin or theme update nags are hidden from the dashboard.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'generalOptionsPreviewCaptionResetAll'         => __( 'Reset clears hidden-notice preferences for every user on this site.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'whiteLabelPreviewDefaultMenuTitle'            => __( 'Notice Manager', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'whiteLabelPreviewDefaultBrand'                => __( 'Edminboost', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'whiteLabelPreviewPluginName'                  => __( 'Edminboost - Admin Notice Manager', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'whiteLabelPreviewMenuSection'                 => __( 'Admin menu', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'whiteLabelPreviewDashboardSection'            => __( 'Dashboard top bar', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'whiteLabelPreviewPluginsSection'              => __( 'Plugins screen', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'whiteLabelPreviewQuickStatus'                 => __( 'Quick Status:', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'whiteLabelPreviewSettingsLink'                => __( 'Settings', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'whiteLabelPreviewDocsLink'                    => __( 'Docs', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'whiteLabelPreviewCaptionDisabled'             => __( 'Default menu title and Edminboost dashboard branding apply when white label is off.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'whiteLabelPreviewCaptionBrandHidden'          => __( 'Dashboard brand area is hidden on plugin screens.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'whiteLabelPreviewCaptionNoBrand'              => __( 'Leave a brand name or logo URL to show the dashboard brand area.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'whiteLabelPreviewCaptionDocsHidden'           => __( 'Docs link is hidden on the Plugins screen row.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'whiteLabelPreviewCaptionCustomDocs'           => __( 'Plugins screen Docs link uses your custom URL.', EDMINBOOST_ANM_TEXT_DOMAIN ),
			),
		);
	}

	/**
	 * Add a Settings shortcut on the Plugins screen.
	 *
	 * @param array $links Plugin action links.
	 * @return array
	 */
	public function add_settings_link( $links ) {
		$settings_url = admin_url( 'admin.php?page=' . self::PAGE_SLUG . '-settings' );
		if ( is_network_admin() && EDMINBOOST_ANM_Multisite::is_active() ) {
			$settings_url = EDMINBOOST_ANM_Multisite::get_network_admin_url();
		}

		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $settings_url ),
			esc_html__( 'Settings', EDMINBOOST_ANM_TEXT_DOMAIN )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Add Docs link on the Plugins screen.
	 *
	 * @param array  $links Plugin row meta links.
	 * @param string $file  Plugin basename.
	 * @return array
	 */
	public function add_plugin_row_meta( $links, $file ) {
		if ( EDMINBOOST_ANM_PLUGIN_BASENAME !== $file ) {
			return $links;
		}

		$docs_url = EDMINBOOST_ANM_Settings::get_plugin_docs_url();
		if ( '' === $docs_url ) {
			return $links;
		}

		$links[] = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( $docs_url ),
			esc_html__( 'Docs', EDMINBOOST_ANM_TEXT_DOMAIN )
		);

		return $links;
	}

	/**
	 * Render the dashboard page.
	 *
	 * @return void
	 */
	public function render_dashboard_page() {
		if ( ! current_user_can( EDMINBOOST_ANM_Settings::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', EDMINBOOST_ANM_TEXT_DOMAIN ) );
		}

		$settings = EDMINBOOST_ANM_Settings::get();
		$metrics  = EDMINBOOST_ANM_Stats::get_dashboard_metrics();

		include EDMINBOOST_ANM_PLUGIN_DIR . 'admin/partials/edminboost-anm-dashboard-page.php';
	}

	/**
	 * Parse inbox list query arguments from a request array.
	 *
	 * @param array $source Request parameters.
	 * @return array
	 */
	public static function parse_inbox_list_args( $source ) {
		$filter  = sanitize_key( $source['filter'] ?? 'all' );
		if ( ! in_array( $filter, EDMINBOOST_ANM_Inbox::LIST_FILTERS, true ) ) {
			$filter = 'all';
		}
		$search  = sanitize_text_field( $source['s'] ?? '' );
		$page    = max( 1, (int) ( $source['paged'] ?? $source['page'] ?? 1 ) );
		$orderby = sanitize_key( $source['orderby'] ?? 'date' );
		$order   = strtolower( sanitize_key( $source['order'] ?? 'desc' ) );

		if ( ! in_array( $orderby, array( 'source', 'message', 'type', 'date' ), true ) ) {
			$orderby = 'date';
		}
		if ( ! in_array( $order, array( 'asc', 'desc' ), true ) ) {
			$order = 'date' === $orderby ? 'desc' : 'asc';
		}

		return array(
			'filter'  => $filter,
			'search'  => $search,
			'page'    => $page,
			'orderby' => $orderby,
			'order'   => $order,
		);
	}

	/**
	 * Fetch a paginated inbox slice for the list table.
	 *
	 * @param array $args List arguments from parse_inbox_list_args().
	 * @return array
	 */
	public static function get_inbox_list_slice( $args ) {
		$per_page = 20;

		$all_notices = EDMINBOOST_ANM_Inbox::query(
			array(
				'filter'   => $args['filter'],
				'search'   => $args['search'],
				'per_page' => 0,
				'orderby'  => $args['orderby'],
				'order'    => $args['order'],
			)
		);

		$total       = count( $all_notices );
		$total_pages = $total > 0 ? (int) ceil( $total / $per_page ) : 1;
		$page        = min( max( 1, (int) $args['page'] ), $total_pages );
		$offset      = ( $page - 1 ) * $per_page;

		return array(
			'notices'     => array_slice( $all_notices, $offset, $per_page ),
			'total'       => $total,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => $total_pages,
			'start'       => $total > 0 ? $offset + 1 : 0,
			'end'         => min( $page * $per_page, $total ),
		);
	}

	/**
	 * Build inbox list URL preserving list state.
	 *
	 * @param array $list_args Arguments from parse_inbox_list_args().
	 * @param array $overrides Query overrides.
	 * @return string
	 */
	public static function get_inbox_list_url( $list_args, $overrides = array() ) {
		$query = array_merge(
			array(
				'page'   => self::PAGE_SLUG . '-inbox',
				'filter' => $list_args['filter'],
				's'      => $list_args['search'],
				'paged'  => $list_args['page'],
				'orderby' => $list_args['orderby'],
				'order'  => $list_args['order'],
			),
			$overrides
		);

		if ( 'all' === $query['filter'] ) {
			unset( $query['filter'] );
		}
		if ( '' === $query['s'] ) {
			unset( $query['s'] );
		}
		if ( $query['paged'] <= 1 ) {
			unset( $query['paged'] );
		}
		if ( 'date' === $query['orderby'] && 'desc' === $query['order'] ) {
			unset( $query['orderby'], $query['order'] );
		}

		return add_query_arg( $query, admin_url( 'admin.php' ) );
	}

	/**
	 * Render inbox pagination controls.
	 *
	 * @param array $list_args List arguments.
	 * @param array $slice     Slice from get_inbox_list_slice().
	 * @return string
	 */
	public static function render_inbox_pager_html( $list_args, $slice ) {
		$current_page = (int) ( $slice['page'] ?? 1 );
		$total_pages  = (int) ( $slice['total_pages'] ?? 1 );

		if ( $total_pages <= 1 ) {
			return '';
		}

		ob_start();
		?>
		<nav class="edminboost-anm-pager" aria-label="<?php esc_attr_e( 'Inbox pagination', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>">
			<?php if ( $current_page > 1 ) : ?>
				<a class="button edminboost-anm-inbox-page-link" href="<?php echo esc_url( self::get_inbox_list_url( $list_args, array( 'paged' => $current_page - 1 ) ) ); ?>" data-page="<?php echo esc_attr( (string) ( $current_page - 1 ) ); ?>"><?php esc_html_e( 'Previous', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></a>
			<?php endif; ?>
			<span class="edminboost-anm-pager__status">
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: current page, 2: total pages */
						__( 'Page %1$d of %2$d', EDMINBOOST_ANM_TEXT_DOMAIN ),
						$current_page,
						$total_pages
					)
				);
				?>
			</span>
			<?php if ( $current_page < $total_pages ) : ?>
				<a class="button edminboost-anm-inbox-page-link" href="<?php echo esc_url( self::get_inbox_list_url( $list_args, array( 'paged' => $current_page + 1 ) ) ); ?>" data-page="<?php echo esc_attr( (string) ( $current_page + 1 ) ); ?>"><?php esc_html_e( 'Next', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></a>
			<?php endif; ?>
		</nav>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render inbox table body rows.
	 *
	 * @param array $notices Notice rows.
	 * @return string
	 */
	public static function render_inbox_table_rows_html( $notices ) {
		ob_start();
		include EDMINBOOST_ANM_PLUGIN_DIR . 'admin/partials/edminboost-anm-inbox-table-rows.php';

		return (string) ob_get_clean();
	}

	/**
	 * Render the inbox page.
	 *
	 * @return void
	 */
	public function render_inbox_page() {
		if ( ! current_user_can( EDMINBOOST_ANM_Settings::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', EDMINBOOST_ANM_TEXT_DOMAIN ) );
		}

		$settings = EDMINBOOST_ANM_Settings::get();
		$list_args = self::parse_inbox_list_args( wp_unslash( $_GET ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$slice     = self::get_inbox_list_slice( $list_args );
		$list_args['page'] = $slice['page'];

		$filter      = $list_args['filter'];
		$search      = $list_args['search'];
		$page        = $slice['page'];
		$orderby     = $list_args['orderby'];
		$order       = $list_args['order'];
		$inbox_pager = self::render_inbox_pager_html( $list_args, $slice );
		$notices     = $slice['notices'];
		$total       = $slice['total'];
		$per_page    = $slice['per_page'];
		$total_pages = $slice['total_pages'];
		$counts      = EDMINBOOST_ANM_Inbox::get_counts();

		include EDMINBOOST_ANM_PLUGIN_DIR . 'admin/partials/edminboost-anm-inbox-page.php';
	}

	/**
	 * Render the auto-rules page.
	 *
	 * @return void
	 */
	public function render_rules_page() {
		if ( ! current_user_can( EDMINBOOST_ANM_Settings::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', EDMINBOOST_ANM_TEXT_DOMAIN ) );
		}

		$settings                        = EDMINBOOST_ANM_Settings::get();
		$rules                           = EDMINBOOST_ANM_Rules::get_all();
		$wp_presets                      = EDMINBOOST_ANM_Rules::get_wp_presets_with_status();
		$edminboost_anm_rules_readonly   = ! EDMINBOOST_ANM_Multisite::site_can_edit_rules();

		usort(
			$rules,
			static function ( $a, $b ) {
				return ( $b['hit_count'] ?? 0 ) <=> ( $a['hit_count'] ?? 0 );
			}
		);

		include EDMINBOOST_ANM_PLUGIN_DIR . 'admin/partials/edminboost-anm-rules-page.php';
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( EDMINBOOST_ANM_Settings::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', EDMINBOOST_ANM_TEXT_DOMAIN ) );
		}

		$settings                         = EDMINBOOST_ANM_Settings::get();
		$legacy                           = EDMINBOOST_ANM_Importer::detect_legacy();
		$export_url                       = wp_nonce_url(
			admin_url( 'admin-post.php?action=edminboost_anm_export_rules' ),
			'edminboost_anm_export_rules'
		);
		$edminboost_anm_settings_readonly = ! EDMINBOOST_ANM_Multisite::site_can_edit_settings();

		include EDMINBOOST_ANM_PLUGIN_DIR . 'admin/partials/edminboost-anm-settings-page.php';
	}

	/**
	 * Render the billing / plans page.
	 *
	 * @return void
	 */
	public function render_billing_page() {
		if ( ! current_user_can( EDMINBOOST_ANM_Settings::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', EDMINBOOST_ANM_TEXT_DOMAIN ) );
		}

		$edminboost_anm_billing_rows = self::get_billing_comparison_rows();

		include EDMINBOOST_ANM_PLUGIN_DIR . 'admin/partials/edminboost-anm-billing-page.php';
	}

	/**
	 * Admin URL for the billing page.
	 *
	 * @return string
	 */
	public static function get_billing_page_url() {
		return admin_url( 'admin.php?page=' . self::PAGE_SLUG . '-billing' );
	}

	/**
	 * Free vs Pro feature rows for the billing comparison table.
	 *
	 * Each row: feature (string), tip (string), free (bool|string), pro (bool|string).
	 *
	 * @return array<int, array{feature: string, tip: string, free: bool|string, pro: bool|string}>
	 */
	public static function get_billing_comparison_rows() {
		$rows = array(
			array(
				'feature' => __( 'Dashboard & notice interception', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'tip'     => __( 'Central dashboard with intercept metrics, quick status, and a live feed of blocked notices.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'free'    => true,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Notice Inbox', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'tip'     => __( 'Review captured notices with search, filters, snooze, read state, and bulk actions.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'free'    => true,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Auto-Rules (keyword, regex, CSS)', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'tip'     => __( 'Automatically hide or route notices using keyword, regular expression, or CSS selector rules.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'free'    => true,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Kill All Upsells preset', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'tip'     => __( 'One-click suppression of common review requests, sale banners, and promotional upsell phrases.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'free'    => true,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Admin bar panel (styles, badge, colors)', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'tip'     => __( 'Configure notice panel style, badge type, and custom colors in the WordPress admin bar.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'free'    => true,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Inline hide for me / hide for all', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'tip'     => __( 'Hide individual notices from the admin screen for yourself or for every administrator.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'free'    => true,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Critical Safety Guard', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'tip'     => __( 'Protect core security, fatal error, and SSL notices from being filtered by mistake.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'free'    => true,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Client Shield Mode', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'tip'     => __( 'Hide plugin and theme update nags from selected non-admin roles on the site.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'free'    => true,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Block ad redirects', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'tip'     => __( 'Stop plugins from opening promotional admin pages automatically after install or update.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'free'    => true,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Access control', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'tip'     => __( 'Limit which roles can use the plugin and who still sees native WordPress admin notices.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'free'    => true,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Word filters (spam & whitelist)', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'tip'     => __( 'Extra spam phrases to block and whitelist words that should never be filtered.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'free'    => true,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Rules export', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'tip'     => __( 'Download your auto-rules as JSON for backup or use on another site.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'free'    => true,
				'pro'     => true,
			),
			array(
				'feature' => __( 'White label', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'tip'     => __( 'Customize the admin menu title, dashboard brand, logo, and documentation link for client sites.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'free'    => false,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Rule schedule', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'tip'     => __( 'Run auto-rules only on selected weekdays and hours using your site timezone.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'free'    => false,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Migration & import', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'tip'     => __( 'Import rules from JSON and migrate settings from supported third-party notice plugins.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'free'    => false,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Multisite', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'tip'     => __( 'Network policy, default settings, and optional shared auto-rules across all sites in the network.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'free'    => false,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Webhook', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'tip'     => __( 'Send JSON alert payloads to Slack, Discord, or any URL when new notices are captured.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'free'    => false,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Email digest', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'tip'     => __( 'Weekly summary email with optional stats and recent inbox notices to addresses you choose.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'free'    => false,
				'pro'     => true,
			),
			array(
				'feature' => __( 'Panel position', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'tip'     => __( 'Place the admin bar notice panel in the left cluster, top-right slots, or beside the profile menu.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'free'    => false,
				'pro'     => true,
			),
		);

		/**
		 * Filter Free vs Pro rows on the billing page.
		 *
		 * @param array<int, array{feature: string, tip: string, free: bool|string, pro: bool|string}> $rows Comparison rows.
		 */
		return apply_filters( 'edminboost_anm_billing_comparison', $rows );
	}

	/**
	 * Render a billing comparison table cell (checkmark, dash, or text).
	 *
	 * @param bool|string $value Cell value.
	 * @return string Safe HTML.
	 */
	public static function render_billing_comparison_cell( $value ) {
		if ( true === $value ) {
			return sprintf(
				'<span class="edminboost-anm-billing-table__mark is-yes"><span class="screen-reader-text">%s</span><span class="dashicons dashicons-yes" aria-hidden="true"></span></span>',
				esc_html__( 'Included', EDMINBOOST_ANM_TEXT_DOMAIN )
			);
		}

		if ( false === $value ) {
			return sprintf(
				'<span class="edminboost-anm-billing-table__mark is-no"><span class="screen-reader-text">%s</span><span aria-hidden="true">—</span></span>',
				esc_html__( 'Not included', EDMINBOOST_ANM_TEXT_DOMAIN )
			);
		}

		return sprintf(
			'<span class="edminboost-anm-billing-table__text">%s</span>',
			esc_html( (string) $value )
		);
	}

	/**
	 * Render the network multisite policy page.
	 *
	 * @return void
	 */
	public function render_network_policy_page() {
		if ( ! current_user_can( EDMINBOOST_ANM_Multisite::NETWORK_CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', EDMINBOOST_ANM_TEXT_DOMAIN ) );
		}

		$edminboost_anm_network = EDMINBOOST_ANM_Multisite::get_policy_option_raw();

		include EDMINBOOST_ANM_PLUGIN_DIR . 'admin/partials/edminboost-anm-network-policy-page.php';
	}

	/**
	 * Render network-wide default plugin settings.
	 *
	 * @return void
	 */
	public function render_network_defaults_page() {
		if ( ! current_user_can( EDMINBOOST_ANM_Multisite::NETWORK_CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', EDMINBOOST_ANM_TEXT_DOMAIN ) );
		}

		$settings                          = EDMINBOOST_ANM_Settings::merge_settings( EDMINBOOST_ANM_Multisite::get_network_settings_raw() );
		$legacy                            = array();
		$export_url                        = '';
		$edminboost_anm_option_name        = EDMINBOOST_ANM_Multisite::NETWORK_SETTINGS_OPTION;
		$edminboost_anm_settings_group     = EDMINBOOST_ANM_Multisite::NETWORK_SETTINGS_GROUP;
		$edminboost_anm_settings_readonly  = false;
		$edminboost_anm_hide_multisite_tab = true;

		include EDMINBOOST_ANM_PLUGIN_DIR . 'admin/partials/edminboost-anm-settings-page.php';
	}

	/**
	 * Render shared network auto-rules.
	 *
	 * @return void
	 */
	public function render_network_rules_page() {
		if ( ! current_user_can( EDMINBOOST_ANM_Multisite::NETWORK_CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', EDMINBOOST_ANM_TEXT_DOMAIN ) );
		}

		$settings   = EDMINBOOST_ANM_Settings::merge_settings( EDMINBOOST_ANM_Multisite::get_network_settings_raw() );
		$rules      = EDMINBOOST_ANM_Rules::get_network_rules();
		$wp_presets = EDMINBOOST_ANM_Rules::get_wp_presets_with_status();

		usort(
			$rules,
			static function ( $a, $b ) {
				return ( $b['hit_count'] ?? 0 ) <=> ( $a['hit_count'] ?? 0 );
			}
		);

		include EDMINBOOST_ANM_PLUGIN_DIR . 'admin/partials/edminboost-anm-rules-page.php';
	}

	/**
	 * Render a settings help tip (WordPress dashicons + hover tooltip).
	 *
	 * @param string $message   Help text.
	 * @param string $placement Popup placement: `below` (default) or `left`.
	 * @return string Safe HTML.
	 */
	public static function help_tip( $message, $placement = 'below' ) {
		$message   = (string) $message;
		$label     = wp_strip_all_tags( $message );
		$placement = 'left' === $placement ? 'left' : 'below';

		return sprintf(
			'<span class="dashicons dashicons-info-outline edminboost-anm-help-tip" tabindex="0" role="button" aria-label="%1$s" data-tip="%2$s" data-tip-placement="%3$s"></span>',
			esc_attr( $label ),
			esc_attr( $message ),
			esc_attr( $placement )
		);
	}

	/**
	 * Whether the current request is a plugin admin screen.
	 *
	 * @param string|null $hook_suffix Optional hook suffix.
	 * @return bool
	 */
	public static function is_plugin_screen( $hook_suffix = null ) {
		if ( is_string( $hook_suffix ) && '' !== $hook_suffix ) {
			return false !== strpos( $hook_suffix, self::PAGE_SLUG );
		}

		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();

			if ( $screen && false !== strpos( $screen->id, self::PAGE_SLUG ) ) {
				return true;
			}
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only routing check.
		if ( isset( $_GET['page'] ) ) {
			$page = sanitize_key( wp_unslash( $_GET['page'] ) );

			return false !== strpos( $page, self::PAGE_SLUG );
		}

		return false;
	}
}
