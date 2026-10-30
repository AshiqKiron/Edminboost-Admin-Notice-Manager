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
