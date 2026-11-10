<?php
/**
 * Settings sanitization tests.
 *
 * @package EdminBoost\AdminNoticeManager
 */

/**
 * Test EDMINBOOST_ANM_Settings sanitizers.
 */
class Test_EDMINBOOST_ANM_Settings_Sanitize extends EDMINBOOST_ANM_Test_Case {

	/**
	 * Webhook URL must be http/https and pass wp_http_validate_url.
	 */
	public function test_sanitize_webhook_url_rejects_javascript() {
		$this->assertSame( '', EDMINBOOST_ANM_Settings::sanitize_webhook_url( 'javascript:alert(1)' ) );
	}

	/**
	 * Valid HTTPS webhook URL is preserved.
	 */
	public function test_sanitize_webhook_url_accepts_https() {
		$url = 'https://example.com/webhook';
		$this->assertSame( $url, EDMINBOOST_ANM_Settings::sanitize_webhook_url( $url ) );
	}

	/**
	 * Webhook settings merge keeps stored URL when disabled.
	 */
	public function test_sanitize_webhook_settings_merges_stored() {
		$stored = array(
			'enabled' => true,
			'url'     => 'https://example.com/hook',
			'message' => 'Hello {site_name}',
		);
		$out    = EDMINBOOST_ANM_Settings::sanitize_webhook_settings(
			array(
				'enabled' => false,
				'message' => 'Updated',
			),
			$stored
		);

		$this->assertFalse( $out['enabled'] );
		$this->assertSame( 'Updated', $out['message'] );
		$this->assertSame( 'https://example.com/hook', $out['url'] );
	}

	/**
	 * Digest recipients sanitize splits and validates emails.
	 */
	public function test_sanitize_digest_recipients() {
		$raw = ' admin@example.com , not-an-email , editor@example.org ';
		$out = EDMINBOOST_ANM_Settings::sanitize_digest_recipients( $raw );
		$this->assertStringContainsString( 'admin@example.com', $out );
		$this->assertStringContainsString( 'editor@example.org', $out );
		$this->assertStringNotContainsString( 'not-an-email', $out );
	}

	/**
	 * enabled is not changed when omitted from sanitize input.
	 */
	public function test_sanitize_preserves_enabled_when_omitted() {
		$stored = EDMINBOOST_ANM_Settings::get_stored_raw();
		$stored['enabled'] = true;
		EDMINBOOST_ANM_Settings::update_stored( $stored );

		$sanitized = EDMINBOOST_ANM_Settings::sanitize(
			array(
				'digest' => array(
					'intro' => 'Test intro',
				),
			),
			$stored
		);

		$this->assertTrue( ! empty( $sanitized['enabled'] ) );
	}
}
