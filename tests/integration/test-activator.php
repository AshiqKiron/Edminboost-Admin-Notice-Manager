<?php
/**
 * Activation seeding tests.
 *
 * @package EdminBoost.AdminNoticeManager
 */

/**
 * Test plugin activation.
 */
class Test_EDMINBOOST_ANM_Activator extends EDMINBOOST_ANM_Test_Case {

	/**
	 * activate_site seeds all expected options.
	 */
	public function test_activate_site_seeds_options() {
		$this->assertNotFalse( get_option( EDMINBOOST_ANM_Settings::OPTION_NAME, false ) );
		$this->assertIsArray( get_option( EDMINBOOST_ANM_Settings::RULES_OPTION, false ) );
		$this->assertIsArray( get_option( EDMINBOOST_ANM_Settings::INBOX_OPTION, false ) );
		$this->assertIsArray( get_option( EDMINBOOST_ANM_Settings::STATS_OPTION, false ) );
		$this->assertSame( EDMINBOOST_ANM_VERSION, get_option( EDMINBOOST_ANM_Settings::VERSION_OPTION ) );
	}
}
