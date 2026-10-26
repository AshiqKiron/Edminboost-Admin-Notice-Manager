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
	 * @return array{added:int,skipped:int}
	 */
	public static function seed_inbox() {
		$suffix = sprintf(
			/* translators: %s: short random token */
			__( '(run %s)', EDMINBOOST_ANM_TEXT_DOMAIN ),
			wp_generate_password( 4, false )
		);

		$added   = 0;
		$skipped = 0;

		foreach ( self::get_test_notices( $suffix ) as $sample ) {
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
	 * @return string Redirect URL.
	 */
	public static function queue_live_notices() {
		$user_id = get_current_user_id();
		set_transient( self::TRANSIENT_PREFIX . $user_id, 1, MINUTE_IN_SECONDS );

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

		if ( ! get_transient( $key ) ) {
			return;
		}

		delete_transient( $key );

		$suffix = sprintf(
			/* translators: %s: short random token */
			__( '(live %s)', EDMINBOOST_ANM_TEXT_DOMAIN ),
			wp_generate_password( 4, false )
		);

		foreach ( self::get_test_notices( $suffix ) as $sample ) {
			printf(
				'<div class="%1$s"><p><strong>%2$s</strong> %3$s</p></div>',
				esc_attr( $sample['class'] ),
				esc_html__( 'Test Notice:', EDMINBOOST_ANM_TEXT_DOMAIN ),
				esc_html( $sample['message'] )
			);
		}
	}
}
