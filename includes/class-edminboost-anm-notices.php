<?php
/**
 * Admin notice handling — runtime hooks for notice management.
 *
 * Purpose: Register front-end/admin hooks that control how notices are displayed.
 *
 * @package EdminBoost\AdminNoticeManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Notice manager.
 */
class EDMINBOOST_ANM_Notices {

	/**
	 * Register runtime hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		if ( ! EDMINBOOST_ANM_Settings::is_enabled() ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		$settings = EDMINBOOST_ANM_Settings::get();

		if ( ! empty( $settings['hide_core_notices'] ) ) {
			add_action( 'admin_head', array( $this, 'hide_core_notices' ), 1 );
		}

		if ( ! empty( $settings['notice_panel']['enabled'] ) ) {
			add_action( 'admin_bar_menu', array( $this, 'register_admin_bar_node' ), 100 );
		}

		/**
		 * Fires after notice manager hooks are registered.
		 *
		 * @param EDMINBOOST_ANM_Notices $notices Notice manager instance.
		 */
		do_action( 'edminboost_anm_notices_loaded', $this );
	}

	/**
	 * Enqueue admin assets when notice features are active.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( ! is_admin() ) {
			return;
		}

		wp_enqueue_style(
			'edminboost-anm-notices',
			EDMINBOOST_ANM_PLUGIN_URL . 'admin/css/edminboost-anm-admin.css',
			array(),
			EDMINBOOST_ANM_VERSION
		);
	}

	/**
	 * Hide default admin notices outside plugin screens.
	 *
	 * @return void
	 */
	public function hide_core_notices() {
		if ( EDMINBOOST_ANM_Admin::is_plugin_screen() ) {
			return;
		}

		echo '<style>.notice, .update-nag, div.error, div.updated { display: none !important; }</style>';
	}

	/**
	 * Add an admin bar entry for the notice panel.
	 *
	 * @param WP_Admin_Bar $admin_bar Admin bar instance.
	 * @return void
	 */
	public function register_admin_bar_node( $admin_bar ) {
		if ( ! current_user_can( EDMINBOOST_ANM_Settings::CAPABILITY ) ) {
			return;
		}

		$admin_bar->add_node(
			array(
				'id'    => 'edminboost-anm-notices',
				'title' => esc_html__( 'Notices', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'href'  => admin_url( 'admin.php?page=' . EDMINBOOST_ANM_Admin::PAGE_SLUG ),
				'meta'  => array(
					'title' => esc_attr__( 'Open Admin Notice Manager', EDMINBOOST_ANM_TEXT_DOMAIN ),
				),
			)
		);
	}
}
