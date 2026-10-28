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
	 * Register the admin menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_menu_page(
			__( 'Notice Manager', EDMINBOOST_ANM_TEXT_DOMAIN ),
			__( 'Notice Manager', EDMINBOOST_ANM_TEXT_DOMAIN ),
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

		if ( ! get_transient( 'edminboost_anm_activation_redirect' ) ) {
			return;
		}

		delete_transient( 'edminboost_anm_activation_redirect' );

		if ( isset( $_GET['activate-multi'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
		exit;
	}

	/**
	 * Enqueue admin styles on plugin screens.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_styles( $hook_suffix ) {
		if ( ! self::is_plugin_screen( $hook_suffix ) ) {
			return;
		}

		$style_deps = array();
		if ( false !== strpos( (string) $hook_suffix, self::PAGE_SLUG . '-inbox' )
			|| false !== strpos( (string) $hook_suffix, self::PAGE_SLUG . '-settings' ) ) {
			wp_enqueue_style( 'list-tables' );
			$style_deps[] = 'list-tables';
		}

		wp_enqueue_style( 'dashicons' );
		$style_deps[] = 'dashicons';

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
		if ( ! self::is_plugin_screen( $hook_suffix ) ) {
			return;
		}

		wp_enqueue_script(
			'edminboost-anm-admin',
			EDMINBOOST_ANM_PLUGIN_URL . 'admin/js/edminboost-anm-admin.js',
			array(),
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

		return array(
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'edminboost_anm_admin' ),
			'pageSlug' => self::PAGE_SLUG,
			'noticePanelPositionLayouts' => $notice_panel_position_layouts,
			'i18n'     => array(
				'saved'           => __( 'Settings saved.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'confirmDelete'   => __( 'Delete this rule?', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'ruleSaved'       => __( 'Rule saved.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'ruleDeleted'     => __( 'Rule deleted.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'importSuccess'   => __( 'Import completed.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'importError'     => __( 'Import failed.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'actionSuccess'   => __( 'Action completed.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'actionError'     => __( 'Action failed.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'createRule'      => __( 'Create New Filter Rule', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'editRule'        => __( 'Edit Filter Rule', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'statusActive'    => __( 'Active', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'statusInactive'  => __( 'Inactive', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'seedSuccess'     => __( 'Added %1$d test notices to the inbox (%2$d skipped as duplicates).', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'seedError'       => __( 'Could not seed test notices.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'triggerError'    => __( 'Could not trigger live test notices.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'selectNoticeLevels' => __( 'Select at least one notice level.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'viewInbox'       => __( 'View Inbox', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'convertedToRule'  => __( 'Converted to rule', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'undoConversion'   => __( 'Converted to rule. Click again to undo.', EDMINBOOST_ANM_TEXT_DOMAIN ),
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
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '-settings' ) ),
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

		$links[] = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( EDMINBOOST_ANM_PLUGIN_DOCS_URL ),
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
		$search  = sanitize_text_field( $source['s'] ?? '' );
		$page    = max( 1, (int) ( $source['paged'] ?? 1 ) );
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
				'per_page' => 9999,
				'orderby'  => $args['orderby'],
				'order'    => $args['order'],
			)
		);

		$total  = count( $all_notices );
		$offset = ( $args['page'] - 1 ) * $per_page;

		return array(
			'notices'  => array_slice( $all_notices, $offset, $per_page ),
			'total'    => $total,
			'page'     => $args['page'],
			'per_page' => $per_page,
			'start'    => $total > 0 ? $offset + 1 : 0,
			'end'      => min( $args['page'] * $per_page, $total ),
		);
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

		$filter   = $list_args['filter'];
		$search   = $list_args['search'];
		$page     = $list_args['page'];
		$orderby  = $list_args['orderby'];
		$order    = $list_args['order'];
		$notices  = $slice['notices'];
		$total    = $slice['total'];
		$per_page = $slice['per_page'];
		$counts   = EDMINBOOST_ANM_Inbox::get_counts();

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

		$settings   = EDMINBOOST_ANM_Settings::get();
		$rules      = EDMINBOOST_ANM_Rules::get_all();
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
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( EDMINBOOST_ANM_Settings::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', EDMINBOOST_ANM_TEXT_DOMAIN ) );
		}

		$settings     = EDMINBOOST_ANM_Settings::get();
		$legacy       = EDMINBOOST_ANM_Importer::detect_legacy();
		$export_url   = wp_nonce_url(
			admin_url( 'admin-post.php?action=edminboost_anm_export_rules' ),
			'edminboost_anm_export_rules'
		);

		include EDMINBOOST_ANM_PLUGIN_DIR . 'admin/partials/edminboost-anm-settings-page.php';
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
