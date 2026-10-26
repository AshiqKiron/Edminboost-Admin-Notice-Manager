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
	 * Rules option name.
	 *
	 * @var string
	 */
	const RULES_OPTION = 'edminboost_anm_rules';

	/**
	 * Inbox option name.
	 *
	 * @var string
	 */
	const INBOX_OPTION = 'edminboost_anm_inbox';

	/**
	 * Stats option name.
	 *
	 * @var string
	 */
	const STATS_OPTION = 'edminboost_anm_stats';

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
			'enabled'           => false,
			'hide_core_notices' => false,
			'notice_panel'      => array(
				'enabled'     => true,
				'style'       => 'link',
				'position'    => 'default',
				'badge_style' => 'count',
			),
			'upsell_preset'     => array(
				'enabled'         => false,
				'custom_phrases'  => array(),
			),
			'safety_guard'      => array(
				'enabled' => true,
				'mode'    => 'strict',
			),
			'client_shield'     => array(
				'enabled' => false,
				'roles'   => array( 'editor', 'author', 'shop_manager' ),
			),
			'digest'            => array(
				'enabled'     => false,
				'recipients'  => get_option( 'admin_email' ),
			),
			'webhook'           => array(
				'enabled' => false,
				'url'     => '',
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

		$nested_keys = array( 'notice_panel', 'upsell_preset', 'safety_guard', 'client_shield', 'digest', 'webhook' );

		foreach ( $nested_keys as $key ) {
			$merged[ $key ] = wp_parse_args(
				isset( $merged[ $key ] ) && is_array( $merged[ $key ] ) ? $merged[ $key ] : array(),
				$defaults[ $key ]
			);
		}

		if ( ! is_array( $merged['client_shield']['roles'] ) ) {
			$merged['client_shield']['roles'] = $defaults['client_shield']['roles'];
		}

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

		$allowed_styles = array( 'link', 'dropdown' );
		$panel_style      = sanitize_key( $panel_input['style'] ?? 'link' );
		if ( ! in_array( $panel_style, $allowed_styles, true ) ) {
			$panel_style = 'link';
		}

		$allowed_positions = array( 'default', 'before_profile' );
		$panel_position    = sanitize_key( $panel_input['position'] ?? 'default' );
		if ( ! in_array( $panel_position, $allowed_positions, true ) ) {
			$panel_position = 'default';
		}

		$allowed_badge_styles = array( 'count', 'dot' );
		$panel_badge_style    = sanitize_key( $panel_input['badge_style'] ?? 'count' );
		if ( ! in_array( $panel_badge_style, $allowed_badge_styles, true ) ) {
			$panel_badge_style = 'count';
		}

		$output['notice_panel'] = array(
			'enabled'     => ! empty( $panel_input['enabled'] ),
			'style'       => $panel_style,
			'position'    => $panel_position,
			'badge_style' => $panel_badge_style,
		);

		$upsell_input = isset( $input['upsell_preset'] ) && is_array( $input['upsell_preset'] )
			? $input['upsell_preset']
			: array();

		$output['upsell_preset'] = array(
			'enabled'        => ! empty( $upsell_input['enabled'] ),
			'custom_phrases' => self::sanitize_custom_phrases( $upsell_input['custom_phrases'] ?? '' ),
		);

		$safety_input = isset( $input['safety_guard'] ) && is_array( $input['safety_guard'] )
			? $input['safety_guard']
			: array();

		$output['safety_guard'] = array(
			'enabled' => ! empty( $safety_input['enabled'] ),
			'mode'    => in_array( $safety_input['mode'] ?? 'strict', array( 'strict', 'normal' ), true )
				? $safety_input['mode']
				: 'strict',
		);

		$shield_input = isset( $input['client_shield'] ) && is_array( $input['client_shield'] )
			? $input['client_shield']
			: array();

		$allowed_roles = array( 'editor', 'author', 'shop_manager', 'administrator' );
		$shield_roles  = array();

		if ( ! empty( $shield_input['roles'] ) && is_array( $shield_input['roles'] ) ) {
			foreach ( $shield_input['roles'] as $role ) {
				$role = sanitize_key( $role );
				if ( in_array( $role, $allowed_roles, true ) ) {
					$shield_roles[] = $role;
				}
			}
		}

		$output['client_shield'] = array(
			'enabled' => ! empty( $shield_input['enabled'] ),
			'roles'   => ! empty( $shield_roles ) ? $shield_roles : $defaults['client_shield']['roles'],
		);

		$digest_input = isset( $input['digest'] ) && is_array( $input['digest'] )
			? $input['digest']
			: array();

		$output['digest'] = array(
			'enabled'    => ! empty( $digest_input['enabled'] ),
			'recipients' => sanitize_text_field( $digest_input['recipients'] ?? $defaults['digest']['recipients'] ),
		);

		$webhook_input = isset( $input['webhook'] ) && is_array( $input['webhook'] )
			? $input['webhook']
			: array();

		$output['webhook'] = array(
			'enabled' => ! empty( $webhook_input['enabled'] ),
			'url'     => ! empty( $webhook_input['enabled'] )
				? esc_url_raw( $webhook_input['url'] ?? '' )
				: '',
		);

		$output['notice_panel'] = wp_parse_args( $output['notice_panel'], $defaults['notice_panel'] );

		if ( class_exists( 'EDMINBOOST_ANM_Digest' ) ) {
			EDMINBOOST_ANM_Digest::sync_schedule( $output );
		}

		/**
		 * Filter sanitized plugin settings.
		 *
		 * @param array $output Sanitized settings.
		 * @param array $input  Raw input.
		 */
		return apply_filters( 'edminboost_anm_sanitize_settings', $output, $input );
	}

	/**
	 * Sanitize custom upsell blacklist phrases.
	 *
	 * @param string|array $input Raw phrase list (newline string or array).
	 * @return array
	 */
	public static function sanitize_custom_phrases( $input ) {
		if ( is_array( $input ) ) {
			$lines = $input;
		} elseif ( is_string( $input ) ) {
			$lines = preg_split( '/\r\n|\r|\n/', $input );
		} else {
			return array();
		}

		$phrases = array();
		$seen    = array();

		foreach ( $lines as $line ) {
			$phrase = trim( sanitize_text_field( $line ) );

			if ( '' === $phrase ) {
				continue;
			}

			$key = strtolower( $phrase );

			if ( isset( $seen[ $key ] ) ) {
				continue;
			}

			$seen[ $key ] = true;
			$phrases[]    = $phrase;
		}

		return $phrases;
	}
}
