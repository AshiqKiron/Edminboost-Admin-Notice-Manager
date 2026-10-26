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

		$url = $settings['webhook']['url'];

		$payload = array(
			'text' => sprintf(
				/* translators: 1: source plugin, 2: notice message */
				__( '[Edminboost ANM] Intercepted notice from %1$s: %2$s', EDMINBOOST_ANM_TEXT_DOMAIN ),
				$notice['source_plugin'] ?? '',
				wp_html_excerpt( $notice['message'] ?? '', 200, '…' )
			),
			'source'  => $notice['source_plugin'] ?? '',
			'message' => $notice['message'] ?? '',
			'type'    => $notice['type'] ?? 'info',
			'site'    => home_url(),
		);

		wp_remote_post(
			$url,
			array(
				'timeout' => 5,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $payload ),
			)
		);
	}
}
