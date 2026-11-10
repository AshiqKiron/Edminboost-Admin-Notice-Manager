<?php
/**
 * Base test case with fresh plugin options per test.
 *
 * @package EdminBoost\AdminNoticeManager
 */

/**
 * ANM PHPUnit base case.
 */
abstract class EDMINBOOST_ANM_Test_Case extends WP_UnitTestCase {

	/**
	 * Reset options and enable the plugin for each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		delete_option( EDMINBOOST_ANM_Settings::OPTION_NAME );
		delete_option( EDMINBOOST_ANM_Settings::RULES_OPTION );
		delete_option( EDMINBOOST_ANM_Settings::INBOX_OPTION );
		delete_option( EDMINBOOST_ANM_Settings::STATS_OPTION );
		delete_option( EDMINBOOST_ANM_Settings::VERSION_OPTION );

		EDMINBOOST_ANM_Activator::activate_site();

		$settings            = EDMINBOOST_ANM_Settings::get_stored_raw();
		$settings['enabled'] = true;
		EDMINBOOST_ANM_Settings::update_stored( $settings );
		EDMINBOOST_ANM_Settings::clear_merged_settings_cache();
	}

	/**
	 * Create an administrator and set as current user.
	 *
	 * @return int User ID.
	 */
	protected function login_as_admin() {
		$user_id = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $user_id );
		return $user_id;
	}

	/**
	 * Create a subscriber (no manage_options).
	 *
	 * @return int User ID.
	 */
	protected function login_as_subscriber() {
		$user_id = $this->factory->user->create(
			array(
				'role' => 'subscriber',
			)
		);
		wp_set_current_user( $user_id );
		return $user_id;
	}
}
