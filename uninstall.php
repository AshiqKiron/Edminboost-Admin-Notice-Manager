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
require_once plugin_dir_path( __FILE__ ) . 'includes/class-edminboost-anm-digest.php';

delete_option( EDMINBOOST_ANM_Settings::OPTION_NAME );
delete_option( EDMINBOOST_ANM_Settings::RULES_OPTION );
delete_option( EDMINBOOST_ANM_Settings::INBOX_OPTION );
delete_option( EDMINBOOST_ANM_Settings::STATS_OPTION );
delete_option( EDMINBOOST_ANM_Settings::HASH_PREFS_OPTION );
delete_option( EDMINBOOST_ANM_Settings::VERSION_OPTION );

EDMINBOOST_ANM_Digest::clear_schedule();
