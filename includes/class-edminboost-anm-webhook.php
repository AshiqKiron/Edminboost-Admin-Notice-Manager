<?php
/**
 * Webhook alerts for intercepted notices (opt-in).
 *
 * @package EdminBoost\AdminNoticeManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Webhook sender.
 */
class EDMINBOOST_ANM_Webhook {

	/**
	 * Placeholder tokens for customizable alert text.
	 *
	 * @var string
	 */
	const TOKEN_SOURCE          = '{source}';
	const TOKEN_MESSAGE         = '{message}';
	const TOKEN_MESSAGE_EXCERPT = '{message_excerpt}';
	const TOKEN_TYPE            = '{type}';
	const TOKEN_SITE            = '{site}';
	const TOKEN_SITE_NAME       = '{site_name}';

	/**
	 * Send webhook alert if enabled.
	 *
	 * @param array $notice Inbox notice data.
	 * @return void
	 */
	public static function maybe_send( $notice ) {
		$settings = EDMINBOOST_ANM_Settings::get();

		if ( empty( $settings['webhook']['enabled'] ) || empty( $settings['webhook']['url'] ) ) {
			return;
		}

		$url     = $settings['webhook']['url'];
		$payload = self::build_payload( $notice, $settings );

		wp_remote_post(
			$url,
			array(
				'timeout' => 5,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $payload ),
			)
		);
	}

	/**
	 * Default alert text template when no custom message is saved.
	 *
	 * @return string
	 */
	public static function get_default_message_template() {
		return __( '[Edminboost ANM] Intercepted notice from {source}: {message_excerpt}', EDMINBOOST_ANM_TEXT_DOMAIN );
	}

	/**
	 * Build JSON payload for the webhook request.
	 *
	 * @param array      $notice   Inbox notice data.
	 * @param array|null $settings Optional full settings array.
	 * @return array
	 */
	public static function build_payload( $notice, $settings = null ) {
		if ( null === $settings ) {
			$settings = EDMINBOOST_ANM_Settings::get();
		}

		$message = (string) ( $notice['message'] ?? '' );
		$source  = (string) ( $notice['source_plugin'] ?? '' );
		$type    = (string) ( $notice['type'] ?? 'info' );

		$payload = array(
			'text'    => self::build_message_text( $notice, $settings ),
			'source'  => $source,
			'message' => $message,
			'type'    => $type,
			'site'    => home_url(),
		);

		/**
		 * Filter webhook JSON payload before send.
		 *
		 * @param array $payload  Payload sent to the webhook URL.
		 * @param array $notice   Intercepted notice data.
		 * @param array $settings Full plugin settings.
		 */
		return apply_filters( 'edminboost_anm_webhook_payload', $payload, $notice, $settings );
	}

	/**
	 * Build the human-readable `text` field from settings and notice data.
	 *
	 * @param array      $notice   Inbox notice data.
	 * @param array|null $settings Optional full settings array.
	 * @return string
	 */
	public static function build_message_text( $notice, $settings = null ) {
		if ( null === $settings ) {
			$settings = EDMINBOOST_ANM_Settings::get();
		}

		$webhook = isset( $settings['webhook'] ) && is_array( $settings['webhook'] )
			? $settings['webhook']
			: array();

		$template = isset( $webhook['message'] ) ? trim( (string) $webhook['message'] ) : '';
		if ( '' === $template ) {
			$template = self::get_default_message_template();
		}

		return self::replace_message_tokens( $template, $notice );
	}

	/**
	 * Replace placeholder tokens in a webhook message template.
	 *
	 * @param string $template Message template.
	 * @param array  $notice   Inbox notice data.
	 * @return string
	 */
	public static function replace_message_tokens( $template, $notice ) {
		$tokens = self::get_message_tokens( $notice );

		return str_replace( array_keys( $tokens ), array_values( $tokens ), $template );
	}

	/**
	 * Token map for webhook message templates.
	 *
	 * @param array $notice Inbox notice data.
	 * @return array<string, string>
	 */
	public static function get_message_tokens( $notice ) {
		$message   = (string) ( $notice['message'] ?? '' );
		$source    = (string) ( $notice['source_plugin'] ?? '' );
		$type      = (string) ( $notice['type'] ?? 'info' );
		$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

		return array(
			self::TOKEN_SOURCE          => $source,
			self::TOKEN_MESSAGE         => $message,
			self::TOKEN_MESSAGE_EXCERPT => wp_html_excerpt( $message, 200, '…' ),
			self::TOKEN_TYPE            => $type,
			self::TOKEN_SITE            => home_url(),
			self::TOKEN_SITE_NAME       => $site_name,
		);
	}

	/**
	 * Sample notice used when the inbox is empty.
	 *
	 * @return array
	 */
	public static function get_sample_notice() {
		return array(
			'source_plugin' => __( 'Example Plugin', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'message'       => __( 'Your license will expire in 3 days. Renew now to keep receiving updates and support.', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'type'          => 'warning',
		);
	}

	/**
	 * Notice data for webhook preview (latest inbox item or sample).
	 *
	 * @return array{ notice: array, from_inbox: bool }
	 */
	public static function get_preview_notice() {
		$notices = EDMINBOOST_ANM_Inbox::get_all();

		for ( $i = count( $notices ) - 1; $i >= 0; $i-- ) {
			if ( ! isset( $notices[ $i ] ) || ! is_array( $notices[ $i ] ) ) {
				continue;
			}
			if ( 'archived' === ( $notices[ $i ]['status'] ?? '' ) ) {
				continue;
			}

			return array(
				'notice'     => $notices[ $i ],
				'from_inbox' => true,
			);
		}

		return array(
			'notice'     => self::get_sample_notice(),
			'from_inbox' => false,
		);
	}

	/**
	 * Build admin preview data for unsaved or saved webhook settings.
	 *
	 * @param array|null $settings Optional full settings array.
	 * @return array
	 */
	public static function get_preview( $settings = null ) {
		if ( null === $settings ) {
			$settings = EDMINBOOST_ANM_Settings::get();
		}

		$preview_notice = self::get_preview_notice();
		$notice         = $preview_notice['notice'];
		$payload        = self::build_payload( $notice, $settings );

		return array(
			'text'            => $payload['text'],
			'payload'         => $payload,
			'sample_from_inbox' => $preview_notice['from_inbox'],
			'sample'          => array(
				'source'  => (string) ( $notice['source_plugin'] ?? '' ),
				'type'    => (string) ( $notice['type'] ?? 'info' ),
				'message' => wp_html_excerpt( (string) ( $notice['message'] ?? '' ), 200, '…' ),
			),
		);
	}
}
