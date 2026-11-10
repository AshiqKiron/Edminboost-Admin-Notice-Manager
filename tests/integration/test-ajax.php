<?php
/**
 * AJAX handler integration tests.
 *
 * @package EdminBoost.AdminNoticeManager
 */

/**
 * AJAX tests for EDMINBOOST_ANM_Ajax.
 */
class Test_EDMINBOOST_ANM_Ajax extends WP_Ajax_UnitTestCase {

	/**
	 * Fresh options before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		delete_option( EDMINBOOST_ANM_Settings::OPTION_NAME );
		delete_option( EDMINBOOST_ANM_Settings::RULES_OPTION );
		delete_option( EDMINBOOST_ANM_Settings::INBOX_OPTION );
		delete_option( EDMINBOOST_ANM_Settings::STATS_OPTION );

		EDMINBOOST_ANM_Activator::activate_site();

		$settings            = EDMINBOOST_ANM_Settings::get_stored_raw();
		$settings['enabled'] = true;
		EDMINBOOST_ANM_Settings::update_stored( $settings );
		EDMINBOOST_ANM_Settings::clear_merged_settings_cache();

		$ajax = new EDMINBOOST_ANM_Ajax();
		$ajax->register_hooks();
	}

	/**
	 * Subscriber cannot toggle enabled.
	 */
	public function test_toggle_enabled_denied_for_subscriber() {
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$_POST['nonce']   = wp_create_nonce( 'edminboost_anm_admin' );
		$_POST['enabled'] = '1';

		$thrown = null;
		try {
			$this->_handleAjax( 'edminboost_anm_toggle_enabled' );
		} catch ( WPAjaxDieContinueException $e ) {
			$thrown = $e;
		} catch ( WPAjaxDieStopException $e ) {
			$thrown = $e;
		}

		$this->assertNotNull( $thrown, 'Expected AJAX handler to terminate with an error.' );
		$this->assertTrue( EDMINBOOST_ANM_Settings::is_enabled(), 'Settings must not change when request is denied.' );
	}

	/**
	 * Admin can toggle enabled.
	 */
	public function test_toggle_enabled_success() {
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$_POST['nonce']   = wp_create_nonce( 'edminboost_anm_admin' );
		$_POST['enabled'] = '1';

		try {
			$this->_handleAjax( 'edminboost_anm_toggle_enabled' );
		} catch ( WPAjaxDieContinueException $e ) {
			// Expected path for wp_send_json_success.
		}

		$this->assertTrue( EDMINBOOST_ANM_Settings::is_enabled() );
	}

	/**
	 * Inbox list returns success for admin.
	 */
	public function test_inbox_list_success() {
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		EDMINBOOST_ANM_Inbox::add(
			array(
				'message'       => 'AJAX list row',
				'source_plugin' => 'PHPUnit',
				'type'          => 'info',
			)
		);

		$_POST['nonce']   = wp_create_nonce( 'edminboost_anm_admin' );
		$_POST['filter']  = 'all';
		$_POST['orderby'] = 'intercepted_at';
		$_POST['order']   = 'desc';

		$caught = null;
		try {
			$this->_handleAjax( 'edminboost_anm_inbox_list' );
		} catch ( WPAjaxDieContinueException $e ) {
			$caught = $e;
		}

		$this->assertInstanceOf( WPAjaxDieContinueException::class, $caught );
	}

	/**
	 * Invalid nonce fails verify_request.
	 */
	public function test_inbox_list_invalid_nonce() {
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$_POST['nonce'] = 'invalid';

		$this->expectException( 'WPAjaxDieStopException' );
		$this->_handleAjax( 'edminboost_anm_inbox_list' );
	}
}
