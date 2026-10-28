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
require_once plugin_dir_path( __FILE__ ) . 'includes/class-edminboost-anm-multisite.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-edminboost-anm-digest.php';

if ( is_multisite() ) {
	EDMINBOOST_ANM_Multisite::uninstall_all_sites();
} else {
	EDMINBOOST_ANM_Settings::delete_site_options();
}
