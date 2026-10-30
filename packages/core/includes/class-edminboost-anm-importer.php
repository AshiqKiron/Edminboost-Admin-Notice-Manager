<?php
/**
 * Competitor importer and JSON rules export/import.
 *
 * @package EdminBoost\AdminNoticeManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Importer and exporter.
 */
class EDMINBOOST_ANM_Importer {

	/**
	 * Legacy option keys to detect.
	 *
	 * @var array
	 */
	private static $legacy_options = array(
		'anm_settings',
		'admin_notices_manager_rules',
		'admin_notices_manager_settings',
		'wp_admin_notices_manager',
	);

	/**
	 * Detect legacy competitor plugin data.
	 *
	 * @return array
	 */
	public static function detect_legacy() {
		$found = array();

		foreach ( self::$legacy_options as $option ) {
			$value = get_option( $option, false );
			if ( false !== $value ) {
				$found[] = $option;
			}
		}

		if ( file_exists( WP_PLUGIN_DIR . '/admin-notices-manager/admin-notices-manager.php' ) ) {
			$found[] = 'admin-notices-manager';
		}

		return array_unique( $found );
	}

	/**
	 * Import rules from legacy options.
	 *
	 * @return array|WP_Error
	 */
	public static function import_legacy() {
		$imported = 0;
		$legacy   = self::detect_legacy();

		if ( empty( $legacy ) ) {
			return new WP_Error( 'no_legacy', __( 'No legacy plugin data detected.', EDMINBOOST_ANM_TEXT_DOMAIN ) );
		}

		foreach ( $legacy as $key ) {
			if ( 'admin-notices-manager' === $key ) {
				continue;
			}

			$data = get_option( $key, array() );
			if ( ! is_array( $data ) ) {
				continue;
			}

			$rules = isset( $data['rules'] ) ? $data['rules'] : $data;

			if ( ! is_array( $rules ) ) {
				continue;
			}

			foreach ( $rules as $rule ) {
				if ( ! is_array( $rule ) ) {
					continue;
				}

				$name   = $rule['name'] ?? $rule['pattern'] ?? $rule['keyword'] ?? '';
				$string = $rule['match_string'] ?? $rule['pattern'] ?? $rule['keyword'] ?? $name;

				if ( '' === $name && '' === $string ) {
					continue;
				}

				$result = EDMINBOOST_ANM_Rules::save(
					array(
						'name'         => $name ?: $string,
						'match_type'   => $rule['match_type'] ?? 'keyword',
						'match_string' => $string,
						'action'       => 'auto_dismiss_inbox',
						'enabled'      => ! empty( $rule['enabled'] ),
					)
				);

				if ( ! is_wp_error( $result ) ) {
					++$imported;
				}
			}
		}

		if ( 0 === $imported ) {
			$defaults = array(
				array(
					'name'         => 'Upgrade to Pro',
					'match_string' => 'upgrade to pro',
				),
				array(
					'name'         => 'Black Friday',
					'match_string' => 'black friday',
				),
				array(
					'name'         => 'Leave a review',
					'match_string' => 'leave a review',
				),
			);

			foreach ( $defaults as $rule ) {
				EDMINBOOST_ANM_Rules::save(
					array(
						'name'         => $rule['name'],
						'match_type'   => 'keyword',
						'match_string' => $rule['match_string'],
						'action'       => 'auto_dismiss_inbox',
						'enabled'      => true,
					)
				);
				++$imported;
			}
		}

		return array(
			'imported' => $imported,
			'sources'  => $legacy,
		);
	}

	/**
	 * Export rules and settings subset as JSON.
	 *
	 * @return string
	 */
	public static function export_json() {
		$settings = EDMINBOOST_ANM_Settings::get();

		$data = array(
			'version'  => EDMINBOOST_ANM_VERSION,
			'exported' => gmdate( 'c' ),
			'rules'    => EDMINBOOST_ANM_Rules::get_all(),
			'settings' => array(
				'upsell_preset' => $settings['upsell_preset'],
				'safety_guard'  => $settings['safety_guard'],
				'client_shield' => $settings['client_shield'],
			),
		);

		return wp_json_encode( $data, JSON_PRETTY_PRINT );
	}

	/**
	 * Import rules from JSON string.
	 *
	 * @param string $json JSON content.
	 * @return array|WP_Error
	 */
	public static function import_json( $json ) {
		$data = json_decode( $json, true );

		if ( ! is_array( $data ) ) {
			return new WP_Error( 'invalid_json', __( 'Invalid JSON file.', EDMINBOOST_ANM_TEXT_DOMAIN ) );
		}

		$imported = 0;

		if ( ! empty( $data['rules'] ) && is_array( $data['rules'] ) ) {
			foreach ( $data['rules'] as $rule ) {
				if ( ! is_array( $rule ) ) {
					continue;
				}

				$result = EDMINBOOST_ANM_Rules::save( $rule );
				if ( ! is_wp_error( $result ) ) {
					++$imported;
				}
			}
		}

		return array(
			'imported' => $imported,
		);
	}
}
