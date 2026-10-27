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
	 * Hash preference: show on the WordPress dashboard as usual.
	 *
	 * @var string
	 */
	const HASH_PREF_NORMAL = 'normal';

	/**
	 * Hash preference: permanently hide this notice.
	 *
	 * @var string
	 */
	const HASH_PREF_FOREVER = 'forever';

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

		$html = isset( $data['html'] ) ? wp_kses_post( $data['html'] ) : '';

		$notice = array(
			'id'             => 'notice_' . strtolower( wp_generate_password( 8, false ) ),
			'source_plugin'  => $source_plugin,
			'message'        => $message,
			'html'           => $html,
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
	 * Remove the converted-rule link from an inbox notice.
	 *
	 * @param string $id Notice ID.
	 * @return bool
	 */
	public static function clear_converted_to_rule( $id ) {
		$notices = self::get_all();

		foreach ( $notices as $index => $notice ) {
			if ( self::id_matches( $notice['id'] ?? '', $id ) ) {
				unset( $notices[ $index ]['converted_rule_id'] );
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

	/**
	 * Stored hash display preferences.
	 *
	 * @return array<string, string>
	 */
	public static function get_hash_preferences() {
		$prefs = get_option( EDMINBOOST_ANM_Settings::HASH_PREFS_OPTION, array() );

		if ( ! is_array( $prefs ) ) {
			return array();
		}

		return self::normalize_hash_preferences_storage( $prefs );
	}

	/**
	 * Normalize hash preference storage (per-user buckets with legacy flat support).
	 *
	 * @param array $prefs Raw option value.
	 * @return array<int, array<string, string>>
	 */
	private static function normalize_hash_preferences_storage( $prefs ) {
		if ( isset( $prefs['users'] ) && is_array( $prefs['users'] ) ) {
			$normalized = array();

			foreach ( $prefs['users'] as $user_id => $user_prefs ) {
				$user_id = absint( $user_id );
				if ( $user_id < 1 || ! is_array( $user_prefs ) ) {
					continue;
				}
				$normalized[ $user_id ] = self::sanitize_hash_pref_map( $user_prefs );
			}

			return $normalized;
		}

		$legacy = self::sanitize_hash_pref_map( $prefs );
		if ( empty( $legacy ) ) {
			return array();
		}

		return array( 0 => $legacy );
	}

	/**
	 * Sanitize a hash => mode map.
	 *
	 * @param array $map Raw map.
	 * @return array<string, string>
	 */
	private static function sanitize_hash_pref_map( $map ) {
		if ( ! is_array( $map ) ) {
			return array();
		}

		$output = array();

		foreach ( $map as $hash => $mode ) {
			$hash = sanitize_key( $hash );
			$mode = sanitize_key( $mode );
			if ( '' === $hash || ! in_array( $mode, array( self::HASH_PREF_NORMAL, self::HASH_PREF_FOREVER ), true ) ) {
				continue;
			}
			$output[ $hash ] = $mode;
		}

		return $output;
	}

	/**
	 * Persist normalized hash preferences.
	 *
	 * @param array $prefs Normalized per-user map.
	 * @return bool
	 */
	private static function save_hash_preferences( $prefs ) {
		$users = array();

		foreach ( $prefs as $user_id => $user_prefs ) {
			$user_id = absint( $user_id );
			if ( $user_id < 0 || ! is_array( $user_prefs ) || empty( $user_prefs ) ) {
				continue;
			}
			$users[ (string) $user_id ] = self::sanitize_hash_pref_map( $user_prefs );
		}

		if ( empty( $users ) ) {
			return delete_option( EDMINBOOST_ANM_Settings::HASH_PREFS_OPTION );
		}

		return update_option(
			EDMINBOOST_ANM_Settings::HASH_PREFS_OPTION,
			array( 'users' => $users ),
			false
		);
	}

	/**
	 * Get display preference for a notice hash.
	 *
	 * @param string $hash Notice hash.
	 * @return string Empty string when unset.
	 */
	public static function get_hash_preference( $hash, $user_id = null ) {
		$hash = sanitize_key( $hash );
		if ( '' === $hash ) {
			return '';
		}

		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		$user_id = absint( $user_id );
		$prefs   = self::get_hash_preferences();

		if ( $user_id > 0 && isset( $prefs[ $user_id ][ $hash ] ) ) {
			return $prefs[ $user_id ][ $hash ];
		}

		if ( isset( $prefs[0][ $hash ] ) ) {
			return $prefs[0][ $hash ];
		}

		return '';
	}

	/**
	 * Persist a hash display preference.
	 *
	 * @param string $hash Notice hash.
	 * @param string $mode Preference mode or empty to clear.
	 * @return bool
	 */
	public static function set_hash_preference( $hash, $mode, $user_id = null ) {
		$hash = sanitize_key( $hash );
		if ( '' === $hash ) {
			return false;
		}

		if ( null === $user_id ) {
			$user_id = get_current_user_id();
			if ( $user_id < 1 ) {
				return false;
			}
		} else {
			$user_id = absint( $user_id );
		}

		$prefs = self::get_hash_preferences();

		if ( ! isset( $prefs[ $user_id ] ) || ! is_array( $prefs[ $user_id ] ) ) {
			$prefs[ $user_id ] = array();
		}

		if ( '' === $mode || ! in_array( $mode, array( self::HASH_PREF_NORMAL, self::HASH_PREF_FOREVER ), true ) ) {
			unset( $prefs[ $user_id ][ $hash ] );
			if ( empty( $prefs[ $user_id ] ) ) {
				unset( $prefs[ $user_id ] );
			}
		} else {
			$prefs[ $user_id ][ $hash ] = $mode;
		}

		return self::save_hash_preferences( $prefs );
	}

	/**
	 * Clear stored hash display preferences.
	 *
	 * @param string $scope current_user|all_users.
	 * @return bool
	 */
	public static function reset_hash_preferences( $scope ) {
		$scope = sanitize_key( $scope );

		if ( 'all_users' === $scope ) {
			return delete_option( EDMINBOOST_ANM_Settings::HASH_PREFS_OPTION );
		}

		$user_id = get_current_user_id();
		if ( $user_id < 1 ) {
			return false;
		}

		$prefs = self::get_hash_preferences();
		unset( $prefs[ $user_id ] );

		return self::save_hash_preferences( $prefs );
	}

	/**
	 * Format the "first logged" timestamp for panel display.
	 *
	 * @param int $timestamp Unix timestamp.
	 * @return string
	 */
	public static function format_first_logged( $timestamp ) {
		$timestamp = (int) $timestamp;
		if ( $timestamp <= 0 ) {
			return '';
		}

		return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
	}

	/**
	 * WordPress notice class string for a panel preview.
	 *
	 * @param string $type Inbox type slug.
	 * @return string
	 */
	public static function get_panel_notice_class( $type ) {
		$map = array(
			'success'  => 'notice notice-success',
			'critical' => 'notice notice-error',
			'warning'  => 'notice notice-warning',
			'upsell'   => 'notice notice-info',
			'info'     => 'notice notice-info',
			'error'    => 'notice notice-error',
		);

		$type = sanitize_key( $type );

		return $map[ $type ] ?? 'notice notice-info';
	}

	/**
	 * Resolve a stable hash for notice HTML (prefer matching inbox entry).
	 *
	 * @param string $html Notice HTML.
	 * @return string
	 */
	public static function resolve_hash_for_html( $html ) {
		$message    = wp_strip_all_tags( $html );
		$normalized = strtolower( trim( $message ) );

		if ( '' === $normalized ) {
			return '';
		}

		foreach ( self::get_all() as $notice ) {
			$existing = strtolower( trim( wp_strip_all_tags( $notice['message'] ?? '' ) ) );
			if ( $existing === $normalized && ! empty( $notice['hash'] ) ) {
				return sanitize_key( $notice['hash'] );
			}
		}

		$source = EDMINBOOST_ANM_Interceptor::guess_source_for_notice_html( $html, $message );

		return self::generate_hash( $message, $source );
	}

	/**
	 * Apply hide-for-me or hide-for-all for a notice hash.
	 *
	 * @param string $hash  Notice hash.
	 * @param string $scope hide_for_me|hide_for_all.
	 * @param string $id    Optional inbox notice ID.
	 * @return true|WP_Error
	 */
	public static function apply_hide_scope( $hash, $scope, $id = '' ) {
		$hash  = sanitize_key( $hash );
		$scope = sanitize_key( $scope );

		if ( '' === $hash ) {
			return new WP_Error( 'invalid_hash', __( 'Could not identify this notice.', EDMINBOOST_ANM_TEXT_DOMAIN ) );
		}

		if ( 'hide_for_me' === $scope ) {
			self::set_hash_preference( $hash, self::HASH_PREF_FOREVER );
			if ( '' !== $id ) {
				self::archive( $id );
			}
			return true;
		}

		if ( 'hide_for_all' === $scope ) {
			self::set_hash_preference( $hash, self::HASH_PREF_FOREVER, 0 );
			if ( '' !== $id ) {
				$notice = self::get( $id );
				if ( $notice && ( empty( $notice['converted_rule_id'] ) || ! EDMINBOOST_ANM_Rules::get( $notice['converted_rule_id'] ?? '' ) ) ) {
					EDMINBOOST_ANM_Rules::create_from_inbox( $id );
				}
				self::archive( $id );
			}
			return true;
		}

		return new WP_Error( 'invalid_mode', __( 'Invalid action.', EDMINBOOST_ANM_TEXT_DOMAIN ) );
	}

	/**
	 * Apply inline hide from live dashboard notice HTML.
	 *
	 * @param string $html  Notice HTML.
	 * @param string $scope hide_for_me|hide_for_all.
	 * @return true|WP_Error
	 */
	public static function apply_inline_hide( $html, $scope ) {
		$html = wp_kses_post( $html );
		if ( '' === trim( wp_strip_all_tags( $html ) ) ) {
			return new WP_Error( 'empty_notice', __( 'Notice not found.', EDMINBOOST_ANM_TEXT_DOMAIN ) );
		}

		if ( EDMINBOOST_ANM_Interceptor::is_protected_notice( $html, wp_strip_all_tags( $html ) ) ) {
			return new WP_Error( 'protected', __( 'This notice cannot be hidden.', EDMINBOOST_ANM_TEXT_DOMAIN ) );
		}

		$hash = self::resolve_hash_for_html( $html );
		$id   = '';

		foreach ( self::get_all() as $notice ) {
			if ( sanitize_key( $notice['hash'] ?? '' ) === $hash ) {
				$id = $notice['id'] ?? '';
				break;
			}
		}

		if ( 'hide_for_all' === $scope && '' === $id ) {
			$message = wp_strip_all_tags( $html );
			$source  = EDMINBOOST_ANM_Interceptor::guess_source_for_notice_html( $html, $message );
			$type    = EDMINBOOST_ANM_Rules::classify_type( $message );
			$added   = self::add(
				array(
					'message'       => $message,
					'html'          => $html,
					'source_plugin' => $source,
					'type'          => $type,
				)
			);
			if ( $added && ! empty( $added['id'] ) ) {
				$id = $added['id'];
			}
		}

		return self::apply_hide_scope( $hash, $scope, $id );
	}

	/**
	 * Apply a panel display choice for an inbox notice.
	 *
	 * @param string $id   Notice ID.
	 * @param string $mode display_normal|hide_for_me|hide_for_all|hide_forever|panel_dismiss.
	 * @return true|WP_Error
	 */
	public static function apply_panel_display_choice( $id, $mode ) {
		$notice = self::get( $id );
		if ( ! $notice ) {
			return new WP_Error( 'not_found', __( 'Notice not found.', EDMINBOOST_ANM_TEXT_DOMAIN ) );
		}

		$hash = sanitize_key( $notice['hash'] ?? '' );
		$mode = sanitize_key( $mode );

		switch ( $mode ) {
			case 'display_normal':
				if ( '' !== $hash ) {
					self::set_hash_preference( $hash, self::HASH_PREF_NORMAL );
				}
				self::archive( $id );
				return true;

			case 'hide_for_me':
				return self::apply_hide_scope( $hash, 'hide_for_me', $id );

			case 'hide_for_all':
			case 'hide_forever':
				return self::apply_hide_scope( $hash, 'hide_for_all', $id );

			case 'panel_dismiss':
				self::archive( $id );
				return true;

			default:
				return new WP_Error( 'invalid_mode', __( 'Invalid action.', EDMINBOOST_ANM_TEXT_DOMAIN ) );
		}
	}
}
