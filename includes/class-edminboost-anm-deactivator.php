<?php
/**
 * Plugin deactivation handler.
 *
 * Purpose: Clear scheduled hooks or transient cleanup on deactivate.
 *
 * @package EdminBoost\AdminNoticeManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin deactivation.
 */
class EDMINBOOST_ANM_Deactivator {

	/**
	 * Run deactivation tasks.
	 *
	 * @return void
	 */
	public static function deactivate() {
		require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-admin.php';
		EDMINBOOST_ANM_Admin::clear_activation_redirect_transients();

		require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-digest.php';
		EDMINBOOST_ANM_Digest::clear_schedule();
	}
}
