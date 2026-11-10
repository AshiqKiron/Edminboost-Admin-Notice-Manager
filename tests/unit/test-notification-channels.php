<?php
/**
 * Notification channel preferences (Free webhook behavior).
 *
 * @package EdminBoost\AdminNoticeManager
 */

/**
 * Test channel sanitization and Free webhook gating.
 */
class Test_EDMINBOOST_ANM_Notification_Channels extends EDMINBOOST_ANM_Test_Case {

	/**
	 * Free sanitize keeps webhook enabled when checkbox is not posted.
	 */
	public function test_free_sanitize_preserves_webhook_when_omitted() {
		if ( function_exists( 'edminboost_anm_is_pro' ) && edminboost_anm_is_pro() ) {
			$this->markTestSkipped( 'Free-only behavior.' );
		}

		$stored = array(
			'sources' => array(
				'wordpress_core' => array(
					'admin'   => true,
					'email'   => true,
					'webhook' => true,
				),
			),
		);

		$posted = array(
			'sources' => array(
				'wordpress_core' => array(
					'admin' => '1',
					'email' => '1',
				),
			),
		);

		$out = EDMINBOOST_ANM_Notification_Sources::sanitize_preferences( $posted, $stored );

		$this->assertTrue( ! empty( $out['sources']['wordpress_core']['webhook'] ) );
	}

	/**
	 * Free runtime treats per-source webhook channel as allowed.
	 */
	public function test_free_webhook_channel_enabled_without_matrix() {
		if ( function_exists( 'edminboost_anm_is_pro' ) && edminboost_anm_is_pro() ) {
			$this->markTestSkipped( 'Free-only behavior.' );
		}

		$settings = EDMINBOOST_ANM_Settings::get_stored_raw();
		$settings['notification_channels']['sources']['wordpress_core'] = array(
			'admin'   => true,
			'email'   => true,
			'webhook' => false,
		);
		EDMINBOOST_ANM_Settings::update_stored( $settings );
		EDMINBOOST_ANM_Settings::clear_merged_settings_cache();

		$this->assertTrue(
			EDMINBOOST_ANM_Notification_Sources::is_channel_enabled_for_source( 'wordpress_core', 'webhook' )
		);
	}
}
