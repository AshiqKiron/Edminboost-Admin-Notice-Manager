<?php
/**
 * Debug helpers — sample notices for local testing (WP_DEBUG only).
 *
 * @package EdminBoost\AdminNoticeManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Debug notice generator.
 */
class EDMINBOOST_ANM_Debug {

	/**
	 * Transient key prefix for one-shot live notice display.
	 *
	 * @var string
	 */
	const TRANSIENT_PREFIX = 'edminboost_anm_debug_notices_';

	/**
	 * Keyword used by the auto-created test rule and sample messages.
	 *
	 * @var string
	 */
	const TEST_KEYWORD = 'ANM_TEST';

	/**
	 * Inbox notice types available for debug seed / live trigger.
	 *
	 * @return string[]
	 */
	public static function get_notice_type_slugs() {
		return array( 'critical', 'warning', 'success', 'info', 'upsell' );
	}

	/**
	 * Whether debug tooling is available in this environment.
	 *
	 * @return bool
	 */
	public static function is_available() {
		return defined( 'WP_DEBUG' ) && WP_DEBUG;
	}

	/**
	 * Register runtime hooks when debug mode is on.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		if ( ! self::is_available() ) {
			return;
		}

		add_action( 'admin_notices', array( __CLASS__, 'render_live_test_notices' ) );
	}

	/**
	 * Sanitize notice type slugs from a request or stored value.
	 *
	 * @param mixed $types Raw type list.
	 * @return string[]
	 */
	public static function sanitize_notice_types( $types ) {
		if ( is_string( $types ) ) {
			$types = '' === trim( $types ) ? array() : preg_split( '/\s*,\s*/', $types );
		} elseif ( ! is_array( $types ) ) {
			$types = array();
		}

		$flat = array();
		foreach ( $types as $type ) {
			if ( is_array( $type ) ) {
				foreach ( $type as $inner ) {
					if ( is_string( $inner ) && '' !== $inner ) {
						$flat[] = $inner;
					}
				}
			} elseif ( is_string( $type ) && '' !== $type ) {
				$flat[] = $type;
			}
		}

		$allowed = self::get_notice_type_slugs();
		$flat    = array_map( 'sanitize_key', $flat );

		return array_values( array_intersect( $flat, $allowed ) );
	}

	/**
	 * Keep sample notices whose inbox type is in the given list.
	 *
	 * @param array    $notices Sample notices from get_test_notices().
	 * @param string[] $types   Allowed type slugs.
	 * @return array
	 */
	public static function filter_test_notices_by_types( array $notices, array $types ) {
		$types = self::sanitize_notice_types( $types );

		if ( empty( $types ) ) {
			return array();
		}

		return array_values(
			array_filter(
				$notices,
				function ( $sample ) use ( $types ) {
					return in_array( $sample['type'] ?? '', $types, true );
				}
			)
		);
	}

	/**
	 * Sample notices covering each inbox type.
	 *
	 * @param string $suffix Optional unique suffix appended to each message.
	 * @return array
	 */
	public static function get_test_notices( $suffix = '' ) {
		$suffix = '' !== $suffix ? ' ' . $suffix : '';

		return array(
			array(
				'message'       => self::TEST_KEYWORD . ': Critical configuration issue requires attention.' . $suffix,
				'source_plugin' => __( 'ANM Test Notices', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'type'          => 'critical',
				'class'         => 'notice notice-error',
			),
			array(
				'message'       => self::TEST_KEYWORD . ': Warning — your license will expire soon.' . $suffix,
				'source_plugin' => __( 'ANM Test Notices', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'type'          => 'warning',
				'class'         => 'notice notice-warning',
			),
			array(
				'message'       => self::TEST_KEYWORD . ': Settings saved successfully.' . $suffix,
				'source_plugin' => __( 'ANM Test Notices', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'type'          => 'success',
				'class'         => 'notice notice-success is-dismissible',
			),
			array(
				'message'       => self::TEST_KEYWORD . ': General info — a new feature is available.' . $suffix,
				'source_plugin' => __( 'ANM Test Notices', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'type'          => 'info',
				'class'         => 'notice notice-info is-dismissible',
			),
			array(
				'message'       => self::TEST_KEYWORD . ': Upgrade to Pro now — limited time offer!' . $suffix,
				'source_plugin' => __( 'ANM Test Notices', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'type'          => 'upsell',
				'class'         => 'notice notice-info is-dismissible',
			),
			array(
				'message'       => self::TEST_KEYWORD . ': Please leave a 5 stars review for our plugin.' . $suffix,
				'source_plugin' => __( 'ANM Test Notices', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'type'          => 'upsell',
				'class'         => 'update-nag',
			),
		);
	}

	/**
	 * Ensure a keyword rule exists so live test notices are intercepted.
	 *
	 * @return array|WP_Error
	 */
	public static function ensure_test_rule() {
		foreach ( EDMINBOOST_ANM_Rules::get_all() as $rule ) {
			if ( self::TEST_KEYWORD === ( $rule['match_string'] ?? '' ) ) {
				if ( empty( $rule['enabled'] ) ) {
					return EDMINBOOST_ANM_Rules::toggle( $rule['id'], true );
				}

				return $rule;
			}
		}

		return EDMINBOOST_ANM_Rules::save(
			array(
				'name'         => __( 'Debug: ANM Test Notices', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'match_type'   => 'keyword',
				'match_string' => self::TEST_KEYWORD,
				'action'       => 'auto_dismiss_inbox',
				'enabled'      => true,
			)
		);
	}

	/**
	 * Seed the inbox with sample notices and record stats.
	 *
	 * @param string[] $types Inbox type slugs to include (critical, warning, success, etc.).
	 * @return array{added:int,skipped:int}
	 */
	public static function seed_inbox( $types = array() ) {
		$suffix = sprintf(
			/* translators: %s: short random token */
			__( '(run %s)', EDMINBOOST_ANM_TEXT_DOMAIN ),
			wp_generate_password( 4, false )
		);

		$added   = 0;
		$skipped = 0;

		$samples = self::filter_test_notices_by_types( self::get_test_notices( $suffix ), $types );

		foreach ( $samples as $sample ) {
			$notice = EDMINBOOST_ANM_Inbox::add(
				array(
					'message'       => $sample['message'],
					'source_plugin' => $sample['source_plugin'],
					'type'          => $sample['type'],
				)
			);

			if ( $notice ) {
				++$added;
				EDMINBOOST_ANM_Stats::record_intercept( $sample['source_plugin'], $sample['message'], $sample['type'] );
			} else {
				++$skipped;
			}
		}

		return array(
			'added'   => $added,
			'skipped' => $skipped,
		);
	}

	/**
	 * Queue live admin notices for the current user on the next admin screen load.
	 *
	 * @param string[] $types Inbox type slugs to output on the next load.
	 * @return string Redirect URL.
	 */
	public static function queue_live_notices( $types = array() ) {
		$user_id = get_current_user_id();
		$types   = self::sanitize_notice_types( $types );
		set_transient( self::TRANSIENT_PREFIX . $user_id, $types, MINUTE_IN_SECONDS );

		return admin_url( 'index.php' );
	}

	/**
	 * Output live test notices when queued (non–plugin admin screens only).
	 *
	 * @return void
	 */
	public static function render_live_test_notices() {
		if ( ! self::is_available() || ! current_user_can( EDMINBOOST_ANM_Settings::CAPABILITY ) ) {
			return;
		}

		if ( EDMINBOOST_ANM_Admin::is_plugin_screen() ) {
			return;
		}

		$user_id = get_current_user_id();
		$key     = self::TRANSIENT_PREFIX . $user_id;

		$queued_types = get_transient( $key );

		if ( false === $queued_types ) {
			return;
		}

		delete_transient( $key );

		if ( is_array( $queued_types ) ) {
			$types = self::sanitize_notice_types( $queued_types );
		} else {
			// Legacy transient stored as 1 — show all sample types.
			$types = self::get_notice_type_slugs();
		}

		if ( empty( $types ) ) {
			return;
		}

		$suffix = sprintf(
			/* translators: %s: short random token */
			__( '(live %s)', EDMINBOOST_ANM_TEXT_DOMAIN ),
			wp_generate_password( 4, false )
		);

		$samples = self::filter_test_notices_by_types( self::get_test_notices( $suffix ), $types );

		foreach ( $samples as $sample ) {
			printf(
				'<div class="%1$s"><p><strong>%2$s</strong> %3$s</p></div>',
				esc_attr( $sample['class'] ),
				esc_html__( 'Test Notice:', EDMINBOOST_ANM_TEXT_DOMAIN ),
				esc_html( $sample['message'] )
			);
		}
	}
}
