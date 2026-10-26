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

		wp_enqueue_style(
			'edminboost-anm-admin',
			EDMINBOOST_ANM_PLUGIN_URL . 'admin/css/edminboost-anm-admin.css',
			array(),
			EDMINBOOST_ANM_VERSION
		);
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
		return array(
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'edminboost_anm_admin' ),
			'pageSlug' => self::PAGE_SLUG,
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
				'viewInbox'       => __( 'View Inbox', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'convertedToRule' => __( 'Converted to rule', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'alreadyConverted' => __( 'Already converted to a rule.', EDMINBOOST_ANM_TEXT_DOMAIN ),
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
	 * Render the inbox page.
	 *
	 * @return void
	 */
	public function render_inbox_page() {
		if ( ! current_user_can( EDMINBOOST_ANM_Settings::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', EDMINBOOST_ANM_TEXT_DOMAIN ) );
		}

		$settings = EDMINBOOST_ANM_Settings::get();
		$filter   = sanitize_key( wp_unslash( $_GET['filter'] ?? 'all' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search   = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page     = max( 1, (int) ( $_GET['paged'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$orderby  = sanitize_key( wp_unslash( $_GET['orderby'] ?? 'date' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order    = strtolower( sanitize_key( wp_unslash( $_GET['order'] ?? 'desc' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! in_array( $orderby, array( 'source', 'message', 'type', 'status', 'date' ), true ) ) {
			$orderby = 'date';
		}
		if ( ! in_array( $order, array( 'asc', 'desc' ), true ) ) {
			$order = 'date' === $orderby ? 'desc' : 'asc';
		}

		$all_notices = EDMINBOOST_ANM_Inbox::query(
			array(
				'filter'   => $filter,
				'search'   => $search,
				'per_page' => 9999,
				'orderby'  => $orderby,
				'order'    => $order,
			)
		);

		$per_page    = 20;
		$total       = count( $all_notices );
		$offset      = ( $page - 1 ) * $per_page;
		$notices     = array_slice( $all_notices, $offset, $per_page );
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
	 * Whether the current request is a plugin admin screen.
	 *
	 * @param string|null $hook_suffix Optional hook suffix.
	 * @return bool
	 */
	public static function is_plugin_screen( $hook_suffix = null ) {
		if ( null === $hook_suffix && function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();

			if ( ! $screen ) {
				return false;
			}

			return false !== strpos( $screen->id, self::PAGE_SLUG );
		}

		if ( is_string( $hook_suffix ) && '' !== $hook_suffix ) {
			return false !== strpos( $hook_suffix, self::PAGE_SLUG );
		}

		return false;
	}
}
