<?php
/**
 * Auto-rules engine — CRUD, matching, and upsell preset phrases.
 *
 * @package EdminBoost\AdminNoticeManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rules manager.
 */
class EDMINBOOST_ANM_Rules {

	/**
	 * Built-in upsell preset phrases.
	 *
	 * @return array
	 */
	public static function get_builtin_preset_phrases() {
		$phrases = array(
			'leave a review',
			'rate us',
			'rate our plugin',
			'5 stars',
			'upgrade to pro',
			'upgrade now',
			'go premium',
			'black friday',
			'cyber monday',
			'special offer',
			'limited time',
			'get pro',
			'buy now',
			'discount',
			'promo',
			'promotional',
			'upsell',
			'try premium',
			'unlock pro',
			'subscribe',
			'newsletter',
			'sign up',
			'give feedback',
			'survey',
			'wp.org review',
			'wordpress.org review',
			'love our plugin',
			'support us',
			'donate',
			'become a patron',
			'affiliate',
			'partner program',
			'free trial',
			'act now',
		);

		/**
		 * Filter upsell preset phrases.
		 *
		 * @param array $phrases Preset phrase list.
		 */
		return apply_filters( 'edminboost_anm_upsell_preset_phrases', $phrases );
	}

	/**
	 * All active preset phrases (built-in plus custom settings).
	 *
	 * @return array
	 */
	public static function get_preset_phrases() {
		$phrases = self::get_builtin_preset_phrases();
		$custom  = EDMINBOOST_ANM_Settings::get()['upsell_preset']['custom_phrases'] ?? array();

		if ( ! is_array( $custom ) || empty( $custom ) ) {
			return $phrases;
		}

		$seen = array();

		foreach ( $phrases as $phrase ) {
			$seen[ strtolower( $phrase ) ] = true;
		}

		foreach ( $custom as $phrase ) {
			$phrase = trim( (string) $phrase );

			if ( '' === $phrase ) {
				continue;
			}

			$key = strtolower( $phrase );

			if ( isset( $seen[ $key ] ) ) {
				continue;
			}

			$seen[ $key ] = true;
			$phrases[]    = $phrase;
		}

		return $phrases;
	}

	/**
	 * Count of preset phrases.
	 *
	 * @return int
	 */
	public static function get_preset_phrase_count() {
		return count( self::get_preset_phrases() );
	}

	/**
	 * Get all rules.
	 *
	 * @return array
	 */
	public static function get_all() {
		$rules = get_option( EDMINBOOST_ANM_Settings::RULES_OPTION, array() );

		return is_array( $rules ) ? $rules : array();
	}

	/**
	 * Save all rules.
	 *
	 * @param array $rules Rules list.
	 * @return bool
	 */
	public static function save_all( $rules ) {
		return update_option( EDMINBOOST_ANM_Settings::RULES_OPTION, $rules, false );
	}

	/**
	 * Get a single rule by ID.
	 *
	 * @param string $id Rule ID.
	 * @return array|null
	 */
	public static function get( $id ) {
		foreach ( self::get_all() as $rule ) {
			if ( self::id_matches( $rule['id'] ?? '', $id ) ) {
				return $rule;
			}
		}

		return null;
	}

	/**
	 * Compare stored and requested rule IDs (case-insensitive).
	 *
	 * @param string $stored    ID from rules storage.
	 * @param string $requested ID from request/UI.
	 * @return bool
	 */
	private static function id_matches( $stored, $requested ) {
		return sanitize_key( (string) $stored ) === sanitize_key( (string) $requested );
	}

	/**
	 * Create or update a rule.
	 *
	 * @param array $data Rule data.
	 * @return array|WP_Error
	 */
	public static function save( $data ) {
		$name         = sanitize_text_field( $data['name'] ?? '' );
		$match_type   = sanitize_key( $data['match_type'] ?? 'keyword' );
		$match_string = sanitize_text_field( $data['match_string'] ?? '' );
		$action       = sanitize_key( $data['action'] ?? 'auto_dismiss_inbox' );
		$enabled      = ! empty( $data['enabled'] );

		if ( '' === $name || '' === $match_string ) {
			return new WP_Error( 'invalid_rule', __( 'Rule name and match string are required.', EDMINBOOST_ANM_TEXT_DOMAIN ) );
		}

		$allowed_types = array( 'keyword', 'regex', 'css' );
		if ( ! in_array( $match_type, $allowed_types, true ) ) {
			$match_type = 'keyword';
		}

		$allowed_actions = array( 'auto_dismiss_inbox', 'dismiss_only', 'route_inbox' );
		if ( ! in_array( $action, $allowed_actions, true ) ) {
			$action = 'auto_dismiss_inbox';
		}

		$rules = self::get_all();
		$id    = ! empty( $data['id'] ) ? sanitize_key( $data['id'] ) : sanitize_key( 'rule_' . wp_generate_password( 8, false ) );

		$existing_hits   = 0;
		$created         = time();
		$wp_notice_preset = '';

		foreach ( $rules as $rule ) {
			if ( self::id_matches( $rule['id'] ?? '', $id ) ) {
				$existing_hits    = (int) ( $rule['hit_count'] ?? 0 );
				$created          = (int) ( $rule['created'] ?? time() );
				$wp_notice_preset = sanitize_key( $rule['wp_notice_preset'] ?? '' );
				break;
			}
		}

		if ( ! empty( $data['wp_notice_preset'] ) ) {
			$wp_notice_preset = sanitize_key( $data['wp_notice_preset'] );
		}

		$new_rule = array(
			'id'           => $id,
			'name'         => $name,
			'match_type'   => $match_type,
			'match_string' => $match_string,
			'action'       => $action,
			'enabled'      => $enabled,
			'hit_count'    => $existing_hits,
			'created'      => $created,
		);

		if ( '' !== $wp_notice_preset ) {
			$new_rule['wp_notice_preset'] = $wp_notice_preset;
		}

		$found = false;
		foreach ( $rules as $index => $rule ) {
			if ( self::id_matches( $rule['id'] ?? '', $id ) ) {
				$rules[ $index ] = $new_rule;
				$found           = true;
				break;
			}
		}

		if ( ! $found ) {
			$rules[] = $new_rule;
		}

		self::save_all( $rules );

		return $new_rule;
	}

	/**
	 * Delete a rule.
	 *
	 * @param string $id Rule ID.
	 * @return bool
	 */
	public static function delete( $id ) {
		$rules = array_filter(
			self::get_all(),
			static function ( $rule ) use ( $id ) {
				return ! self::id_matches( $rule['id'] ?? '', $id );
			}
		);

		return self::save_all( array_values( $rules ) );
	}

	/**
	 * Toggle rule enabled state.
	 *
	 * @param string $id      Rule ID.
	 * @param bool   $enabled Enabled state.
	 * @return array|null
	 */
	public static function toggle( $id, $enabled ) {
		$rules = self::get_all();

		foreach ( $rules as $index => $rule ) {
			if ( self::id_matches( $rule['id'] ?? '', $id ) ) {
				$rules[ $index ]['enabled'] = (bool) $enabled;
				self::save_all( $rules );
				return $rules[ $index ];
			}
		}

		return null;
	}

	/**
	 * Increment hit count for a rule.
	 *
	 * @param string $id Rule ID.
	 * @return void
	 */
	public static function increment_hit( $id ) {
		$rules = self::get_all();

		foreach ( $rules as $index => $rule ) {
			if ( ( $rule['id'] ?? '' ) === $id ) {
				$rules[ $index ]['hit_count'] = (int) ( $rule['hit_count'] ?? 0 ) + 1;
				self::save_all( $rules );
				return;
			}
		}
	}

	/**
	 * Evaluate whether a notice should be intercepted.
	 *
	 * @param string $message Notice text.
	 * @param string $html    Notice HTML.
	 * @return array|null Matched rule info or preset match.
	 */
	public static function evaluate( $message, $html = '' ) {
		$settings = EDMINBOOST_ANM_Settings::get();
		$haystack = strtolower( wp_strip_all_tags( $message ) );

		if ( ! empty( $settings['upsell_preset']['enabled'] ) ) {
			foreach ( self::get_preset_phrases() as $phrase ) {
				if ( false !== strpos( $haystack, strtolower( $phrase ) ) ) {
					return array(
						'source' => 'preset',
						'id'     => 'upsell_preset',
						'name'   => __( 'Upsell Preset', EDMINBOOST_ANM_TEXT_DOMAIN ),
						'action' => 'auto_dismiss_inbox',
						'type'   => 'upsell',
					);
				}
			}
		}

		foreach ( self::get_all() as $rule ) {
			if ( empty( $rule['enabled'] ) ) {
				continue;
			}

			$matched = false;
			$string  = $rule['match_string'] ?? '';

			switch ( $rule['match_type'] ?? 'keyword' ) {
				case 'regex':
					$pattern = '@' . str_replace( '@', '\@', $string ) . '@i';
					if ( @preg_match( $pattern, $message ) ) {
						$matched = true;
					}
					break;
				case 'css':
					break;
				default:
					if ( false !== stripos( $haystack, strtolower( $string ) ) ) {
						$matched = true;
					}
					break;
			}

			if ( $matched ) {
				self::increment_hit( $rule['id'] );
				return array(
					'source' => 'rule',
					'id'     => $rule['id'],
					'name'   => $rule['name'],
					'action' => $rule['action'],
					'type'   => self::classify_type( $message ),
				);
			}
		}

		return null;
	}

	/**
	 * Classify notice type from message content.
	 *
	 * @param string $message Notice text.
	 * @return string
	 */
	public static function classify_type( $message ) {
		$lower = strtolower( wp_strip_all_tags( $message ) );

		if ( preg_match( '/\b(error|fatal|critical|database)\b/i', $lower ) ) {
			return 'critical';
		}

		foreach ( self::get_preset_phrases() as $phrase ) {
			if ( false !== strpos( $lower, strtolower( $phrase ) ) ) {
				return 'upsell';
			}
		}

		if ( preg_match( '/\b(success|updated|saved|completed)\b/i', $lower ) ) {
			return 'success';
		}

		if ( preg_match( '/\b(warning|notice|caution)\b/i', $lower ) ) {
			return 'warning';
		}

		if ( preg_match( '/\b(sale|discount|upgrade|review|promo)\b/i', $lower ) ) {
			return 'upsell';
		}

		return 'info';
	}

	/**
	 * Human-readable match type label.
	 *
	 * @param string $type Match type slug.
	 * @return string
	 */
	public static function get_match_type_label( $type ) {
		$labels = array(
			'keyword' => __( 'Keyword (String Match)', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'regex'   => __( 'Wildcard / Regex', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'css'     => __( 'CSS Selector', EDMINBOOST_ANM_TEXT_DOMAIN ),
		);

		return $labels[ $type ] ?? $labels['keyword'];
	}

	/**
	 * Built-in WordPress core notice presets for one-click rule creation.
	 *
	 * @return array
	 */
	public static function get_wp_notice_presets() {
		$presets = array(
			array(
				'id'           => 'wp_core_update',
				'name'         => __( 'WordPress update available', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'message'      => __( 'WordPress 6.7 is available! Please update now.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'match_string' => 'wordpress',
				'type'         => 'info',
				'source'       => __( 'WordPress Core', EDMINBOOST_ANM_TEXT_DOMAIN ),
			),
			array(
				'id'           => 'wp_php_outdated',
				'name'         => __( 'Outdated PHP version', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'message'      => __( 'Your site is running an outdated version of PHP.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'match_string' => 'outdated version of php',
				'type'         => 'warning',
				'source'       => __( 'WordPress Core', EDMINBOOST_ANM_TEXT_DOMAIN ),
			),
			array(
				'id'           => 'wp_site_health_critical',
				'name'         => __( 'Site Health critical issues', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'message'      => __( 'Your site has critical issues that should be addressed as soon as possible.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'match_string' => 'critical issues',
				'type'         => 'critical',
				'source'       => __( 'WordPress Core', EDMINBOOST_ANM_TEXT_DOMAIN ),
			),
			array(
				'id'           => 'wp_auto_update_failed',
				'name'         => __( 'Automatic update failed', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'message'      => __( 'An automated WordPress update has failed to complete.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'match_string' => 'automated wordpress update',
				'type'         => 'warning',
				'source'       => __( 'WordPress Core', EDMINBOOST_ANM_TEXT_DOMAIN ),
			),
			array(
				'id'           => 'wp_db_upgrade',
				'name'         => __( 'Database upgrade required', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'message'      => __( 'WordPress database upgrade required.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'match_string' => 'database upgrade required',
				'type'         => 'info',
				'source'       => __( 'WordPress Core', EDMINBOOST_ANM_TEXT_DOMAIN ),
			),
			array(
				'id'           => 'wp_cookies_blocked',
				'name'         => __( 'Cookies blocked notice', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'message'      => __( 'Cookies are blocked or not supported by your browser.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'match_string' => 'cookies are blocked',
				'type'         => 'warning',
				'source'       => __( 'WordPress Core', EDMINBOOST_ANM_TEXT_DOMAIN ),
			),
		);

		/**
		 * Filter WordPress core notice presets shown on the Auto-Rules page.
		 *
		 * @param array $presets Preset definitions.
		 */
		return apply_filters( 'edminboost_anm_wp_notice_presets', $presets );
	}

	/**
	 * Presets with conversion status for the admin UI.
	 *
	 * @return array
	 */
	public static function get_wp_presets_with_status() {
		$presets = self::get_wp_notice_presets();

		foreach ( $presets as $index => $preset ) {
			$rule = self::find_rule_by_wp_preset( $preset['id'] ?? '' );

			$presets[ $index ]['converted'] = ! empty( $rule );
			$presets[ $index ]['rule_id']   = $rule['id'] ?? '';
		}

		return $presets;
	}

	/**
	 * Find a rule created from a WordPress notice preset.
	 *
	 * @param string $preset_id Preset slug.
	 * @return array|null
	 */
	public static function find_rule_by_wp_preset( $preset_id ) {
		$preset_id = sanitize_key( $preset_id );

		if ( '' === $preset_id ) {
			return null;
		}

		foreach ( self::get_all() as $rule ) {
			if ( ( $rule['wp_notice_preset'] ?? '' ) === $preset_id ) {
				return $rule;
			}
		}

		return null;
	}

	/**
	 * Create a rule from a WordPress core notice preset.
	 *
	 * @param string $preset_id Preset slug.
	 * @return array|WP_Error
	 */
	public static function create_from_wp_preset( $preset_id ) {
		$preset_id = sanitize_key( $preset_id );
		$preset    = null;

		foreach ( self::get_wp_notice_presets() as $candidate ) {
			if ( ( $candidate['id'] ?? '' ) === $preset_id ) {
				$preset = $candidate;
				break;
			}
		}

		if ( ! $preset ) {
			return new WP_Error( 'invalid_preset', __( 'Unknown notice preset.', EDMINBOOST_ANM_TEXT_DOMAIN ) );
		}

		if ( self::find_rule_by_wp_preset( $preset_id ) ) {
			return new WP_Error( 'already_converted', __( 'This notice is already converted to a rule.', EDMINBOOST_ANM_TEXT_DOMAIN ) );
		}

		return self::save(
			array(
				'name'              => $preset['name'],
				'match_type'        => 'keyword',
				'match_string'      => $preset['match_string'],
				'action'            => 'auto_dismiss_inbox',
				'enabled'           => true,
				'wp_notice_preset'  => $preset_id,
			)
		);
	}

	/**
	 * Create rule from inbox notice.
	 *
	 * @param string $inbox_id Inbox notice ID.
	 * @return array|WP_Error
	 */
	public static function create_from_inbox( $inbox_id ) {
		$notice = EDMINBOOST_ANM_Inbox::get( $inbox_id );

		if ( ! $notice ) {
			return new WP_Error( 'not_found', __( 'Notice not found.', EDMINBOOST_ANM_TEXT_DOMAIN ) );
		}

		if ( ! empty( $notice['converted_rule_id'] ) && self::get( $notice['converted_rule_id'] ) ) {
			return new WP_Error( 'already_converted', __( 'This notice is already converted to a rule.', EDMINBOOST_ANM_TEXT_DOMAIN ) );
		}

		$keyword = wp_trim_words( wp_strip_all_tags( $notice['message'] ), 4, '' );

		$rule = self::save(
			array(
				'name'         => sprintf(
					/* translators: %s: notice source plugin name */
					__( 'Rule from %s', EDMINBOOST_ANM_TEXT_DOMAIN ),
					$notice['source_plugin']
				),
				'match_type'   => 'keyword',
				'match_string' => $keyword,
				'action'       => 'auto_dismiss_inbox',
				'enabled'      => true,
			)
		);

		if ( is_wp_error( $rule ) ) {
			return $rule;
		}

		EDMINBOOST_ANM_Inbox::mark_converted_to_rule( $inbox_id, $rule['id'] );

		return $rule;
	}

	/**
	 * Remove a rule created from an inbox notice and clear the inbox link.
	 *
	 * @param string $inbox_id Inbox notice ID.
	 * @return true|WP_Error
	 */
	public static function unconvert_inbox( $inbox_id ) {
		$notice = EDMINBOOST_ANM_Inbox::get( $inbox_id );

		if ( ! $notice ) {
			return new WP_Error( 'not_found', __( 'Notice not found.', EDMINBOOST_ANM_TEXT_DOMAIN ) );
		}

		$rule_id = sanitize_key( $notice['converted_rule_id'] ?? '' );

		if ( '' !== $rule_id ) {
			self::delete( $rule_id );
		}

		EDMINBOOST_ANM_Inbox::clear_converted_to_rule( $inbox_id );

		return true;
	}

	/**
	 * Remove a rule created from a WordPress notice preset.
	 *
	 * @param string $preset_id Preset slug.
	 * @return true|WP_Error
	 */
	public static function unconvert_wp_preset( $preset_id ) {
		$preset_id = sanitize_key( $preset_id );
		$rule      = self::find_rule_by_wp_preset( $preset_id );

		if ( ! $rule ) {
			return new WP_Error( 'not_converted', __( 'No rule exists for this notice preset.', EDMINBOOST_ANM_TEXT_DOMAIN ) );
		}

		self::delete( $rule['id'] ?? '' );

		return true;
	}
}
