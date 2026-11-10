<?php
/**
 * Inbox storage and query tests.
 *
 * @package EdminBoost.AdminNoticeManager
 */

/**
 * Test EDMINBOOST_ANM_Inbox.
 */
class Test_EDMINBOOST_ANM_Inbox extends EDMINBOOST_ANM_Test_Case {

	/**
	 * Adding a notice persists it with expected shape.
	 */
	public function test_add_notice() {
		$notice = EDMINBOOST_ANM_Inbox::add(
			array(
				'message'       => 'PHPUnit inbox message',
				'source_plugin' => 'PHPUnit',
				'type'          => 'warning',
			)
		);

		$this->assertIsArray( $notice );
		$this->assertSame( 'warning', $notice['type'] );
		$this->assertSame( 'new', $notice['status'] );
		$this->assertFalse( $notice['_is_repeat_capture'] );

		$loaded = EDMINBOOST_ANM_Inbox::get( $notice['id'] );
		$this->assertNotNull( $loaded );
		$this->assertSame( $notice['hash'], $loaded['hash'] );
	}

	/**
	 * Duplicate capture increments repeat_count.
	 */
	public function test_add_duplicate_increments_repeat_count() {
		$data = array(
			'message'       => 'Duplicate capture test',
			'source_plugin' => 'PHPUnit',
			'type'          => 'info',
		);

		$first  = EDMINBOOST_ANM_Inbox::add( $data );
		$second = EDMINBOOST_ANM_Inbox::add( $data );

		$this->assertTrue( $second['_is_repeat_capture'] );
		$this->assertSame( 2, (int) $second['repeat_count'] );
		$this->assertSame( $first['id'], $second['id'] );
	}

	/**
	 * Alerts filter excludes snoozed notices.
	 */
	public function test_query_alerts_filter() {
		$notice = EDMINBOOST_ANM_Inbox::add(
			array(
				'message'       => 'Critical alert for filter test',
				'source_plugin' => 'PHPUnit',
				'type'          => 'critical',
			)
		);

		EDMINBOOST_ANM_Inbox::snooze( $notice['id'], DAY_IN_SECONDS, '1d' );

		$alerts = EDMINBOOST_ANM_Inbox::query(
			array(
				'filter' => 'alerts',
			)
		);

		$ids = wp_list_pluck( $alerts, 'id' );
		$this->assertNotContains( $notice['id'], $ids );
	}

	/**
	 * Mark read sets read_at.
	 */
	public function test_mark_read() {
		$notice = EDMINBOOST_ANM_Inbox::add(
			array(
				'message'       => 'Read state test',
				'source_plugin' => 'PHPUnit',
				'type'          => 'info',
			)
		);

		$this->assertTrue( EDMINBOOST_ANM_Inbox::mark_read( $notice['id'] ) );
		$loaded = EDMINBOOST_ANM_Inbox::get( $notice['id'] );
		$this->assertNotEmpty( $loaded['read_at'] );
	}

	/**
	 * Unread badge count excludes read notices.
	 */
	public function test_unread_active_count() {
		$unread = EDMINBOOST_ANM_Inbox::add(
			array(
				'message'       => 'Unread badge test',
				'source_plugin' => 'PHPUnit',
				'type'          => 'info',
			)
		);
		$read = EDMINBOOST_ANM_Inbox::add(
			array(
				'message'       => 'Read badge test',
				'source_plugin' => 'PHPUnit',
				'type'          => 'info',
			)
		);
		EDMINBOOST_ANM_Inbox::mark_read( $read['id'] );

		$this->assertSame( 2, EDMINBOOST_ANM_Inbox::get_active_count() );
		$this->assertSame( 1, EDMINBOOST_ANM_Inbox::get_unread_active_count() );
		$this->assertSame( 1, EDMINBOOST_ANM_Inbox::get_counts()['unread'] );
		$this->assertFalse( EDMINBOOST_ANM_Inbox::is_marked_read( EDMINBOOST_ANM_Inbox::get( $unread['id'] ) ) );
	}
}
