<?php
/**
 * Weekly email digest for silenced notices.
 *
 * @package EdminBoost\AdminNoticeManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Digest scheduler and sender.
 */
class EDMINBOOST_ANM_Digest {

	/**
	 * Cron hook name.
	 *
	 * @var string
	 */
	const CRON_HOOK = 'edminboost_anm_weekly_digest';

	/**
	 * Register cron hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( self::CRON_HOOK, array( $this, 'send_digest' ) );
	}

	/**
	 * Sync cron schedule based on settings.
	 *
	 * @param array|null $settings Optional settings array.
	 * @return void
	 */
	public static function sync_schedule( $settings = null ) {
		if ( null === $settings ) {
			$settings = EDMINBOOST_ANM_Settings::get();
		}

		if ( ! empty( $settings['digest']['enabled'] ) ) {
			if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
				wp_schedule_event( time() + WEEK_IN_SECONDS, 'weekly', self::CRON_HOOK );
			}
		} else {
			self::clear_schedule();
		}
	}

	/**
	 * Clear scheduled digest.
	 *
	 * @return void
	 */
	public static function clear_schedule() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	/**
	 * Send weekly digest email.
	 *
	 * @return void
	 */
	public function send_digest() {
		$settings = EDMINBOOST_ANM_Settings::get();

		if ( empty( $settings['digest']['enabled'] ) ) {
			return;
		}

		$metrics = EDMINBOOST_ANM_Stats::get_dashboard_metrics();
		$notices = EDMINBOOST_ANM_Inbox::query( array( 'per_page' => 10 ) );

		$lines = array(
			sprintf(
				/* translators: %d: number of blocked notices */
				__( 'Total notices blocked this month: %d', EDMINBOOST_ANM_TEXT_DOMAIN ),
				$metrics['monthly_blocked']
			),
			sprintf(
				/* translators: %d: number of active rules */
				__( 'Active auto-rules: %d', EDMINBOOST_ANM_TEXT_DOMAIN ),
				$metrics['active_rules']
			),
			'',
			__( 'Recent intercepted notices:', EDMINBOOST_ANM_TEXT_DOMAIN ),
		);

		foreach ( $notices as $notice ) {
			$lines[] = sprintf(
				'- [%s] %s',
				$notice['source_plugin'] ?? '',
				wp_html_excerpt( $notice['message'] ?? '', 80, '…' )
			);
		}

		$recipients = array_map( 'trim', explode( ',', $settings['digest']['recipients'] ?? '' ) );
		$recipients = array_filter( $recipients, 'is_email' );

		if ( empty( $recipients ) ) {
			$recipients = array( get_option( 'admin_email' ) );
		}

		$subject = sprintf(
			/* translators: %s: site name */
			__( '[%s] Weekly Notice Diet Digest', EDMINBOOST_ANM_TEXT_DOMAIN ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
		);

		wp_mail(
			implode( ',', $recipients ),
			$subject,
			implode( "\n", $lines )
		);
	}
}
