<?php
/**
 * Plugin activation handler.
 *
 * Purpose: Seed default options on first install and record plugin version.
 *
 * @package EdminBoost\AdminNoticeManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin activation.
 */
class EDMINBOOST_ANM_Activator {

	/**
	 * Run activation tasks.
	 *
	 * @return void
	 */
	public static function activate() {
		require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-settings.php';

		$defaults = EDMINBOOST_ANM_Settings::get_defaults();

		if ( false === get_option( EDMINBOOST_ANM_Settings::OPTION_NAME, false ) ) {
			add_option( EDMINBOOST_ANM_Settings::OPTION_NAME, $defaults, '', false );
		}

		update_option( EDMINBOOST_ANM_Settings::VERSION_OPTION, EDMINBOOST_ANM_VERSION, false );

		set_transient( 'edminboost_anm_activation_redirect', 1, 30 );
	}
}
