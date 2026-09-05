<?php
/**
 * Plugin deactivation handler.
 *
 * Purpose: Reserved for clearing scheduled hooks or transient cleanup on deactivate.
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
		delete_transient( 'edminboost_anm_activation_redirect' );
	}
}
