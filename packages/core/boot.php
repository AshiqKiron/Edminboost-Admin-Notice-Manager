<?php
/**
 * Shared bootstrap: constants, dependency load, activation hooks, run plugin.
 *
 * @package EdminBoost\AdminNoticeManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register plugin constants and load dependencies.
 *
 * @param string $plugin_file Main plugin file path (__FILE__ from edition bootstrap).
 * @param array  $edition     {
 *     @type bool   $is_pro       Pro edition flag.
 *     @type string $slug         Plugin slug directory name.
 *     @type string $version      Plugin version.
 *     @type string $plugin_name  Optional. Human-readable name for filters.
 * }
 * @return void
 */
function edminboost_anm_register( $plugin_file, array $edition ) {
	define( 'EDMINBOOST_ANM_VERSION', $edition['version'] );
	define( 'EDMINBOOST_ANM_IS_PRO', ! empty( $edition['is_pro'] ) );
	define( 'EDMINBOOST_ANM_PLUGIN_FILE', $plugin_file );

	$core_dir = ! empty( $edition['core_dir'] ) ? trailingslashit( $edition['core_dir'] ) : plugin_dir_path( $plugin_file );
	$core_url = ! empty( $edition['core_url'] ) ? trailingslashit( $edition['core_url'] ) : plugin_dir_url( $plugin_file );

	define( 'EDMINBOOST_ANM_PLUGIN_DIR', $core_dir );
	define( 'EDMINBOOST_ANM_PLUGIN_URL', $core_url );
	define( 'EDMINBOOST_ANM_PLUGIN_BASENAME', plugin_basename( $plugin_file ) );
	define( 'EDMINBOOST_ANM_PLUGIN_SLUG', $edition['slug'] );
	define( 'EDMINBOOST_ANM_TEXT_DOMAIN', 'edminboost-admin-notice-manager' );
	define( 'EDMINBOOST_ANM_PLUGIN_DOCS_URL', 'https://asphaltthemes.com/edminboost/admin-notice-manager/docs' );
	define( 'EDMINBOOST_ANM_PRO_URL', 'https://asphaltthemes.com/edminboost/admin-notice-manager/pricing' );

	require_once EDMINBOOST_ANM_PLUGIN_DIR . 'load.php';

	register_activation_hook( $plugin_file, array( 'EDMINBOOST_ANM_Activator', 'activate' ) );
	register_deactivation_hook( $plugin_file, array( 'EDMINBOOST_ANM_Deactivator', 'deactivate' ) );
}

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
