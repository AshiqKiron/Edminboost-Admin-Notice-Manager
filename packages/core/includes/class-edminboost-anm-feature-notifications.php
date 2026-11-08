<?php
/**
 * Bridge for WordPress core Feature Notifications (feature plugin / future core).
 *
 * @package EdminBoost\AdminNoticeManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integrates with core Feature Notifications when available.
 */
class EDMINBOOST_ANM_Feature_Notifications {

	/**
	 * Coexistence mode slugs (stored in settings).
	 *
	 * @var string[]
	 */
	const COEXIST_MODES = array( 'coexist', 'anm_primary', 'legacy_only' );

	/**
	 * Register integration hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		if ( ! EDMINBOOST_ANM_Settings::is_enabled() ) {
			return;
		}

		/**
		 * Fires when a core feature notification is stored (feature plugin; hook name may change).
		 *
		 * @param array $notification Raw notification payload from core.
		 */
		add_action( 'feature_notifications_notification_created', array( $this, 'ingest_notification' ), 10, 1 );
		add_action( 'wp_feature_notifications_created', array( $this, 'ingest_notification' ), 10, 1 );

		/**
		 * Filter outbound severity before core displays a notification (when supported).
		 *
		 * @param array $notification Notification payload.
		 */
		add_filter( 'feature_notifications_notification_payload', array( $this, 'filter_core_payload' ), 10, 1 );
		add_filter( 'wp_feature_notifications_payload', array( $this, 'filter_core_payload' ), 10, 1 );
	}

	/**
	 * Whether core Feature Notifications appears to be active.
	 *
	 * @return bool
	 */
	public static function is_available() {
		/**
		 * Filter whether WordPress Feature Notifications is active on this site.
		 *
		 * @param bool $active Default detection from known class/constants.
		 */
		return (bool) apply_filters(
			'edminboost_anm_core_feature_notifications_active',
			class_exists( 'WP_Feature_Notifications', false )
				|| defined( 'WP_FEATURE_NOTIFICATIONS_VERSION' )
				|| function_exists( 'wp_feature_notifications' )
		);
	}

	/**
	 * Import a core notification into the ANM inbox (capable admins only).
	 *
	 * @param array|object $notification Core notification data.
	 * @return void
	 */
	public function ingest_notification( $notification ) {
		if ( function_exists( 'edminboost_anm_is_pro' ) && ! edminboost_anm_is_pro() ) {
			return;
		}

		if ( ! current_user_can( EDMINBOOST_ANM_Settings::CAPABILITY ) ) {
			return;
		}

		if ( 'legacy_only' === EDMINBOOST_ANM_Settings::get_feature_notifications_coexist_mode() ) {
			return;
		}

		$data = is_object( $notification ) ? (array) $notification : $notification;
		if ( ! is_array( $data ) ) {
			return;
		}

		$message = wp_strip_all_tags( (string) ( $data['message'] ?? $data['title'] ?? $data['content'] ?? '' ) );
		if ( '' === trim( $message ) ) {
			return;
		}

		$source = sanitize_text_field( (string) ( $data['source'] ?? $data['plugin'] ?? __( 'WordPress', EDMINBOOST_ANM_TEXT_DOMAIN ) ) );
		$type   = EDMINBOOST_ANM_Inbox::map_core_severity_to_type( $data );

		$draft = array(
			'message'       => $message,
			'html'          => isset( $data['html'] ) ? wp_kses_post( (string) $data['html'] ) : '',
			'source_plugin' => $source,
			'type'          => $type,
			'origin'        => 'core_feature_notifications',
		);

		$notice = null;
		if ( ! class_exists( 'EDMINBOOST_ANM_Notification_Sources' )
			|| EDMINBOOST_ANM_Notification_Sources::notice_allows_channel( $draft, 'admin' ) ) {
			$notice = EDMINBOOST_ANM_Inbox::add( $draft );
		}

		if ( $notice ) {
			EDMINBOOST_ANM_Stats::record_intercept( $source, $message, $type );
			EDMINBOOST_ANM_Webhook::maybe_send( $notice );
		} elseif ( class_exists( 'EDMINBOOST_ANM_Notification_Sources' )
			&& EDMINBOOST_ANM_Notification_Sources::notice_allows_channel( $draft, 'webhook' ) ) {
			EDMINBOOST_ANM_Webhook::maybe_send( $draft );
		}
	}

	/**
	 * Apply ANM severity policy to core payloads when filter is supported.
	 *
	 * @param array $notification Notification payload.
	 * @return array
	 */
	public function filter_core_payload( $notification ) {
		if ( ! is_array( $notification ) ) {
			return $notification;
		}

		$message = wp_strip_all_tags( (string) ( $notification['message'] ?? $notification['title'] ?? '' ) );
		$type    = EDMINBOOST_ANM_Inbox::map_core_severity_to_type( $notification );
		$type    = EDMINBOOST_ANM_Inbox::apply_severity_policy( $type, $message, sanitize_text_field( (string) ( $notification['source'] ?? '' ) ) );

		if ( isset( $notification['severity'] ) ) {
			$notification['severity'] = EDMINBOOST_ANM_Inbox::get_severity_slug( $type );
		}

		return $notification;
	}
}
