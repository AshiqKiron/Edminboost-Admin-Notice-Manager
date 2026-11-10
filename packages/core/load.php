<?php
/**
 * Load plugin PHP dependencies (shared by Free and Pro builds).
 *
 * @package EdminBoost\AdminNoticeManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-activator.php';
require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-deactivator.php';
require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-feature-notifications.php';
require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-notification-sources.php';
require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-settings.php';
require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-multisite.php';
require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-stats.php';
require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-rules.php';
require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-inbox.php';
require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-interceptor.php';
require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-webhook.php';
require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-digest.php';
require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-importer.php';
require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-ajax.php';
require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-notices.php';
require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-ad-redirects.php';
require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-admin.php';
require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-plugin.php';

if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-debug.php';
}

/**
 * Whether the running build is the Pro edition.
 *
 * @return bool
 */
function edminboost_anm_is_pro() {
	return defined( 'EDMINBOOST_ANM_IS_PRO' ) && EDMINBOOST_ANM_IS_PRO;
}

/**
 * Agent debug log (session ab79ad) — writes NDJSON under plugin .cursor/.
 *
 * @param string $location File/function label.
 * @param string $message  Event name.
 * @param array  $data     Safe structured fields.
 * @param string $hypothesis_id Hypothesis id.
 * @return void
 */
function edminboost_anm_agent_debug_log( $location, $message, $data = array(), $hypothesis_id = '' ) {
	if ( ! defined( 'EDMINBOOST_ANM_PLUGIN_FILE' ) ) {
		return;
	}

	$log_file = dirname( EDMINBOOST_ANM_PLUGIN_FILE ) . '/.cursor/debug-ab79ad.log';
	$log_dir  = dirname( $log_file );
	if ( ! is_dir( $log_dir ) ) {
		wp_mkdir_p( $log_dir );
	}

	// #region agent log
	$line = wp_json_encode(
		array(
			'sessionId'    => 'ab79ad',
			'runId'        => 'verify',
			'hypothesisId' => $hypothesis_id,
			'location'     => $location,
			'message'      => $message,
			'data'         => is_array( $data ) ? $data : array(),
			'timestamp'    => (int) round( microtime( true ) * 1000 ),
		)
	);
	if ( is_string( $line ) ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $log_file, $line . "\n", FILE_APPEND | LOCK_EX );
	}
	// #endregion
}

/**
 * Log admin runtime context once per request (verification).
 *
 * @return void
 */
function edminboost_anm_agent_debug_admin_context() {
	if ( ! is_admin() || ! function_exists( 'edminboost_anm_agent_debug_log' ) ) {
		return;
	}

	edminboost_anm_agent_debug_log(
		'load.php:edminboost_anm_agent_debug_admin_context',
		'admin_boot',
		array(
			'plugin_enabled'   => EDMINBOOST_ANM_Settings::is_enabled(),
			'manage_mode'      => EDMINBOOST_ANM_Settings::get_manage_notices_mode(),
			'should_intercept' => EDMINBOOST_ANM_Settings::should_intercept_notices(),
			'apply_hiding'     => EDMINBOOST_ANM_Settings::should_apply_notice_hiding_for_user(),
			'is_plugin_screen' => class_exists( 'EDMINBOOST_ANM_Admin' ) && EDMINBOOST_ANM_Admin::is_plugin_screen(),
			'webhook_enabled'  => ! empty( EDMINBOOST_ANM_Settings::get()['webhook']['enabled'] ),
		),
		'C'
	);
}
