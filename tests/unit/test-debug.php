<?php
/**
 * Debug helper tests.
 *
 * @package EdminBoost.AdminNoticeManager
 */

/**
 * Test EDMINBOOST_ANM_Debug sanitization.
 */
class Test_EDMINBOOST_ANM_Debug extends EDMINBOOST_ANM_Test_Case {

	/**
	 * sanitize_notice_types strips invalid slugs.
	 */
	public function test_sanitize_notice_types() {
		$types = EDMINBOOST_ANM_Debug::sanitize_notice_types( array( 'critical', 'invalid', 'warning', '' ) );
		$this->assertSame( array( 'critical', 'warning' ), $types );
	}

	/**
	 * sanitize_notice_types accepts comma-separated string.
	 */
	public function test_sanitize_notice_types_string() {
		$types = EDMINBOOST_ANM_Debug::sanitize_notice_types( 'success, upsell, bogus' );
		$this->assertSame( array( 'success', 'upsell' ), $types );
	}

	/**
	 * filter_test_notices_by_types respects allow list.
	 */
	public function test_filter_test_notices_by_types() {
		$samples = EDMINBOOST_ANM_Debug::get_test_notices( 'x' );
		$filtered = EDMINBOOST_ANM_Debug::filter_test_notices_by_types( $samples, array( 'upsell' ) );
		$this->assertNotEmpty( $filtered );
		foreach ( $filtered as $row ) {
			$this->assertSame( 'upsell', $row['type'] );
		}
	}
}
