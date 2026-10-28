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
	 * Cron recurrence slug (registered via cron_schedules — not in WordPress core).
	 *
	 * @var string
	 */
	const CRON_SCHEDULE = 'weekly';

	/**
	 * Local hour (0–23) when the weekly digest cron should run.
	 *
	 * @var int
	 */
	const SEND_HOUR = 9;

	/**
	 * Default weekday slug when none is saved.
	 *
	 * @var string
	 */
	const DEFAULT_SEND_WEEKDAY = 'monday';

	/**
	 * Placeholder token for the site name in customizable subject/intro/footer.
	 *
	 * @var string
	 */
	const SITE_NAME_TOKEN = '{site_name}';

	/**
	 * Register cron hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_filter( 'cron_schedules', array( __CLASS__, 'add_cron_schedules' ) );
		add_action( self::CRON_HOOK, array( $this, 'send_digest' ) );
		add_action( 'init', array( __CLASS__, 'ensure_schedule_if_needed' ), 20 );
	}

	/**
	 * Schedule digest cron when enabled but missing (e.g. after fixing weekly interval registration).
	 *
	 * @return void
	 */
	public static function ensure_schedule_if_needed() {
		$settings = EDMINBOOST_ANM_Settings::get();

		if ( empty( $settings['digest']['enabled'] ) ) {
			return;
		}

		$next = wp_next_scheduled( self::CRON_HOOK );
		if ( ! $next || ! self::scheduled_timestamp_matches_settings( $next, $settings ) ) {
			self::sync_schedule( $settings );
		}
	}

	/**
	 * Weekday slug => label for digest send day (site timezone).
	 *
	 * @return array<string, string>
	 */
	public static function get_send_weekday_choices() {
		return array(
			'sunday'    => __( 'Sunday', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'monday'    => __( 'Monday', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'tuesday'   => __( 'Tuesday', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'wednesday' => __( 'Wednesday', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'thursday'  => __( 'Thursday', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'friday'    => __( 'Friday', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'saturday'  => __( 'Saturday', EDMINBOOST_ANM_TEXT_DOMAIN ),
		);
	}

	/**
	 * Sanitize stored digest send weekday slug.
	 *
	 * @param string $value Raw weekday slug.
	 * @return string
	 */
	public static function sanitize_send_weekday( $value ) {
		$value   = sanitize_key( (string) $value );
		$choices = self::get_send_weekday_choices();

		if ( isset( $choices[ $value ] ) ) {
			return $value;
		}

		return self::DEFAULT_SEND_WEEKDAY;
	}

	/**
	 * Map weekday slug to PHP date( 'w' ) value (0 = Sunday … 6 = Saturday).
	 *
	 * @param string $weekday_slug Weekday slug.
	 * @return int
	 */
	public static function weekday_slug_to_php_w( $weekday_slug ) {
		$map = array(
			'sunday'    => 0,
			'monday'    => 1,
			'tuesday'   => 2,
			'wednesday' => 3,
			'thursday'  => 4,
			'friday'    => 5,
			'saturday'  => 6,
		);

		$weekday_slug = self::sanitize_send_weekday( $weekday_slug );

		return $map[ $weekday_slug ];
	}

	/**
	 * Next Unix timestamp for the configured weekday at SEND_HOUR in the site timezone.
	 *
	 * @param string $weekday_slug Weekday slug.
	 * @return int
	 */
	public static function get_next_send_timestamp( $weekday_slug ) {
		$target_w = self::weekday_slug_to_php_w( $weekday_slug );
		$tz       = wp_timezone();
		$now      = new DateTimeImmutable( 'now', $tz );
		$send     = $now->setTime( self::SEND_HOUR, 0, 0 );

		$current_w  = (int) $send->format( 'w' );
		$days_ahead = ( $target_w - $current_w + 7 ) % 7;

		if ( 0 === $days_ahead && $send <= $now ) {
			$days_ahead = 7;
		}

		if ( $days_ahead > 0 ) {
			$send = $send->modify( '+' . $days_ahead . ' days' );
		}

		return $send->getTimestamp();
	}

	/**
	 * Whether a scheduled cron timestamp matches the digest weekday setting.
	 *
	 * @param int   $timestamp Scheduled event time.
	 * @param array $settings  Full plugin settings.
	 * @return bool
	 */
	public static function scheduled_timestamp_matches_settings( $timestamp, $settings ) {
		$digest = isset( $settings['digest'] ) && is_array( $settings['digest'] )
			? $settings['digest']
			: array();

		$expected_w = self::weekday_slug_to_php_w( $digest['send_weekday'] ?? self::DEFAULT_SEND_WEEKDAY );
		$actual_w   = (int) wp_date( 'w', (int) $timestamp );

		return $expected_w === $actual_w;
	}

	/**
	 * Human-readable next send datetime for settings preview (site timezone).
	 *
	 * @param array|null $settings Optional full settings array.
	 * @return string Empty when digest is disabled.
	 */
	public static function get_next_send_label( $settings = null ) {
		if ( null === $settings ) {
			$settings = EDMINBOOST_ANM_Settings::get();
		}

		$digest = isset( $settings['digest'] ) && is_array( $settings['digest'] )
			? $settings['digest']
			: array();

		if ( empty( $digest['enabled'] ) ) {
			return '';
		}

		$weekday_slug = self::sanitize_send_weekday( $digest['send_weekday'] ?? self::DEFAULT_SEND_WEEKDAY );
		$timestamp    = self::get_next_send_timestamp( $weekday_slug );
		$date_format  = get_option( 'date_format' );
		$time_format  = get_option( 'time_format' );

		return wp_date(
			trim( $date_format . ' ' . $time_format ),
			$timestamp
		);
	}

	/**
	 * Register weekly interval for digest cron (WordPress core has no "weekly" schedule).
	 *
	 * @param array<string, array{interval:int,display:string}> $schedules Existing schedules.
	 * @return array<string, array{interval:int,display:string}>
	 */
	public static function add_cron_schedules( $schedules ) {
		if ( ! is_array( $schedules ) ) {
			$schedules = array();
		}

		if ( ! isset( $schedules[ self::CRON_SCHEDULE ] ) ) {
			$schedules[ self::CRON_SCHEDULE ] = array(
				'interval' => WEEK_IN_SECONDS,
				'display'  => __( 'Once Weekly', EDMINBOOST_ANM_TEXT_DOMAIN ),
			);
		}

		return $schedules;
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

		if ( empty( $settings['digest']['enabled'] ) ) {
			self::clear_schedule();
			return;
		}

		$digest = isset( $settings['digest'] ) && is_array( $settings['digest'] )
			? $settings['digest']
			: array();

		$weekday_slug = self::sanitize_send_weekday( $digest['send_weekday'] ?? self::DEFAULT_SEND_WEEKDAY );
		$next         = wp_next_scheduled( self::CRON_HOOK );

		if ( ! $next || ! self::scheduled_timestamp_matches_settings( $next, $settings ) ) {
			self::clear_schedule();
			wp_schedule_event(
				self::get_next_send_timestamp( $weekday_slug ),
				self::CRON_SCHEDULE,
				self::CRON_HOOK
			);
		}
	}

	/**
	 * Clear scheduled digest.
	 *
	 * @return void
	 */
	public static function clear_schedule() {
		while ( $timestamp = wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	/**
	 * Default email subject when no custom subject is saved.
	 *
	 * @param string $site_name Site name.
	 * @return string
	 */
	public static function get_default_subject( $site_name ) {
		return sprintf(
			/* translators: %s: site name */
			__( '[%s] Weekly Notice Diet Digest', EDMINBOOST_ANM_TEXT_DOMAIN ),
			$site_name
		);
	}

	/**
	 * Default opening line when no custom intro is saved.
	 *
	 * @param string $site_name Site name.
	 * @return string
	 */
	public static function get_default_intro( $site_name ) {
		return sprintf(
			/* translators: %s: site name */
			__( 'Weekly summary of silenced admin notices for %s.', EDMINBOOST_ANM_TEXT_DOMAIN ),
			$site_name
		);
	}

	/**
	 * Replace site name token in customizable digest text.
	 *
	 * @param string $text      Subject, intro, or footer text.
	 * @param string $site_name Decoded site name.
	 * @return string
	 */
	public static function replace_site_name_token( $text, $site_name ) {
		return str_replace( self::SITE_NAME_TOKEN, $site_name, $text );
	}

	/**
	 * Structured digest sections (shared by plain-text email and admin preview).
	 *
	 * @param array|null $settings Optional full settings array.
	 * @return array{
	 *     intro:string,
	 *     stats:?array{monthly_blocked:int,active_rules:int},
	 *     recent:?array{items:array<int,array{source:string,message:string,type:string}>,empty:bool},
	 *     footer:?string
	 * }
	 */
	public static function build_email_content( $settings = null ) {
		if ( null === $settings ) {
			$settings = EDMINBOOST_ANM_Settings::get();
		}

		$digest    = isset( $settings['digest'] ) && is_array( $settings['digest'] )
			? $settings['digest']
			: array();
		$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

		$custom_intro = isset( $digest['intro'] ) ? trim( (string) $digest['intro'] ) : '';
		if ( '' === $custom_intro ) {
			$intro = self::get_default_intro( $site_name );
		} else {
			$intro = self::replace_site_name_token( $custom_intro, $site_name );
		}

		$include_stats = ! isset( $digest['include_stats'] ) || ! empty( $digest['include_stats'] );
		$stats         = null;

		if ( $include_stats ) {
			$metrics = EDMINBOOST_ANM_Stats::get_dashboard_metrics();
			$stats   = array(
				'monthly_blocked' => (int) ( $metrics['monthly_blocked'] ?? 0 ),
				'active_rules'    => (int) ( $metrics['active_rules'] ?? 0 ),
			);
		}

		$include_recent = ! isset( $digest['include_recent_notices'] ) || ! empty( $digest['include_recent_notices'] );
		$recent         = null;

		if ( $include_recent ) {
			$limit = isset( $digest['recent_notices_count'] ) ? absint( $digest['recent_notices_count'] ) : 10;
			$limit = max( 1, min( 25, $limit ) );

			$notices = EDMINBOOST_ANM_Inbox::query( array( 'per_page' => $limit ) );
			$items   = array();

			foreach ( $notices as $notice ) {
				$items[] = array(
					'source'  => (string) ( $notice['source_plugin'] ?? '' ),
					'message' => wp_html_excerpt( $notice['message'] ?? '', 120, '…' ),
					'type'    => sanitize_key( $notice['type'] ?? 'info' ),
				);
			}

			$recent = array(
				'items' => $items,
				'empty' => empty( $items ),
			);
		}

		$custom_footer = isset( $digest['footer'] ) ? trim( (string) $digest['footer'] ) : '';
		$footer        = '' !== $custom_footer
			? self::replace_site_name_token( $custom_footer, $site_name )
			: null;

		return array(
			'intro'  => $intro,
			'stats'  => $stats,
			'recent' => $recent,
			'footer' => $footer,
		);
	}

	/**
	 * Format structured digest content as plain text for wp_mail().
	 *
	 * @param array $content Output from build_email_content().
	 * @return string
	 */
	public static function format_email_body_plain( array $content ) {
		$lines = array( $content['intro'] );

		if ( ! empty( $content['stats'] ) ) {
			$lines[] = '';
			$lines[] = __( 'At a glance', EDMINBOOST_ANM_TEXT_DOMAIN );
			$lines[] = sprintf(
				/* translators: %d: number of blocked notices */
				__( 'Notices blocked this month: %d', EDMINBOOST_ANM_TEXT_DOMAIN ),
				$content['stats']['monthly_blocked']
			);
			$lines[] = sprintf(
				/* translators: %d: number of active rules */
				__( 'Active auto-rules: %d', EDMINBOOST_ANM_TEXT_DOMAIN ),
				$content['stats']['active_rules']
			);
		}

		if ( null !== $content['recent'] ) {
			$lines[] = '';
			$lines[] = __( 'Recent intercepted notices', EDMINBOOST_ANM_TEXT_DOMAIN );

			if ( ! empty( $content['recent']['empty'] ) ) {
				$lines[] = __( 'No intercepted notices in the inbox yet.', EDMINBOOST_ANM_TEXT_DOMAIN );
			} else {
				$index = 1;
				foreach ( $content['recent']['items'] as $item ) {
					$lines[] = '';
					$lines[] = sprintf(
						/* translators: 1: list number, 2: notice source */
						__( '%1$d. %2$s', EDMINBOOST_ANM_TEXT_DOMAIN ),
						$index,
						$item['source']
					);
					$lines[] = '   ' . $item['message'];
					++$index;
				}
			}
		}

		if ( ! empty( $content['footer'] ) ) {
			$lines[] = '';
			$lines[] = $content['footer'];
		}

		return implode( "\n", $lines );
	}

	/**
	 * Build digest email subject and plain-text body from plugin settings.
	 *
	 * @param array|null $settings Optional full settings array.
	 * @return array{subject:string,body:string}
	 */
	public static function build_email( $settings = null ) {
		if ( null === $settings ) {
			$settings = EDMINBOOST_ANM_Settings::get();
		}

		$digest    = isset( $settings['digest'] ) && is_array( $settings['digest'] )
			? $settings['digest']
			: array();
		$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

		$custom_subject = isset( $digest['subject'] ) ? trim( (string) $digest['subject'] ) : '';
		if ( '' === $custom_subject ) {
			$subject = self::get_default_subject( $site_name );
		} else {
			$subject = self::replace_site_name_token( $custom_subject, $site_name );
		}

		$content = self::build_email_content( $settings );
		$body    = self::format_email_body_plain( $content );

		/**
		 * Filter digest email subject and body before send or preview.
		 *
		 * @param array  $email    Keys `subject` and `body`.
		 * @param array  $settings Full plugin settings used to build the email.
		 */
		$email = apply_filters(
			'edminboost_anm_digest_email',
			array(
				'subject' => $subject,
				'body'    => $body,
			),
			$settings
		);

		return array(
			'subject' => isset( $email['subject'] ) ? (string) $email['subject'] : $subject,
			'body'    => isset( $email['body'] ) ? (string) $email['body'] : $body,
		);
	}

	/**
	 * Resolve digest recipient email addresses (same logic as send).
	 *
	 * @param array|null $settings Optional full settings array.
	 * @return string[]
	 */
	public static function get_recipients( $settings = null ) {
		if ( null === $settings ) {
			$settings = EDMINBOOST_ANM_Settings::get();
		}

		$digest = isset( $settings['digest'] ) && is_array( $settings['digest'] )
			? $settings['digest']
			: array();

		$recipients = array_map( 'trim', explode( ',', $digest['recipients'] ?? '' ) );
		$recipients = array_filter( $recipients, 'is_email' );

		if ( empty( $recipients ) ) {
			$recipients = array( get_option( 'admin_email' ) );
		}

		return array_values( $recipients );
	}

	/**
	 * From address used by wp_mail() (includes core filters).
	 *
	 * @return array{email:string,name:string,formatted:string}
	 */
	public static function get_from_address() {
		$email = apply_filters( 'wp_mail_from', get_option( 'admin_email' ) );
		$name  = apply_filters(
			'wp_mail_from_name',
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
		);

		$email = sanitize_email( $email );
		$name  = trim( wp_strip_all_tags( (string) $name ) );

		if ( '' === $email ) {
			$email = sanitize_email( get_option( 'admin_email' ) );
		}

		$formatted = '' === $name ? $email : sprintf( '%s <%s>', $name, $email );

		return array(
			'email'     => $email,
			'name'      => $name,
			'formatted' => $formatted,
		);
	}

	/**
	 * Full plain-text digest preview (matches weekly wp_mail() content).
	 *
	 * @param array|null $settings Optional full settings array.
	 * @return array{to:string,from:string,subject:string,body:string,content:array,next_send:string}
	 */
	public static function get_preview( $settings = null ) {
		if ( null === $settings ) {
			$settings = EDMINBOOST_ANM_Settings::get();
		}

		$email   = self::build_email( $settings );
		$from    = self::get_from_address();
		$content = self::build_email_content( $settings );

		return array(
			'to'        => implode( ', ', self::get_recipients( $settings ) ),
			'from'      => $from['formatted'],
			'subject'   => $email['subject'],
			'body'      => $email['body'],
			'content'   => $content,
			'next_send' => self::get_next_send_label( $settings ),
		);
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

		$email      = self::build_email( $settings );
		$recipients = self::get_recipients( $settings );

		wp_mail(
			implode( ',', $recipients ),
			$email['subject'],
			$email['body']
		);
	}
}
