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
	 * @param bool $network_wide Whether the plugin is being activated network-wide.
	 * @return void
	 */
	public static function activate( $network_wide = false ) {
		require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-multisite.php';

		if ( is_multisite() && $network_wide ) {
			EDMINBOOST_ANM_Multisite::seed_network_options();

			$sites = get_sites(
				array(
					'number' => 0,
					'fields' => 'ids',
				)
			);

			foreach ( $sites as $blog_id ) {
				switch_to_blog( (int) $blog_id );
				self::activate_site();
				restore_current_blog();
			}

			return;
		}

		self::activate_site();
	}

	/**
	 * Seed options for the current site/blog.
	 *
	 * @return void
	 */
	public static function activate_site() {
		require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-settings.php';
		require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-stats.php';
		require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-rules.php';
		require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-inbox.php';
		require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-digest.php';

		$defaults = EDMINBOOST_ANM_Settings::get_defaults();

		if ( false === get_option( EDMINBOOST_ANM_Settings::OPTION_NAME, false ) ) {
			add_option( EDMINBOOST_ANM_Settings::OPTION_NAME, $defaults, '', false );
		}

		if ( false === get_option( EDMINBOOST_ANM_Settings::RULES_OPTION, false ) ) {
			add_option( EDMINBOOST_ANM_Settings::RULES_OPTION, array(), '', false );
		}

		if ( false === get_option( EDMINBOOST_ANM_Settings::INBOX_OPTION, false ) ) {
			add_option( EDMINBOOST_ANM_Settings::INBOX_OPTION, array(), '', false );
		}

		if ( false === get_option( EDMINBOOST_ANM_Settings::STATS_OPTION, false ) ) {
			$stats                        = EDMINBOOST_ANM_Stats::get_defaults();
			$stats['preset_phrase_count'] = EDMINBOOST_ANM_Rules::get_preset_phrase_count();
			add_option( EDMINBOOST_ANM_Settings::STATS_OPTION, $stats, '', false );
		}

		update_option( EDMINBOOST_ANM_Settings::VERSION_OPTION, EDMINBOOST_ANM_VERSION, false );

		add_filter( 'cron_schedules', array( 'EDMINBOOST_ANM_Digest', 'add_cron_schedules' ) );
		EDMINBOOST_ANM_Digest::sync_schedule( $defaults );

		set_transient( 'edminboost_anm_activation_redirect', 1, 30 );
	}
}
