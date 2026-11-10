<?php
/**
 * Legacy importer tests.
 *
 * @package EdminBoost.AdminNoticeManager
 */

/**
 * Test EDMINBOOST_ANM_Importer.
 */
class Test_EDMINBOOST_ANM_Importer extends EDMINBOOST_ANM_Test_Case {

	/**
	 * Empty legacy rule payload must not seed default upsell rules.
	 */
	public function test_import_legacy_empty_rules_returns_error() {
		$option_key = 'anm_settings';
		update_option(
			$option_key,
			array(
				'rules' => array(),
			),
			false
		);

		delete_option( EDMINBOOST_ANM_Settings::RULES_OPTION );

		$result = EDMINBOOST_ANM_Importer::import_legacy();

		$this->assertWPError( $result );
		$this->assertSame( 'no_rules_imported', $result->get_error_code() );
		$this->assertSame( 0, count( EDMINBOOST_ANM_Rules::get_all() ) );

		delete_option( $option_key );
	}
}
