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
	 * @return array{to:string,from:string,subject:string,body:string,content:array}
	 */
	public static function get_preview( $settings = null ) {
		if ( null === $settings ) {
			$settings = EDMINBOOST_ANM_Settings::get();
		}

		$email   = self::build_email( $settings );
		$from    = self::get_from_address();
		$content = self::build_email_content( $settings );

		return array(
			'to'      => implode( ', ', self::get_recipients( $settings ) ),
			'from'    => $from['formatted'],
			'subject' => $email['subject'],
			'body'    => $email['body'],
			'content' => $content,
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
