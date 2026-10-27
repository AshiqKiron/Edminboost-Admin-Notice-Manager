<?php
/**
 * Plugin bootstrap — defines constants, loads dependencies, registers lifecycle hooks.
 *
 * Purpose: Single entry point. No business logic here; delegates to EDMINBOOST_ANM_Plugin.
 *
 * Plugin Name: Edminboost - Admin Notice Manager
 * Plugin URI: https://asphaltthemes.com/edminboost/admin-notice-manager
 * Description: Manage, organize, and control WordPress admin notices from one central dashboard.
 * Version: 1.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: ashiquzzaman
 * Author URI: https://asphaltthemes.com
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: edminboost-admin-notice-manager
 * Domain Path: /languages
 *
 * @package EdminBoost\AdminNoticeManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EDMINBOOST_ANM_VERSION', '1.1.0' );
define( 'EDMINBOOST_ANM_PLUGIN_FILE', __FILE__ );
define( 'EDMINBOOST_ANM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EDMINBOOST_ANM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'EDMINBOOST_ANM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'EDMINBOOST_ANM_PLUGIN_SLUG', 'edminboost-admin-notice-manager' );
define( 'EDMINBOOST_ANM_TEXT_DOMAIN', 'edminboost-admin-notice-manager' );
define( 'EDMINBOOST_ANM_PLUGIN_DOCS_URL', 'https://asphaltthemes.com/edminboost/admin-notice-manager/docs' );

require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-activator.php';
require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-deactivator.php';
require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-settings.php';
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

register_activation_hook( __FILE__, array( 'EDMINBOOST_ANM_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'EDMINBOOST_ANM_Deactivator', 'deactivate' ) );

/**
 * Initialize and run the plugin.
 *
 * @return EDMINBOOST_ANM_Plugin
 */
function edminboost_anm_run_plugin() {
	static $plugin = null;

	if ( null === $plugin ) {
		$plugin = new EDMINBOOST_ANM_Plugin();
		$plugin->run();
	}

	return $plugin;
}

edminboost_anm_run_plugin();
