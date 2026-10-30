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
	 * Custom upsell blacklist phrases from settings (always eligible for matching).
	 *
	 * @return array
	 */
	public static function get_custom_blacklist_phrases() {
		$custom = EDMINBOOST_ANM_Settings::get()['upsell_preset']['custom_phrases'] ?? array();

		if ( ! is_array( $custom ) ) {
			return array();
		}

		return $custom;
	}

	/**
	 * Phrases that currently suppress upsell notices (custom always; built-in when preset enabled).
	 *
	 * @return array
	 */
	public static function get_active_blacklist_phrases() {
		$settings = EDMINBOOST_ANM_Settings::get();
		$phrases  = self::get_custom_blacklist_phrases();

		if ( ! empty( $settings['upsell_preset']['enabled'] ) ) {
			$phrases = array_merge( self::get_builtin_preset_phrases(), $phrases );
		}

		return self::dedupe_phrases( $phrases );
	}

	/**
	 * All active preset phrases (built-in plus custom settings).
	 *
	 * @return array
	 */
	public static function get_preset_phrases() {
		return self::get_active_blacklist_phrases();
	}

	/**
	 * Remove duplicate phrases (case-insensitive).
	 *
	 * @param array $phrases Phrase list.
	 * @return array
	 */
	private static function dedupe_phrases( $phrases ) {
		if ( ! is_array( $phrases ) || empty( $phrases ) ) {
			return array();
		}

		$unique = array();
		$seen   = array();

		foreach ( $phrases as $phrase ) {
			$phrase = trim( (string) $phrase );

			if ( '' === $phrase ) {
				continue;
			}

			$key = strtolower( $phrase );

			if ( isset( $seen[ $key ] ) ) {
				continue;
			}

			$seen[ $key ] = true;
			$unique[]     = $phrase;
		}

		return $unique;
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
		$rules = is_array( $rules ) ? $rules : array();

		if ( class_exists( 'EDMINBOOST_ANM_Multisite' ) ) {
			return EDMINBOOST_ANM_Multisite::merge_rules_layers( $rules );
		}

		return $rules;
	}

	/**
	 * Get rules stored on the current site (without network merge).
	 *
	 * @return array
	 */
	public static function get_site_rules() {
		$rules = get_option( EDMINBOOST_ANM_Settings::RULES_OPTION, array() );

		return is_array( $rules ) ? $rules : array();
	}

	/**
	 * Get rules stored at the network level.
	 *
	 * @return array
	 */
	public static function get_network_rules() {
		if ( ! class_exists( 'EDMINBOOST_ANM_Multisite' ) || ! EDMINBOOST_ANM_Multisite::is_active() ) {
			return array();
		}

		return EDMINBOOST_ANM_Multisite::get_network_rules_raw();
	}

	/**
	 * Save all rules.
	 *
	 * @param array  $rules   Rules list.
	 * @param string $context Storage context: site or network.
	 * @return bool
	 */
	public static function save_all( $rules, $context = 'site' ) {
		if ( 'network' === $context ) {
			if ( ! class_exists( 'EDMINBOOST_ANM_Multisite' ) || ! EDMINBOOST_ANM_Multisite::is_active() ) {
				return false;
			}

			return update_site_option( EDMINBOOST_ANM_Multisite::NETWORK_RULES_OPTION, $rules );
		}

		if ( class_exists( 'EDMINBOOST_ANM_Multisite' ) && ! EDMINBOOST_ANM_Multisite::site_can_edit_rules() ) {
			return false;
		}

		return update_option( EDMINBOOST_ANM_Settings::RULES_OPTION, $rules, false );
	}

	/**
	 * Rules list for CRUD in a given storage context.
	 *
	 * @param string $context site|network.
	 * @return array
	 */
	private static function get_mutable_rules( $context = 'site' ) {
		if ( 'network' === $context ) {
			return self::get_network_rules();
		}

		return self::get_site_rules();
	}

	/**
	 * Whether a rule ID belongs to the network ruleset.
	 *
	 * @param string $id Rule ID.
	 * @return bool
	 */
	private static function is_network_rule_id( $id ) {
		foreach ( self::get_network_rules() as $rule ) {
			if ( self::id_matches( $rule['id'] ?? '', $id ) ) {
				return true;
			}
		}

		return false;
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

		$context = sanitize_key( $data['context'] ?? 'site' );
		if ( ! in_array( $context, array( 'site', 'network' ), true ) ) {
			$context = 'site';
		}

		if ( 'network' === $context ) {
			if ( ! class_exists( 'EDMINBOOST_ANM_Multisite' ) || ! EDMINBOOST_ANM_Multisite::is_active() ) {
				return new WP_Error( 'forbidden', __( 'Permission denied.', EDMINBOOST_ANM_TEXT_DOMAIN ) );
			}
			if ( ! current_user_can( EDMINBOOST_ANM_Multisite::NETWORK_CAPABILITY ) ) {
				return new WP_Error( 'forbidden', __( 'Permission denied.', EDMINBOOST_ANM_TEXT_DOMAIN ) );
			}
		} elseif ( class_exists( 'EDMINBOOST_ANM_Multisite' ) && ! EDMINBOOST_ANM_Multisite::site_can_edit_rules() ) {
			return new WP_Error( 'forbidden', __( 'Auto-rules are managed by the network administrator.', EDMINBOOST_ANM_TEXT_DOMAIN ) );
		}

		$rules = self::get_mutable_rules( $context );
		$id    = ! empty( $data['id'] ) ? sanitize_key( $data['id'] ) : sanitize_key( 'rule_' . wp_generate_password( 8, false ) );

		$existing_hits    = 0;
		$created          = time();
		$wp_notice_preset = '';
		$schedule         = self::get_default_schedule();

		foreach ( $rules as $rule ) {
			if ( self::id_matches( $rule['id'] ?? '', $id ) ) {
				$existing_hits    = (int) ( $rule['hit_count'] ?? 0 );
				$created          = (int) ( $rule['created'] ?? time() );
				$wp_notice_preset = sanitize_key( $rule['wp_notice_preset'] ?? '' );
				$schedule         = self::sanitize_schedule( $rule['schedule'] ?? array() );
				break;
			}
		}

		if ( isset( $data['schedule'] ) && is_array( $data['schedule'] ) ) {
			$schedule = self::sanitize_schedule( $data['schedule'] );
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
			'schedule'     => $schedule,
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

		self::save_all( $rules, $context );

		return $new_rule;
	}

	/**
	 * Delete a rule.
	 *
	 * @param string $id      Rule ID.
	 * @param string $context Storage context: site or network.
	 * @return bool
	 */
	public static function delete( $id, $context = 'site' ) {
		$context = sanitize_key( $context );
		if ( ! in_array( $context, array( 'site', 'network' ), true ) ) {
			$context = 'site';
		}

		if ( 'network' === $context ) {
			if ( ! class_exists( 'EDMINBOOST_ANM_Multisite' ) || ! EDMINBOOST_ANM_Multisite::is_active()
				|| ! current_user_can( EDMINBOOST_ANM_Multisite::NETWORK_CAPABILITY ) ) {
				return false;
			}
		} elseif ( class_exists( 'EDMINBOOST_ANM_Multisite' ) && ! EDMINBOOST_ANM_Multisite::site_can_edit_rules() ) {
			return false;
		}

		if ( 'site' === $context && self::is_network_rule_id( $id ) && class_exists( 'EDMINBOOST_ANM_Multisite' ) && EDMINBOOST_ANM_Multisite::shares_network_rules() ) {
			return false;
		}

		$rules = array_filter(
			self::get_mutable_rules( $context ),
			static function ( $rule ) use ( $id ) {
				return ! self::id_matches( $rule['id'] ?? '', $id );
			}
		);

		return self::save_all( array_values( $rules ), $context );
	}

	/**
	 * Toggle rule enabled state.
	 *
	 * @param string $id      Rule ID.
	 * @param bool   $enabled Enabled state.
	 * @param string $context Storage context: site or network.
	 * @return array|null
	 */
	public static function toggle( $id, $enabled, $context = 'site' ) {
		$context = sanitize_key( $context );
		if ( ! in_array( $context, array( 'site', 'network' ), true ) ) {
			$context = 'site';
		}

		if ( 'network' === $context ) {
			if ( ! class_exists( 'EDMINBOOST_ANM_Multisite' ) || ! EDMINBOOST_ANM_Multisite::is_active()
				|| ! current_user_can( EDMINBOOST_ANM_Multisite::NETWORK_CAPABILITY ) ) {
				return null;
			}
		} elseif ( class_exists( 'EDMINBOOST_ANM_Multisite' ) && ! EDMINBOOST_ANM_Multisite::site_can_edit_rules() ) {
			return null;
		}

		if ( 'site' === $context && self::is_network_rule_id( $id ) ) {
			return null;
		}

		$rules = self::get_mutable_rules( $context );

		foreach ( $rules as $index => $rule ) {
			if ( self::id_matches( $rule['id'] ?? '', $id ) ) {
				$rules[ $index ]['enabled'] = (bool) $enabled;
				self::save_all( $rules, $context );
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
		$context = self::is_network_rule_id( $id ) ? 'network' : 'site';
		$rules   = self::get_mutable_rules( $context );

		foreach ( $rules as $index => $rule ) {
			if ( self::id_matches( $rule['id'] ?? '', $id ) ) {
				$rules[ $index ]['hit_count'] = (int) ( $rule['hit_count'] ?? 0 ) + 1;
				self::save_all( $rules, $context );
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
		$haystack = strtolower( wp_strip_all_tags( $message ) );

		$blacklist_match = self::match_upsell_blacklist( $haystack );
		if ( $blacklist_match ) {
			return $blacklist_match;
		}

		foreach ( self::get_all() as $rule ) {
			if ( ! self::is_rule_effective( $rule ) ) {
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
					if ( self::html_matches_css_selector( $html, $string ) ) {
						$matched = true;
					}
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
	 * Match notice text against custom blacklist and optional built-in upsell preset.
	 *
	 * @param string $haystack Lowercase plain notice text.
	 * @return array|null
	 */
	private static function match_upsell_blacklist( $haystack ) {
		foreach ( self::get_custom_blacklist_phrases() as $phrase ) {
			if ( false !== strpos( $haystack, strtolower( $phrase ) ) ) {
				return array(
					'source' => 'custom_blacklist',
					'id'     => 'upsell_custom_blacklist',
					'name'   => __( 'Upsell Blacklist', EDMINBOOST_ANM_TEXT_DOMAIN ),
					'action' => 'auto_dismiss_inbox',
					'type'   => 'upsell',
				);
			}
		}

		$settings = EDMINBOOST_ANM_Settings::get();

		if ( empty( $settings['upsell_preset']['enabled'] ) ) {
			return null;
		}

		foreach ( self::get_builtin_preset_phrases() as $phrase ) {
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

		return null;
	}

	/**
	 * Detect WordPress standard notice severity from markup.
	 *
	 * @param string $html Notice HTML.
	 * @return string|null Level slug (success, error, warning, info) or null if not standard.
	 */
	public static function detect_standard_notice_level( $html ) {
		if ( ! self::is_admin_notice_markup( $html ) ) {
			return null;
		}

		if ( preg_match( '/class=["\'][^"\']*\b(notice-success|updated)\b/i', $html ) ) {
			return 'success';
		}

		if ( preg_match( '/class=["\'][^"\']*\b(notice-error|error)\b/i', $html ) ) {
			return 'error';
		}

		if ( preg_match( '/class=["\'][^"\']*\bnotice-warning\b/i', $html ) ) {
			return 'warning';
		}

		if ( preg_match( '/class=["\'][^"\']*\bnotice-info\b/i', $html ) ) {
			return 'info';
		}

		return null;
	}

	/**
	 * Whether HTML looks like a WordPress admin notice container.
	 *
	 * @param string $html Notice HTML.
	 * @return bool
	 */
	public static function is_admin_notice_markup( $html ) {
		return (bool) preg_match( '/class=["\'][^"\']*\b(notice|update-nag)\b/i', $html )
			|| (bool) preg_match( '/<div[^>]+class=["\'][^"\']*\b(error|updated)\b/i', $html );
	}

	/**
	 * Map a standard notice level to an inbox type slug.
	 *
	 * @param string $level Standard level slug.
	 * @return string
	 */
	public static function inbox_type_for_level( $level ) {
		if ( 'error' === $level ) {
			return 'critical';
		}

		if ( in_array( $level, array( 'success', 'warning', 'info' ), true ) ) {
			return $level;
		}

		return 'info';
	}

	/**
	 * Whether notice HTML matches any configured CSS ignore selector.
	 *
	 * @param string $html Notice HTML.
	 * @return bool
	 */
	public static function matches_css_ignore_selectors( $html ) {
		$selectors = EDMINBOOST_ANM_Settings::get_css_ignore_selectors();

		if ( empty( $selectors ) ) {
			return false;
		}

		foreach ( $selectors as $selector ) {
			if ( self::html_matches_css_selector( $html, $selector ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Basic CSS selector match against notice HTML (class, id, and tag.class).
	 *
	 * @param string $html     Notice HTML.
	 * @param string $selector CSS selector fragment.
	 * @return bool
	 */
	public static function html_matches_css_selector( $html, $selector ) {
		$selector = trim( $selector );

		if ( '' === $selector ) {
			return false;
		}

		if ( preg_match( '/^\.([a-zA-Z0-9_-]+)$/', $selector, $matches ) ) {
			return (bool) preg_match(
				'/class=["\'][^"\']*\b' . preg_quote( $matches[1], '/' ) . '\b/i',
				$html
			);
		}

		if ( preg_match( '/^#([a-zA-Z0-9_-]+)$/', $selector, $matches ) ) {
			return (bool) preg_match(
				'/id=["\']' . preg_quote( $matches[1], '/' ) . '["\']/i',
				$html
			);
		}

		if ( preg_match( '/^([a-zA-Z0-9_-]+)\.([a-zA-Z0-9_-]+)$/', $selector, $matches ) ) {
			return (bool) preg_match(
				'/' . preg_quote( $matches[1], '/' ) . '[^>]*class=["\'][^"\']*\b' . preg_quote( $matches[2], '/' ) . '\b/i',
				$html
			);
		}

		return false;
	}

	/**
	 * Resolve handling mode for an unmatched notice from plugin settings.
	 *
	 * @param string $html Notice HTML.
	 * @return array{mode: string, type: string, source: string}
	 */
	public static function resolve_notice_policy( $html ) {
		$settings = EDMINBOOST_ANM_Settings::get();
		$level    = self::detect_standard_notice_level( $html );

		if ( $level ) {
			$modes = $settings['standard_admin_notices'] ?? EDMINBOOST_ANM_Settings::get_standard_admin_notice_defaults();

			return array(
				'mode'   => $modes[ $level ] ?? 'default',
				'type'   => self::inbox_type_for_level( $level ),
				'source' => 'standard_level',
			);
		}

		if ( self::is_admin_notice_markup( $html ) ) {
			$non_standard = $settings['non_standard_admin_notices'] ?? array();

			return array(
				'mode'   => $non_standard['no_level'] ?? 'default',
				'type'   => 'info',
				'source' => 'non_standard',
			);
		}

		return array(
			'mode'   => 'default',
			'type'   => 'info',
			'source' => 'none',
		);
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

		foreach ( self::get_active_blacklist_phrases() as $phrase ) {
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
	 * Default schedule shape for new rules (disabled until enabled in the UI).
	 *
	 * @return array{enabled:bool,days:string[],start:string,end:string}
	 */
	public static function get_default_schedule() {
		return array(
			'enabled' => false,
			'days'    => array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday' ),
			'start'   => '09:00',
			'end'     => '17:00',
		);
	}

	/**
	 * Weekday choices for rule schedules (site-local).
	 *
	 * @return array<string, string>
	 */
	public static function get_schedule_weekday_choices() {
		return EDMINBOOST_ANM_Digest::get_send_weekday_choices();
	}

	/**
	 * Sanitize schedule settings stored on a rule.
	 *
	 * @param array $raw Raw schedule array.
	 * @return array{enabled:bool,days:string[],start:string,end:string}
	 */
	public static function sanitize_schedule( $raw ) {
		$defaults = self::get_default_schedule();

		if ( ! is_array( $raw ) ) {
			return $defaults;
		}

		$enabled = ! empty( $raw['enabled'] );
		$days    = array();

		if ( ! empty( $raw['days'] ) && is_array( $raw['days'] ) ) {
			$choices = array_keys( self::get_schedule_weekday_choices() );
			foreach ( $raw['days'] as $day ) {
				$day = sanitize_key( (string) $day );
				if ( in_array( $day, $choices, true ) ) {
					$days[] = $day;
				}
			}
			$days = array_values( array_unique( $days ) );
		}

		return array(
			'enabled' => $enabled,
			'days'    => $days,
			'start'   => self::sanitize_schedule_time( $raw['start'] ?? $defaults['start'], $defaults['start'] ),
			'end'     => self::sanitize_schedule_time( $raw['end'] ?? $defaults['end'], $defaults['end'] ),
		);
	}

	/**
	 * Sanitize HH:MM time for schedules.
	 *
	 * @param string $value   Raw time.
	 * @param string $fallback Fallback when invalid.
	 * @return string
	 */
	public static function sanitize_schedule_time( $value, $fallback = '09:00' ) {
		$value = sanitize_text_field( (string) $value );

		if ( preg_match( '/^([01]?\d|2[0-3]):([0-5]\d)$/', $value, $matches ) ) {
			return sprintf( '%02d:%02d', (int) $matches[1], (int) $matches[2] );
		}

		return $fallback;
	}

	/**
	 * Build schedule array from rule save AJAX POST fields.
	 *
	 * @param array $post Raw POST (unslashed).
	 * @return array{enabled:bool,days:string[],start:string,end:string}
	 */
	public static function schedule_from_request( $post ) {
		$days = array();
		if ( isset( $post['schedule_days'] ) && is_array( $post['schedule_days'] ) ) {
			$days = $post['schedule_days'];
		}

		return self::sanitize_schedule(
			array(
				'enabled' => ! empty( $post['schedule_enabled'] ),
				'days'    => $days,
				'start'   => $post['schedule_start'] ?? '',
				'end'     => $post['schedule_end'] ?? '',
			)
		);
	}

	/**
	 * Whether a rule is enabled and within its optional schedule window.
	 *
	 * @param array $rule Rule record.
	 * @return bool
	 */
	public static function is_rule_effective( $rule ) {
		if ( empty( $rule['enabled'] ) ) {
			return false;
		}

		return self::is_schedule_active( $rule['schedule'] ?? array() );
	}

	/**
	 * Whether the current site-local time falls inside a rule schedule.
	 *
	 * @param array $schedule Sanitized schedule array.
	 * @return bool True when schedule is off or the current moment is inside the window.
	 */
	public static function is_schedule_active( $schedule ) {
		$schedule = self::sanitize_schedule( is_array( $schedule ) ? $schedule : array() );

		if ( empty( $schedule['enabled'] ) ) {
			return true;
		}

		$tz  = wp_timezone();
		$now = new DateTimeImmutable( 'now', $tz );
		$w   = (int) $now->format( 'w' );

		if ( ! empty( $schedule['days'] ) ) {
			$allowed = array();
			foreach ( $schedule['days'] as $slug ) {
				$allowed[] = EDMINBOOST_ANM_Digest::weekday_slug_to_php_w( $slug );
			}
			if ( ! in_array( $w, $allowed, true ) ) {
				return false;
			}
		}

		$start_minutes = self::schedule_time_to_minutes( $schedule['start'] );
		$end_minutes   = self::schedule_time_to_minutes( $schedule['end'] );
		$current       = ( (int) $now->format( 'G' ) * 60 ) + (int) $now->format( 'i' );

		if ( $start_minutes <= $end_minutes ) {
			return $current >= $start_minutes && $current <= $end_minutes;
		}

		return $current >= $start_minutes || $current <= $end_minutes;
	}

	/**
	 * Convert HH:MM to minutes from midnight.
	 *
	 * @param string $time HH:MM.
	 * @return int
	 */
	private static function schedule_time_to_minutes( $time ) {
		$parts = explode( ':', $time );
		$hour  = isset( $parts[0] ) ? (int) $parts[0] : 0;
		$min   = isset( $parts[1] ) ? (int) $parts[1] : 0;

		return ( $hour * 60 ) + $min;
	}

	/**
	 * Format a schedule time for display using site time format.
	 *
	 * @param string $time HH:MM.
	 * @return string
	 */
	public static function format_schedule_time_display( $time ) {
		$time  = self::sanitize_schedule_time( $time );
		$parts = explode( ':', $time );
		$dt    = ( new DateTimeImmutable( 'now', wp_timezone() ) )->setTime( (int) $parts[0], (int) $parts[1] );

		return wp_date( get_option( 'time_format' ), $dt->getTimestamp() );
	}

	/**
	 * Short human-readable schedule summary for the rules table.
	 *
	 * @param array $rule Rule record.
	 * @return string Empty when scheduling is off.
	 */
	public static function get_schedule_summary( $rule ) {
		$schedule = self::sanitize_schedule( $rule['schedule'] ?? array() );

		if ( empty( $schedule['enabled'] ) ) {
			return '';
		}

		$choices = self::get_schedule_weekday_choices();
		$labels  = array();

		if ( empty( $schedule['days'] ) ) {
			$labels[] = __( 'Every day', EDMINBOOST_ANM_TEXT_DOMAIN );
		} else {
			foreach ( $schedule['days'] as $slug ) {
				if ( isset( $choices[ $slug ] ) ) {
					$labels[] = $choices[ $slug ];
				}
			}
		}

		$days_part = implode( ', ', $labels );
		$time_part = sprintf(
			/* translators: 1: start time, 2: end time (site time format) */
			__( '%1$s–%2$s', EDMINBOOST_ANM_TEXT_DOMAIN ),
			self::format_schedule_time_display( $schedule['start'] ),
			self::format_schedule_time_display( $schedule['end'] )
		);

		return trim( $days_part . ' · ' . $time_part );
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
