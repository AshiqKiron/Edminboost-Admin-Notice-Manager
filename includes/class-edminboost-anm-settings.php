<?php
/**
 * Centralized plugin settings — defaults, retrieval, sanitization.
 *
 * Purpose: Single source of truth for the edminboost_anm_settings option.
 *
 * @package EdminBoost\AdminNoticeManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings helper.
 */
class EDMINBOOST_ANM_Settings {

	/**
	 * Settings option name.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'edminboost_anm_settings';

	/**
	 * Installed version option name.
	 *
	 * @var string
	 */
	const VERSION_OPTION = 'edminboost_anm_version';

	/**
	 * Settings API group identifier.
	 *
	 * @var string
	 */
	const SETTINGS_GROUP = 'edminboost_anm_settings_group';

	/**
	 * Required capability for settings access.
	 *
	 * @var string
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	public static function get_defaults() {
		$defaults = array(
			'enabled'           => true,
			'hide_core_notices' => false,
			'notice_panel'      => array(
				'enabled' => true,
			),
		);

		/**
		 * Filter default plugin settings.
		 *
		 * @param array $defaults Default settings.
		 */
		return apply_filters( 'edminboost_anm_settings_defaults', $defaults );
	}

	/**
	 * Get merged plugin settings.
	 *
	 * @return array
	 */
	public static function get() {
		$settings = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		return self::merge_settings( $settings );
	}

	/**
	 * Whether the plugin is globally enabled.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$settings = self::get();

		return ! empty( $settings['enabled'] );
	}

	/**
	 * Merge stored settings with plugin defaults.
	 *
	 * @param array $settings Stored or empty settings.
	 * @return array
	 */
	private static function merge_settings( $settings ) {
		$defaults = self::get_defaults();
		$merged   = wp_parse_args( $settings, $defaults );

		$merged['notice_panel'] = wp_parse_args(
			isset( $merged['notice_panel'] ) && is_array( $merged['notice_panel'] ) ? $merged['notice_panel'] : array(),
			$defaults['notice_panel']
		);

		/**
		 * Filter merged plugin settings.
		 *
		 * @param array $merged   Merged settings.
		 * @param array $settings Raw stored settings.
		 */
		return apply_filters( 'edminboost_anm_settings', $merged, $settings );
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * @param array $input Raw settings input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		$defaults = self::get_defaults();
		$output   = self::get();

		$output['enabled']           = ! empty( $input['enabled'] );
		$output['hide_core_notices'] = ! empty( $input['hide_core_notices'] );

		$panel_input = isset( $input['notice_panel'] ) && is_array( $input['notice_panel'] )
			? $input['notice_panel']
			: array();

		$output['notice_panel'] = array(
			'enabled' => ! empty( $panel_input['enabled'] ),
		);

		$output['notice_panel'] = wp_parse_args( $output['notice_panel'], $defaults['notice_panel'] );

		/**
		 * Filter sanitized plugin settings.
		 *
		 * @param array $output Sanitized settings.
		 * @param array $input  Raw input.
		 */
		return apply_filters( 'edminboost_anm_sanitize_settings', $output, $input );
	}
}
