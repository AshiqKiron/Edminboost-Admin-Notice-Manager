<?php
/**
 * Uninstall cleanup.
 *
 * Purpose: Remove all plugin options when the plugin is deleted (not deactivated).
 *
 * @package EdminBoost\AdminNoticeManager
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-edminboost-anm-settings.php';

delete_option( EDMINBOOST_ANM_Settings::OPTION_NAME );
delete_option( EDMINBOOST_ANM_Settings::VERSION_OPTION );
