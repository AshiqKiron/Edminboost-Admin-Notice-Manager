<?php
/**
 * Rules engine matching tests.
 *
 * @package EdminBoost.AdminNoticeManager
 */

/**
 * Test EDMINBOOST_ANM_Rules matching helpers.
 */
class Test_EDMINBOOST_ANM_Rules_Matching extends EDMINBOOST_ANM_Test_Case {

	/**
	 * Class selector match.
	 */
	public function test_html_matches_css_selector_class() {
		$html = '<div class="notice notice-error is-dismissible"><p>Error</p></div>';
		$this->assertTrue( EDMINBOOST_ANM_Rules::html_matches_css_selector( $html, '.notice-error' ) );
		$this->assertFalse( EDMINBOOST_ANM_Rules::html_matches_css_selector( $html, '.notice-success' ) );
	}

	/**
	 * ID selector match.
	 */
	public function test_html_matches_css_selector_id() {
		$html = '<div id="e2e-notice" class="notice"><p>Hi</p></div>';
		$this->assertTrue( EDMINBOOST_ANM_Rules::html_matches_css_selector( $html, '#e2e-notice' ) );
	}

	/**
	 * Tag.class selector match.
	 */
	public function test_html_matches_css_selector_tag_class() {
		$html = '<div class="update-nag">Please upgrade now</div>';
		$this->assertTrue( EDMINBOOST_ANM_Rules::html_matches_css_selector( $html, 'div.update-nag' ) );
	}

	/**
	 * Upsell classification uses blacklist phrases.
	 */
	public function test_classify_type_detects_upsell() {
		$this->assertSame(
			'upsell',
			EDMINBOOST_ANM_Rules::classify_type( 'Please leave a 5 stars review for our plugin.' )
		);
	}

	/**
	 * Critical keywords map to critical type.
	 */
	public function test_classify_type_detects_critical() {
		$this->assertSame(
			'critical',
			EDMINBOOST_ANM_Rules::classify_type( 'A fatal database connection error occurred.' )
		);
	}

	/**
	 * Keyword rule save and retrieval.
	 */
	public function test_save_keyword_rule() {
		$rule = EDMINBOOST_ANM_Rules::save(
			array(
				'name'         => 'E2E keyword',
				'match_type'   => 'keyword',
				'match_string' => 'E2E_UNIQUE_MARKER',
				'action'       => 'auto_dismiss_inbox',
				'enabled'      => true,
			)
		);

		$this->assertIsArray( $rule );
		$this->assertNotEmpty( $rule['id'] );
		$this->assertSame( 'keyword', $rule['match_type'] );
	}
}
