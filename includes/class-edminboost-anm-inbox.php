<?php
/**
 * Notice inbox — storage, filtering, and bulk actions.
 *
 * @package EdminBoost\AdminNoticeManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Inbox manager.
 */
class EDMINBOOST_ANM_Inbox {

	/**
	 * Get all inbox notices.
	 *
	 * @return array
	 */
	public static function get_all() {
		$inbox = get_option( EDMINBOOST_ANM_Settings::INBOX_OPTION, array() );

		return is_array( $inbox ) ? $inbox : array();
	}

	/**
	 * Save inbox notices.
	 *
	 * @param array $notices Notices list.
	 * @return bool
	 */
	public static function save_all( $notices ) {
		return update_option( EDMINBOOST_ANM_Settings::INBOX_OPTION, $notices, false );
	}

	/**
	 * Get a notice by ID.
	 *
	 * @param string $id Notice ID.
	 * @return array|null
	 */
	public static function get( $id ) {
		foreach ( self::get_all() as $notice ) {
			if ( self::id_matches( $notice['id'] ?? '', $id ) ) {
				return $notice;
			}
		}

		return null;
	}

	/**
	 * Compare stored and requested notice IDs (case-insensitive).
	 *
	 * @param string $stored    ID from inbox storage.
	 * @param string $requested ID from request/UI.
	 * @return bool
	 */
	private static function id_matches( $stored, $requested ) {
		return sanitize_key( $stored ) === sanitize_key( $requested );
	}

	/**
	 * Generate hash for deduplication.
	 *
	 * @param string $message Notice message.
	 * @param string $source  Source plugin.
	 * @return string
	 */
	public static function generate_hash( $message, $source = '' ) {
		return md5( strtolower( wp_strip_all_tags( $message ) ) . '|' . sanitize_key( $source ) );
	}

	/**
	 * Add a notice to the inbox.
	 *
	 * @param array $data Notice data.
	 * @return array|null Added notice or null if duplicate.
	 */
	public static function add( $data ) {
		$message       = wp_strip_all_tags( $data['message'] ?? '' );
		$source_plugin = sanitize_text_field( $data['source_plugin'] ?? __( 'Unknown', EDMINBOOST_ANM_TEXT_DOMAIN ) );
		$type          = sanitize_key( $data['type'] ?? 'info' );
		$hash          = self::generate_hash( $message, $source_plugin );

		foreach ( self::get_all() as $existing ) {
			if ( ( $existing['hash'] ?? '' ) === $hash && 'archived' !== ( $existing['status'] ?? '' ) ) {
				return null;
			}
		}

		$notice = array(
			'id'             => 'notice_' . strtolower( wp_generate_password( 8, false ) ),
			'source_plugin'  => $source_plugin,
			'message'        => $message,
			'type'           => $type,
			'status'         => 'new',
			'snoozed_until'  => 0,
			'intercepted_at' => time(),
			'hash'           => $hash,
		);

		$notices   = self::get_all();
		$notices[] = $notice;
		self::save_all( $notices );

		return $notice;
	}

	/**
	 * Query inbox with filters.
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public static function query( $args = array() ) {
		$defaults = array(
			'filter'   => 'all',
			'search'   => '',
			'page'     => 1,
			'per_page' => 20,
			'orderby'  => 'date',
			'order'    => 'desc',
		);

		$args    = wp_parse_args( $args, $defaults );
		$notices = self::get_all();
		$now     = time();

		$notices = array_filter(
			$notices,
			static function ( $notice ) use ( $args, $now ) {
				if ( 'archived' === ( $notice['status'] ?? '' ) ) {
					return false;
				}

				if ( 'snoozed' === ( $notice['status'] ?? '' ) && (int) ( $notice['snoozed_until'] ?? 0 ) <= $now ) {
					return true;
				}

				if ( 'snoozed' === ( $notice['status'] ?? '' ) && (int) ( $notice['snoozed_until'] ?? 0 ) > $now ) {
					if ( 'snoozed' !== $args['filter'] && 'all' !== $args['filter'] ) {
						return false;
					}
				}

				switch ( $args['filter'] ) {
					case 'critical':
						if ( 'critical' !== ( $notice['type'] ?? '' ) && 'warning' !== ( $notice['type'] ?? '' ) ) {
							return false;
						}
						break;
					case 'snoozed':
						if ( 'snoozed' !== ( $notice['status'] ?? '' ) || (int) ( $notice['snoozed_until'] ?? 0 ) <= $now ) {
							return false;
						}
						break;
					case 'upsells':
						if ( 'upsell' !== ( $notice['type'] ?? '' ) ) {
							return false;
						}
						break;
				}

				if ( ! empty( $args['search'] ) ) {
					$search = strtolower( $args['search'] );
					$hay    = strtolower( ( $notice['message'] ?? '' ) . ' ' . ( $notice['source_plugin'] ?? '' ) );
					if ( false === strpos( $hay, $search ) ) {
						return false;
					}
				}

				return true;
			}
		);

		$orderby = sanitize_key( $args['orderby'] );
		$order   = 'asc' === strtolower( $args['order'] ) ? 'asc' : 'desc';

		if ( ! in_array( $orderby, array( 'source', 'message', 'type', 'status', 'date' ), true ) ) {
			$orderby = 'date';
		}

		usort(
			$notices,
			static function ( $a, $b ) use ( $orderby, $order ) {
				switch ( $orderby ) {
					case 'source':
						$cmp = strcasecmp( (string) ( $a['source_plugin'] ?? '' ), (string) ( $b['source_plugin'] ?? '' ) );
						break;
					case 'message':
						$cmp = strcasecmp( (string) ( $a['message'] ?? '' ), (string) ( $b['message'] ?? '' ) );
						break;
					case 'type':
						$cmp = strcasecmp( (string) ( $a['type'] ?? '' ), (string) ( $b['type'] ?? '' ) );
						break;
					case 'status':
						$cmp = strcasecmp( (string) ( $a['status'] ?? '' ), (string) ( $b['status'] ?? '' ) );
						break;
					case 'date':
					default:
						$cmp = (int) ( $a['intercepted_at'] ?? 0 ) <=> (int) ( $b['intercepted_at'] ?? 0 );
						break;
				}

				return 'asc' === $order ? $cmp : -$cmp;
			}
		);

		return array_values( $notices );
	}

	/**
	 * Get filter tab counts.
	 *
	 * @return array
	 */
	public static function get_counts() {
		$all      = self::query( array( 'filter' => 'all', 'per_page' => 9999 ) );
		$critical = self::query( array( 'filter' => 'critical', 'per_page' => 9999 ) );
		$snoozed  = self::query( array( 'filter' => 'snoozed', 'per_page' => 9999 ) );
		$upsells  = self::query( array( 'filter' => 'upsells', 'per_page' => 9999 ) );

		return array(
			'all'      => count( $all ),
			'critical' => count( $critical ),
			'snoozed'  => count( $snoozed ),
			'upsells'  => count( $upsells ),
		);
	}

	/**
	 * Link an inbox notice to a rule created from it.
	 *
	 * @param string $id      Notice ID.
	 * @param string $rule_id Rule ID.
	 * @return bool
	 */
	public static function mark_converted_to_rule( $id, $rule_id ) {
		$notices = self::get_all();
		$rule_id = sanitize_key( $rule_id );

		foreach ( $notices as $index => $notice ) {
			if ( self::id_matches( $notice['id'] ?? '', $id ) ) {
				$notices[ $index ]['converted_rule_id'] = $rule_id;
				self::save_all( $notices );
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether the notice has an active converted rule.
	 *
	 * @param array $notice Notice row.
	 * @return bool
	 */
	public static function is_converted_to_rule( $notice ) {
		$rule_id = sanitize_key( $notice['converted_rule_id'] ?? '' );

		if ( '' === $rule_id ) {
			return false;
		}

		return null !== EDMINBOOST_ANM_Rules::get( $rule_id );
	}

	/**
	 * Mark notice as read.
	 *
	 * @param string $id Notice ID.
	 * @return bool
	 */
	public static function mark_read( $id ) {
		return self::update_status( $id, 'read' );
	}

	/**
	 * Snooze a notice.
	 *
	 * @param string $id       Notice ID.
	 * @param int    $duration Duration in seconds.
	 * @return bool
	 */
	public static function snooze( $id, $duration = DAY_IN_SECONDS ) {
		$notices = self::get_all();

		foreach ( $notices as $index => $notice ) {
			if ( self::id_matches( $notice['id'] ?? '', $id ) ) {
				$notices[ $index ]['status']        = 'snoozed';
				$notices[ $index ]['snoozed_until'] = time() + (int) $duration;
				self::save_all( $notices );
				return true;
			}
		}

		return false;
	}

	/**
	 * Archive a notice.
	 *
	 * @param string $id Notice ID.
	 * @return bool
	 */
	public static function archive( $id ) {
		return self::update_status( $id, 'archived' );
	}

	/**
	 * Update notice status.
	 *
	 * @param string $id     Notice ID.
	 * @param string $status New status.
	 * @return bool
	 */
	private static function update_status( $id, $status ) {
		$notices = self::get_all();

		foreach ( $notices as $index => $notice ) {
			if ( self::id_matches( $notice['id'] ?? '', $id ) ) {
				$notices[ $index ]['status'] = sanitize_key( $status );
				self::save_all( $notices );
				return true;
			}
		}

		return false;
	}

	/**
	 * Bulk action on multiple notices.
	 *
	 * @param array  $ids    Notice IDs.
	 * @param string $action Action slug.
	 * @return int Number affected.
	 */
	public static function bulk_action( $ids, $action ) {
		$count = 0;

		foreach ( (array) $ids as $id ) {
			switch ( $action ) {
				case 'read':
					if ( self::mark_read( $id ) ) {
						++$count;
					}
					break;
				case 'snooze':
					if ( self::snooze( $id ) ) {
						++$count;
					}
					break;
				case 'archive':
					if ( self::archive( $id ) ) {
						++$count;
					}
					break;
			}
		}

		return $count;
	}

	/**
	 * Human-readable type label.
	 *
	 * @param string $type Type slug.
	 * @return string
	 */
	public static function get_type_label( $type ) {
		$labels = array(
			'critical' => __( 'Warning (Critical)', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'warning'  => __( 'Warning (Critical)', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'upsell'   => __( 'Promotion (Upsell)', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'success'  => __( 'Success', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'info'     => __( 'Info', EDMINBOOST_ANM_TEXT_DOMAIN ),
		);

		return $labels[ $type ] ?? ucfirst( $type );
	}
}
