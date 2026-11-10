<?php
/**
 * Inbox snooze normalization tests.
 *
 * @package EdminBoost.AdminNoticeManager
 */

/**
 * Expired snooze row cleanup.
 */
class Test_EDMINBOOST_ANM_Inbox_Snooze extends EDMINBOOST_ANM_Test_Case {

	/**
	 * Expired snooze rows are restored when the inbox loads.
	 */
	public function test_expired_snooze_normalized_on_get_all() {
		delete_option( EDMINBOOST_ANM_Settings::INBOX_OPTION );
		EDMINBOOST_ANM_Inbox::clear_list_caches();

		$notice = array(
			'id'              => 'notice_expired_snooze_test',
			'hash'            => md5( 'expired-snooze-test' ),
			'message'         => 'Expired snooze normalization',
			'source_plugin'   => 'PHPUnit',
			'type'            => 'info',
			'status'          => 'snoozed',
			'snoozed_until'   => time() - HOUR_IN_SECONDS,
			'snooze_duration' => '1d',
			'intercepted_at'  => time() - DAY_IN_SECONDS,
		);

		update_option( EDMINBOOST_ANM_Settings::INBOX_OPTION, array( $notice ), false );

		$loaded = EDMINBOOST_ANM_Inbox::get( $notice['id'] );

		$this->assertIsArray( $loaded );
		$this->assertSame( 'new', $loaded['status'] );
		$this->assertSame( 0, (int) ( $loaded['snoozed_until'] ?? 1 ) );
		$this->assertSame( '', $loaded['snooze_duration'] ?? 'x' );
	}
}
