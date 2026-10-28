<?php
/**
 * Centralized plugin settings — defaults, retrieval, sanitization.
 *
 * Purpose: Single source of truth for the edminboost_anm_settings option.
 *
 * @package EdminBoost\AdminNoticeManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings helper.
 */
class EDMINBOOST_ANM_Settings {

	/**
	 * Settings option name.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'edminboost_anm_settings';

	/**
	 * Rules option name.
	 *
	 * @var string
	 */
	const RULES_OPTION = 'edminboost_anm_rules';

	/**
	 * Inbox option name.
	 *
	 * @var string
	 */
	const INBOX_OPTION = 'edminboost_anm_inbox';

	/**
	 * Stats option name.
	 *
	 * @var string
	 */
	const STATS_OPTION = 'edminboost_anm_stats';

	/**
	 * Per-notice hash display preferences (normal | forever).
	 *
	 * @var string
	 */
	const HASH_PREFS_OPTION = 'edminboost_anm_notice_hash_prefs';

	/**
	 * Installed version option name.
	 *
	 * @var string
	 */
	const VERSION_OPTION = 'edminboost_anm_version';

	/**
	 * Settings API group identifier.
	 *
	 * @var string
	 */
	const SETTINGS_GROUP = 'edminboost_anm_settings_group';

	/**
	 * Required capability for settings access.
	 *
	 * @var string
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * Allowed handling modes for standard and non-standard admin notices.
	 *
	 * @var string[]
	 */
	const ADMIN_NOTICE_MODES = array( 'inbox', 'hide', 'default' );

	/**
	 * Standard WordPress notice severity levels (settings keys).
	 *
	 * @var string[]
	 */
	const STANDARD_NOTICE_LEVELS = array( 'success', 'error', 'warning', 'info' );

	/**
	 * Allowed per-user notice hiding visibility modes.
	 *
	 * @var string[]
	 */
	const HIDING_VISIBILITY_MODES = array( 'all', 'only', 'except' );

	/**
	 * Allowed ACL visibility values per role (dashboard notifications).
	 *
	 * @var string[]
	 */
	const ACL_ROLE_VISIBILITY = array( 'display', 'hide' );

	/**
	 * Global notice management modes (General settings).
	 *
	 * @var string[]
	 */
	const MANAGE_NOTICES_MODES = array( 'show_all', 'hide_all', 'hide_selected', 'compact_panel' );

	/**
	 * Display experience preset slugs (General settings UI).
	 *
	 * @var string[]
	 */
	const DISPLAY_EXPERIENCE_PRESETS = array( 'dashboard', 'clean_inbox', 'toolbar_only' );

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	public static function get_defaults() {
		$defaults = array(
			'enabled'           => false,
			'hide_core_notices' => false,
			'manage_notices_mode'                  => 'hide_selected',
			'disable_plugin_update_notifications'  => false,
			'disable_core_update_notifications'    => false,
			'standard_admin_notices'     => self::get_standard_admin_notice_defaults(),
			'non_standard_admin_notices' => array(
				'no_level'             => 'inbox',
				'css_ignore_selectors' => '',
			),
			'hiding_notifications'       => array(
				'mode'  => 'all',
				'users' => array(),
			),
			'acl'                        => array(
				'roles' => array(),
			),
			'notice_panel'      => array(
				'enabled'     => true,
				'style'       => 'link',
				'position'    => 'default',
				'badge_style' => 'count',
				'colors'      => self::get_notice_panel_color_defaults(),
			),
			'upsell_preset'     => array(
				'enabled'         => false,
				'custom_phrases'  => array(),
			),
			'safety_guard'      => array(
				'enabled' => true,
				'mode'    => 'strict',
			),
			'client_shield'     => array(
				'enabled' => false,
				'roles'   => array( 'editor', 'author', 'shop_manager' ),
			),
			'digest'            => array(
				'enabled'                => false,
				'recipients'             => get_option( 'admin_email' ),
				'send_weekday'           => EDMINBOOST_ANM_Digest::DEFAULT_SEND_WEEKDAY,
				'subject'                => '',
				'intro'                  => '',
				'include_stats'          => true,
				'include_recent_notices' => true,
				'recent_notices_count'   => 10,
				'footer'                 => '',
			),
			'webhook'           => array(
				'enabled' => false,
				'url'     => '',
				'message' => '',
			),
			'word_filters'      => array(
				'spam_words'      => array(),
				'whitelist_words' => array(),
			),
			'block_ad_redirects' => array(
				'enabled' => false,
				'urls'    => array(),
			),
		);

		/**
		 * Filter default plugin settings.
		 *
		 * @param array $defaults Default settings.
		 */
		return apply_filters( 'edminboost_anm_settings_defaults', $defaults );
	}

	/**
	 * Get merged plugin settings.
	 *
	 * @return array
	 */
	public static function get() {
		$settings = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		return self::merge_settings( $settings );
	}

	/**
	 * Whether the plugin is globally enabled.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$settings = self::get();

		return ! empty( $settings['enabled'] );
	}

	/**
	 * Merge stored settings with plugin defaults.
	 *
	 * @param array $settings Stored or empty settings.
	 * @return array
	 */
	private static function merge_settings( $settings ) {
		$defaults = self::get_defaults();
		$merged   = wp_parse_args( $settings, $defaults );

		$nested_keys = array(
			'notice_panel',
			'upsell_preset',
			'safety_guard',
			'client_shield',
			'digest',
			'webhook',
			'word_filters',
			'block_ad_redirects',
			'standard_admin_notices',
			'non_standard_admin_notices',
			'hiding_notifications',
			'acl',
		);

		foreach ( $nested_keys as $key ) {
			$merged[ $key ] = wp_parse_args(
				isset( $merged[ $key ] ) && is_array( $merged[ $key ] ) ? $merged[ $key ] : array(),
				$defaults[ $key ]
			);
		}

		$merged['notice_panel']['colors'] = wp_parse_args(
			isset( $merged['notice_panel']['colors'] ) && is_array( $merged['notice_panel']['colors'] )
				? $merged['notice_panel']['colors']
				: array(),
			self::get_notice_panel_color_defaults()
		);

		if ( ! is_array( $merged['client_shield']['roles'] ) ) {
			$merged['client_shield']['roles'] = $defaults['client_shield']['roles'];
		}

		$merged['standard_admin_notices'] = wp_parse_args(
			isset( $merged['standard_admin_notices'] ) && is_array( $merged['standard_admin_notices'] )
				? $merged['standard_admin_notices']
				: array(),
			self::get_standard_admin_notice_defaults()
		);

		$merged['non_standard_admin_notices'] = wp_parse_args(
			isset( $merged['non_standard_admin_notices'] ) && is_array( $merged['non_standard_admin_notices'] )
				? $merged['non_standard_admin_notices']
				: array(),
			$defaults['non_standard_admin_notices']
		);

		if ( ! is_array( $merged['hiding_notifications']['users'] ) ) {
			$merged['hiding_notifications']['users'] = array();
		}

		if ( ! is_array( $merged['block_ad_redirects']['urls'] ) ) {
			$merged['block_ad_redirects']['urls'] = array();
		}

		$merged['block_ad_redirects']['urls'] = array_values(
			array_filter(
				array_map(
					'strval',
					$merged['block_ad_redirects']['urls']
				),
				static function ( $url ) {
					return '' !== trim( $url );
				}
			)
		);

		$merged['hiding_notifications']['users'] = array_values(
			array_unique(
				array_map(
					'intval',
					$merged['hiding_notifications']['users']
				)
			)
		);

		$acl_stored = isset( $merged['acl']['roles'] ) && is_array( $merged['acl']['roles'] )
			? $merged['acl']['roles']
			: array();
		$merged['acl']['roles']             = self::merge_acl_role_map( $acl_stored );
		$merged['acl']['roles']             = self::filter_acl_role_map( $merged['acl']['roles'] );

		/**
		 * Filter merged plugin settings.
		 *
		 * @param array $merged   Merged settings.
		 * @param array $settings Raw stored settings.
		 */
		return apply_filters( 'edminboost_anm_settings', $merged, $settings );
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * @param array $input Raw settings input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		$defaults = self::get_defaults();
		$output   = self::get();

		// Global enable is toggled from the dashboard only; omit from Settings form.
		if ( array_key_exists( 'enabled', $input ) ) {
			$output['enabled'] = ! empty( $input['enabled'] );
		}

		$manage_mode = sanitize_key( $input['manage_notices_mode'] ?? $output['manage_notices_mode'] ?? $defaults['manage_notices_mode'] );
		if ( ! in_array( $manage_mode, self::MANAGE_NOTICES_MODES, true ) ) {
			$manage_mode = $defaults['manage_notices_mode'];
		}

		$output['manage_notices_mode']                 = $manage_mode;
		$output['disable_plugin_update_notifications'] = ! empty( $input['disable_plugin_update_notifications'] );
		$output['disable_core_update_notifications']   = ! empty( $input['disable_core_update_notifications'] );
		$output['hide_core_notices']                   = in_array( $manage_mode, array( 'hide_all', 'compact_panel' ), true );

		$standard_input = isset( $input['standard_admin_notices'] ) && is_array( $input['standard_admin_notices'] )
			? $input['standard_admin_notices']
			: array();

		$output['standard_admin_notices'] = self::sanitize_standard_admin_notices( $standard_input );

		$non_standard_input = isset( $input['non_standard_admin_notices'] ) && is_array( $input['non_standard_admin_notices'] )
			? $input['non_standard_admin_notices']
			: array();

		$output['non_standard_admin_notices'] = self::sanitize_non_standard_admin_notices( $non_standard_input );

		$hiding_input = isset( $input['hiding_notifications'] ) && is_array( $input['hiding_notifications'] )
			? $input['hiding_notifications']
			: array();

		$output['hiding_notifications'] = self::sanitize_hiding_notifications( $hiding_input );

		$acl_input = isset( $input['acl'] ) && is_array( $input['acl'] ) ? $input['acl'] : array();
		$output['acl']                    = self::sanitize_acl( $acl_input );

		$panel_input = isset( $input['notice_panel'] ) && is_array( $input['notice_panel'] )
			? $input['notice_panel']
			: array();

		$allowed_styles = self::get_notice_panel_style_slugs();
		$panel_style      = sanitize_key( $panel_input['style'] ?? 'link' );
		if ( ! in_array( $panel_style, $allowed_styles, true ) ) {
			$panel_style = 'link';
		}

		$allowed_positions = self::get_notice_panel_position_slugs();
		$panel_position    = sanitize_key( $panel_input['position'] ?? 'default' );
		if ( ! in_array( $panel_position, $allowed_positions, true ) ) {
			$panel_position = 'default';
		}

		$allowed_badge_styles = self::get_notice_panel_badge_style_slugs();
		$panel_badge_style    = sanitize_key( $panel_input['badge_style'] ?? 'count' );
		if ( ! in_array( $panel_badge_style, $allowed_badge_styles, true ) ) {
			$panel_badge_style = 'count';
		}

		$colors_input = isset( $panel_input['colors'] ) && is_array( $panel_input['colors'] )
			? $panel_input['colors']
			: array();

		$output['notice_panel'] = array(
			'enabled'     => true,
			'style'       => $panel_style,
			'position'    => $panel_position,
			'badge_style' => $panel_badge_style,
			'colors'      => self::sanitize_notice_panel_colors( $colors_input ),
		);

		if ( isset( $input['upsell_preset'] ) && is_array( $input['upsell_preset'] ) ) {
			$upsell_input = $input['upsell_preset'];

			$output['upsell_preset'] = array(
				'enabled'        => ! empty( $upsell_input['enabled'] ),
				'custom_phrases' => self::sanitize_custom_phrases( $upsell_input['custom_phrases'] ?? '' ),
			);
		}

		if ( isset( $input['word_filters'] ) && is_array( $input['word_filters'] ) ) {
			$word_filters_input = $input['word_filters'];

			$output['word_filters'] = array(
				'spam_words'      => self::sanitize_word_list( $word_filters_input['spam_words'] ?? '' ),
				'whitelist_words' => self::sanitize_word_list( $word_filters_input['whitelist_words'] ?? '' ),
			);
		}

		$safety_input = isset( $input['safety_guard'] ) && is_array( $input['safety_guard'] )
			? $input['safety_guard']
			: array();

		$output['safety_guard'] = array(
			'enabled' => ! empty( $safety_input['enabled'] ),
			'mode'    => in_array( $safety_input['mode'] ?? 'strict', array( 'strict', 'normal' ), true )
				? $safety_input['mode']
				: 'strict',
		);

		$shield_input = isset( $input['client_shield'] ) && is_array( $input['client_shield'] )
			? $input['client_shield']
			: array();

		$allowed_roles = array( 'editor', 'author', 'shop_manager', 'administrator' );
		$shield_roles  = array();

		if ( ! empty( $shield_input['roles'] ) && is_array( $shield_input['roles'] ) ) {
			foreach ( $shield_input['roles'] as $role ) {
				$role = sanitize_key( $role );
				if ( in_array( $role, $allowed_roles, true ) ) {
					$shield_roles[] = $role;
				}
			}
		}

		$output['client_shield'] = array(
			'enabled' => ! empty( $shield_input['enabled'] ),
			'roles'   => ! empty( $shield_roles ) ? $shield_roles : $defaults['client_shield']['roles'],
		);

		$digest_input = isset( $input['digest'] ) && is_array( $input['digest'] )
			? $input['digest']
			: array();

		$output['digest'] = self::sanitize_digest_settings( $digest_input, $defaults['digest'] );

		$webhook_input = isset( $input['webhook'] ) && is_array( $input['webhook'] )
			? $input['webhook']
			: array();

		$output['webhook'] = self::sanitize_webhook_settings( $webhook_input, $defaults['webhook'] );

		$redirect_input = isset( $input['block_ad_redirects'] ) && is_array( $input['block_ad_redirects'] )
			? $input['block_ad_redirects']
			: array();

		$output['block_ad_redirects'] = array(
			'enabled' => ! empty( $redirect_input['enabled'] ),
			'urls'    => self::sanitize_block_ad_redirect_urls( $redirect_input['urls'] ?? array() ),
		);

		$output['notice_panel'] = wp_parse_args( $output['notice_panel'], $defaults['notice_panel'] );

		if ( class_exists( 'EDMINBOOST_ANM_Digest' ) ) {
			EDMINBOOST_ANM_Digest::sync_schedule( $output );
		}

		/**
		 * Filter sanitized plugin settings.
		 *
		 * @param array $output Sanitized settings.
		 * @param array $input  Raw input.
		 */
		return apply_filters( 'edminboost_anm_sanitize_settings', $output, $input );
	}

	/**
	 * Default handling mode per standard notice level.
	 *
	 * @return array<string, string>
	 */
	public static function get_standard_admin_notice_defaults() {
		return array(
			'success' => 'inbox',
			'error'   => 'inbox',
			'warning' => 'inbox',
			'info'    => 'inbox',
		);
	}

	/**
	 * Sanitize standard admin notice level settings.
	 *
	 * @param array $input Raw level modes.
	 * @return array<string, string>
	 */
	public static function sanitize_standard_admin_notices( $input ) {
		$defaults = self::get_standard_admin_notice_defaults();
		$output   = array();

		foreach ( self::STANDARD_NOTICE_LEVELS as $level ) {
			$mode = sanitize_key( $input[ $level ] ?? $defaults[ $level ] );
			if ( ! in_array( $mode, self::ADMIN_NOTICE_MODES, true ) ) {
				$mode = $defaults[ $level ];
			}
			$output[ $level ] = $mode;
		}

		return $output;
	}

	/**
	 * Sanitize non-standard admin notice settings.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public static function sanitize_non_standard_admin_notices( $input ) {
		$defaults = self::get_defaults()['non_standard_admin_notices'];
		$mode     = sanitize_key( $input['no_level'] ?? $defaults['no_level'] );
		if ( ! in_array( $mode, self::ADMIN_NOTICE_MODES, true ) ) {
			$mode = $defaults['no_level'];
		}

		$selectors = sanitize_text_field( $input['css_ignore_selectors'] ?? '' );
		$selectors = preg_replace( '/\s+/', ' ', trim( $selectors ) );

		return array(
			'no_level'             => $mode,
			'css_ignore_selectors' => $selectors,
		);
	}

	/**
	 * Sanitize per-user notice hiding settings.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public static function sanitize_hiding_notifications( $input ) {
		$stored   = self::get()['hiding_notifications'];
		$defaults = self::get_defaults()['hiding_notifications'];
		$mode     = sanitize_key( $input['mode'] ?? $stored['mode'] ?? $defaults['mode'] );

		if ( ! in_array( $mode, self::HIDING_VISIBILITY_MODES, true ) ) {
			$mode = $defaults['mode'];
		}

		$user_ids = array();

		if ( isset( $input['users'] ) && is_array( $input['users'] ) ) {
			foreach ( $input['users'] as $user_id ) {
				$user_id = absint( $user_id );
				if ( $user_id < 1 ) {
					continue;
				}
				if ( ! get_userdata( $user_id ) ) {
					continue;
				}
				$user_ids[] = $user_id;
			}
		} elseif ( in_array( $mode, array( 'only', 'except' ), true ) ) {
			$user_ids = array();
		} else {
			$user_ids = isset( $stored['users'] ) && is_array( $stored['users'] ) ? $stored['users'] : array();
		}

		$user_ids = array_values( array_unique( $user_ids ) );

		return array(
			'mode'  => $mode,
			'users' => $user_ids,
		);
	}

	/**
	 * Default ACL map: every registered role displays dashboard notifications.
	 *
	 * @return array<string, string>
	 */
	public static function get_default_acl_role_map() {
		$map = array();

		if ( ! function_exists( 'wp_roles' ) ) {
			return $map;
		}

		foreach ( array_keys( wp_roles()->roles ) as $role ) {
			$map[ $role ] = 'display';
		}

		return $map;
	}

	/**
	 * Merge stored ACL role map with defaults for all registered roles.
	 *
	 * @param array $stored Stored role => visibility map.
	 * @return array<string, string>
	 */
	private static function merge_acl_role_map( $stored ) {
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return wp_parse_args( $stored, self::get_default_acl_role_map() );
	}

	/**
	 * Keep only valid roles and visibility values in an ACL map.
	 *
	 * @param array $map Role => visibility map.
	 * @return array<string, string>
	 */
	private static function filter_acl_role_map( $map ) {
		if ( ! is_array( $map ) || ! function_exists( 'wp_roles' ) ) {
			return array();
		}

		$valid_roles = array_keys( wp_roles()->roles );
		$filtered    = array();

		foreach ( $map as $role => $visibility ) {
			$role = sanitize_key( $role );
			if ( ! in_array( $role, $valid_roles, true ) ) {
				continue;
			}
			$visibility = sanitize_key( $visibility );
			if ( ! in_array( $visibility, self::ACL_ROLE_VISIBILITY, true ) ) {
				$visibility = 'display';
			}
			$filtered[ $role ] = $visibility;
		}

		return $filtered;
	}

	/**
	 * Sanitize ACL settings.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public static function sanitize_acl( $input ) {
		$stored   = self::get()['acl'] ?? self::get_defaults()['acl'];
		$base_map = self::merge_acl_role_map(
			isset( $stored['roles'] ) && is_array( $stored['roles'] ) ? $stored['roles'] : array()
		);

		$roles_input = isset( $input['roles'] ) && is_array( $input['roles'] ) ? $input['roles'] : array();

		foreach ( $roles_input as $role => $visibility ) {
			$role       = sanitize_key( $role );
			$visibility = sanitize_key( $visibility );
			if ( ! isset( $base_map[ $role ] ) ) {
				continue;
			}
			if ( ! in_array( $visibility, self::ACL_ROLE_VISIBILITY, true ) ) {
				$visibility = 'display';
			}
			$base_map[ $role ] = $visibility;
		}

		return array(
			'roles' => self::filter_acl_role_map( $base_map ),
		);
	}

	/**
	 * Registered roles for the ACL settings UI (slug + label).
	 *
	 * @return array<int, array{slug: string, label: string}>
	 */
	public static function get_acl_role_choices() {
		if ( ! function_exists( 'wp_roles' ) ) {
			return array();
		}

		$choices = array();

		foreach ( wp_roles()->roles as $role_slug => $role_data ) {
			$name = isset( $role_data['name'] ) ? translate_user_role( $role_data['name'] ) : $role_slug;
			$choices[] = array(
				'slug'  => $role_slug,
				'label' => $name,
			);
		}

		return $choices;
	}

	/**
	 * Whether the user should see dashboard notifications per role ACL.
	 *
	 * Users with multiple roles see notifications when at least one role is set to display.
	 *
	 * @param int|null $user_id User ID or null for current user.
	 * @return bool
	 */
	public static function should_display_dashboard_notifications_for_user( $user_id = null ) {
		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		$user_id = absint( $user_id );
		if ( $user_id < 1 ) {
			return true;
		}

		$user = get_userdata( $user_id );
		if ( ! $user || empty( $user->roles ) ) {
			return true;
		}

		$settings  = self::get();
		$acl_roles = $settings['acl']['roles'] ?? array();
		if ( ! is_array( $acl_roles ) || empty( $acl_roles ) ) {
			return true;
		}

		foreach ( $user->roles as $role ) {
			$visibility = $acl_roles[ $role ] ?? 'display';
			if ( 'display' === $visibility ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Resolved global notice management mode (legacy hide_core_notices maps to hide_all).
	 *
	 * @return string
	 */
	public static function get_manage_notices_mode() {
		$settings = self::get();
		$mode     = sanitize_key( $settings['manage_notices_mode'] ?? '' );

		if ( in_array( $mode, self::MANAGE_NOTICES_MODES, true ) ) {
			return $mode;
		}

		if ( ! empty( $settings['hide_core_notices'] ) ) {
			return 'hide_all';
		}

		return self::get_defaults()['manage_notices_mode'];
	}

	/**
	 * Labels for raw manage_notices_mode values (advanced General settings).
	 *
	 * @return array<string, string>
	 */
	public static function get_manage_notices_mode_labels() {
		return array(
			'show_all'      => __( 'Show all', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'hide_all'      => __( 'Hide all', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'hide_selected' => __( 'Hide selected', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'compact_panel' => __( 'Compact panel', EDMINBOOST_ANM_TEXT_DOMAIN ),
		);
	}

	/**
	 * User-facing display experience presets mapped to manage_notices_mode.
	 *
	 * @return array<string, array{label: string, description: string, mode: string}>
	 */
	public static function get_display_experience_presets() {
		return array(
			'dashboard'    => array(
				'label'       => __( 'Leave notices on the dashboard', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'description' => __( 'WordPress behaves as usual. The plugin stays available for inbox, rules, and reports without changing how notices appear on screen.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'mode'        => 'show_all',
			),
			'clean_inbox'  => array(
				'label'       => __( 'Clean dashboard + inbox / admin bar', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'description' => __( 'Recommended. Hides the notices you configure under Notices, Access & visibility, and Auto-Rules, then collects them in the inbox and admin bar panel.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'mode'        => 'hide_selected',
			),
			'toolbar_only' => array(
				'label'       => __( 'Toolbar only', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'description' => __( 'Hides the dashboard notice area entirely. Open captured notices from the admin bar panel or the plugin inbox.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'mode'        => 'compact_panel',
			),
		);
	}

	/**
	 * Preset slug for a stored manage_notices_mode, or empty when using a custom/advanced mode.
	 *
	 * @param string $mode Manage notices mode slug.
	 * @return string Preset slug or empty string.
	 */
	public static function get_display_experience_preset_for_mode( $mode ) {
		$mode = sanitize_key( $mode );

		foreach ( self::get_display_experience_presets() as $preset => $data ) {
			if ( isset( $data['mode'] ) && $mode === $data['mode'] ) {
				return $preset;
			}
		}

		return '';
	}

	/**
	 * Whether notice interception should run for the current configuration.
	 *
	 * @return bool
	 */
	public static function should_intercept_notices() {
		if ( ! self::is_enabled() ) {
			return false;
		}

		return 'show_all' !== self::get_manage_notices_mode();
	}

	/**
	 * Whether dashboard notice output should be hidden via CSS.
	 *
	 * @return bool
	 */
	public static function should_hide_dashboard_notices() {
		if ( ! self::is_enabled() ) {
			return false;
		}

		$mode = self::get_manage_notices_mode();

		return in_array( $mode, array( 'hide_all', 'compact_panel' ), true );
	}

	/**
	 * Whether the admin bar notice panel should register for the current user.
	 *
	 * @return bool
	 */
	public static function should_show_admin_bar_notice_panel() {
		if ( ! self::is_enabled() ) {
			return false;
		}

		$settings = self::get();
		$panel    = wp_parse_args(
			$settings['notice_panel'] ?? array(),
			self::get_defaults()['notice_panel']
		);

		return ! empty( $panel['enabled'] );
	}

	/**
	 * Whether per-user notice hiding rules apply for a user.
	 *
	 * @param int|null $user_id User ID or null for current user.
	 * @return bool
	 */
	public static function should_apply_notice_hiding_for_user( $user_id = null ) {
		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		$user_id = absint( $user_id );
		if ( $user_id < 1 ) {
			return false;
		}

		if ( ! self::should_display_dashboard_notifications_for_user( $user_id ) ) {
			return true;
		}

		$settings = self::get();
		$hiding   = $settings['hiding_notifications'] ?? self::get_defaults()['hiding_notifications'];
		$mode     = $hiding['mode'] ?? 'all';
		$users    = array_map( 'intval', $hiding['users'] ?? array() );
		$in_list  = in_array( $user_id, $users, true );

		if ( 'only' === $mode ) {
			return $in_list;
		}

		if ( 'except' === $mode ) {
			return ! $in_list;
		}

		return true;
	}

	/**
	 * Selected users for the hiding notifications pickers (id + label).
	 *
	 * @return array<int, array{id: int, label: string}>
	 */
	public static function get_hiding_notification_user_choices() {
		$settings = self::get();
		$user_ids = $settings['hiding_notifications']['users'] ?? array();
		$choices  = array();

		foreach ( $user_ids as $user_id ) {
			$user_id = absint( $user_id );
			if ( $user_id < 1 ) {
				continue;
			}

			$user = get_userdata( $user_id );
			if ( ! $user ) {
				continue;
			}

			$choices[] = array(
				'id'    => $user_id,
				'label' => sprintf(
					/* translators: 1: display name, 2: login */
					__( '%1$s (%2$s)', EDMINBOOST_ANM_TEXT_DOMAIN ),
					$user->display_name,
					$user->user_login
				),
			);
		}

		return $choices;
	}

	/**
	 * Parsed CSS ignore selector list for non-standard notice handling.
	 *
	 * @return string[]
	 */
	public static function get_css_ignore_selectors() {
		$settings  = self::get();
		$raw       = $settings['non_standard_admin_notices']['css_ignore_selectors'] ?? '';
		$selectors = array_map( 'trim', explode( ',', $raw ) );
		$selectors = array_filter(
			$selectors,
			static function ( $selector ) {
				return '' !== $selector;
			}
		);

		return array_values( $selectors );
	}

	/**
	 * Default notice panel color palette.
	 *
	 * @return array<string, string>
	 */
	public static function get_notice_panel_color_defaults() {
		return array(
			'badge'        => '#d63638',
			'badge_text'   => '#ffffff',
			'accent'       => '#2271b1',
			'accent_hover' => '#135e96',
			'bell_active'  => '#72aee6',
		);
	}

	/**
	 * Admin bar panel style choices (slug => label).
	 *
	 * @return array<string, string>
	 */
	public static function get_notice_panel_styles() {
		return array(
			'link'              => __( 'Text link with count', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'bell_link'         => __( 'Bell icon (opens inbox)', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'dropdown'          => __( 'Notification dropdown — detailed cards', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'dropdown_compact'  => __( 'Notification dropdown — compact list', EDMINBOOST_ANM_TEXT_DOMAIN ),
		);
	}

	/**
	 * Allowed notice panel style slugs.
	 *
	 * @return string[]
	 */
	public static function get_notice_panel_style_slugs() {
		return array_keys( self::get_notice_panel_styles() );
	}

	/**
	 * Admin bar panel position choices (slug => label).
	 *
	 * @return array<string, string>
	 */
	public static function get_notice_panel_positions() {
		return array(
			'default'             => __( 'Default admin bar slot', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'after_site_name'     => __( 'Left bar, after site name', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'after_updates'       => __( 'Left bar, after updates', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'top_secondary_start' => __( 'Top-right area, first item', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'before_profile'      => __( 'Top right, before “Howdy” profile menu', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'after_profile'       => __( 'Top right, after “Howdy” profile menu', EDMINBOOST_ANM_TEXT_DOMAIN ),
		);
	}

	/**
	 * Allowed notice panel position slugs.
	 *
	 * @return string[]
	 */
	public static function get_notice_panel_position_slugs() {
		return array_keys( self::get_notice_panel_positions() );
	}

	/**
	 * Admin bar hook priority and parent group for a panel position.
	 *
	 * @param string $position Panel position slug.
	 * @return array{hook_priority: int, parent: string}
	 */
	public static function get_notice_panel_admin_bar_placement( $position ) {
		$position   = sanitize_key( $position );
		$placements = array(
			'default'             => array(
				'hook_priority' => 100,
				'parent'        => '',
			),
			'after_site_name'     => array(
				'hook_priority' => 31,
				'parent'        => '',
			),
			'after_updates'       => array(
				'hook_priority' => 51,
				'parent'        => '',
			),
			'top_secondary_start' => array(
				'hook_priority' => 201,
				'parent'        => 'top-secondary',
			),
			'before_profile'      => array(
				'hook_priority' => 9990,
				'parent'        => 'top-secondary',
			),
			'after_profile'       => array(
				'hook_priority' => 9993,
				'parent'        => 'top-secondary',
			),
		);

		if ( isset( $placements[ $position ] ) ) {
			return $placements[ $position ];
		}

		return $placements['default'];
	}

	/**
	 * Preview mock bar layout key for settings UI (matches admin JS).
	 *
	 * @param string $position Panel position slug.
	 * @return string
	 */
	public static function get_notice_panel_preview_layout( $position ) {
		$position = sanitize_key( $position );
		$layouts  = array(
			'default'             => 'left',
			'after_site_name'     => 'left',
			'after_updates'       => 'left',
			'top_secondary_start' => 'right',
			'before_profile'      => 'profile_before',
			'after_profile'       => 'profile_after',
		);

		return $layouts[ $position ] ?? 'right';
	}

	/**
	 * Whether the position uses Facebook-style bell styling beside Howdy.
	 *
	 * @param string $position Panel position slug.
	 * @return bool
	 */
	public static function notice_panel_position_uses_facebook_slot( $position ) {
		return 'before_profile' === sanitize_key( $position );
	}

	/**
	 * Whether the panel node is placed on the left (primary) admin bar group.
	 *
	 * @param string $position Panel position slug.
	 * @return bool
	 */
	public static function notice_panel_position_uses_left_admin_bar( $position ) {
		$position = sanitize_key( $position );

		return in_array( $position, array( 'default', 'after_site_name', 'after_updates' ), true );
	}

	/**
	 * Bell unread indicator choices (slug => label).
	 *
	 * @return array<string, string>
	 */
	public static function get_notice_panel_badge_styles() {
		return array(
			'count'        => __( 'Number badge', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'count_9plus'  => __( 'Number badge (9+ cap)', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'count_99plus' => __( 'Number badge (99+ cap)', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'dot'          => __( 'Dot only (no count)', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'dot_pulse'    => __( 'Pulsing dot (no count)', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'none'         => __( 'Hidden (no visual indicator)', EDMINBOOST_ANM_TEXT_DOMAIN ),
		);
	}

	/**
	 * Allowed notice panel badge style slugs.
	 *
	 * @return string[]
	 */
	public static function get_notice_panel_badge_style_slugs() {
		return array_keys( self::get_notice_panel_badge_styles() );
	}

	/**
	 * Visible numeric text for the bell badge (respects cap styles).
	 *
	 * @param int    $count       Active notice count.
	 * @param string $badge_style Badge style slug.
	 * @return string
	 */
	public static function format_notice_panel_badge_display_count( $count, $badge_style ) {
		$count = max( 0, (int) $count );
		if ( $count <= 0 ) {
			return '0';
		}

		$badge_style = sanitize_key( $badge_style );

		if ( 'count_9plus' === $badge_style && $count > 9 ) {
			return '9+';
		}

		if ( 'count_99plus' === $badge_style && $count > 99 ) {
			return '99+';
		}

		return (string) $count;
	}

	/**
	 * Whether a badge style uses a dot instead of a numeric pill.
	 *
	 * @param string $badge_style Badge style slug.
	 * @return bool
	 */
	public static function notice_panel_badge_style_uses_dot( $badge_style ) {
		return in_array( sanitize_key( $badge_style ), array( 'dot', 'dot_pulse' ), true );
	}

	/**
	 * Whether a badge style shows no visual indicator on the bell.
	 *
	 * @param string $badge_style Badge style slug.
	 * @return bool
	 */
	public static function notice_panel_badge_style_is_hidden( $badge_style ) {
		return 'none' === sanitize_key( $badge_style );
	}

	/**
	 * Whether a panel style opens an in-bar dropdown panel.
	 *
	 * @param string $style Panel style slug.
	 * @return bool
	 */
	public static function notice_panel_style_is_dropdown( $style ) {
		return in_array( sanitize_key( $style ), array( 'dropdown', 'dropdown_compact' ), true );
	}

	/**
	 * Whether a panel style uses the bell trigger (badge colors apply).
	 *
	 * @param string $style    Panel style slug.
	 * @param string $position Panel position slug.
	 * @return bool
	 */
	public static function notice_panel_style_uses_bell( $style, $position = 'default' ) {
		$style = sanitize_key( $style );

		if ( in_array( $style, array( 'bell_link', 'dropdown', 'dropdown_compact' ), true ) ) {
			return true;
		}

		return 'link' === $style && self::notice_panel_position_uses_facebook_slot( $position );
	}

	/**
	 * Merge notice panel colors with defaults.
	 *
	 * @param array $panel Notice panel settings.
	 * @return array<string, string>
	 */
	public static function get_notice_panel_colors( $panel ) {
		$colors = isset( $panel['colors'] ) && is_array( $panel['colors'] ) ? $panel['colors'] : array();

		return wp_parse_args( $colors, self::get_notice_panel_color_defaults() );
	}

	/**
	 * Sanitize notice panel color settings.
	 *
	 * @param array $input Raw color input.
	 * @return array<string, string>
	 */
	public static function sanitize_notice_panel_colors( $input ) {
		$defaults = self::get_notice_panel_color_defaults();
		$output   = array();

		foreach ( $defaults as $key => $default ) {
			$output[ $key ] = self::sanitize_hex_color_or_default( $input[ $key ] ?? '', $default );
		}

		return $output;
	}

	/**
	 * Sanitize a hex color value with fallback.
	 *
	 * @param string $value   Raw color.
	 * @param string $default Default hex color.
	 * @return string
	 */
	private static function sanitize_hex_color_or_default( $value, $default ) {
		$color = sanitize_hex_color( $value );

		return $color ? $color : $default;
	}

	/**
	 * Whether blocking plugin ad / welcome redirects is enabled.
	 *
	 * @return bool
	 */
	public static function is_block_ad_redirects_enabled() {
		if ( ! self::is_enabled() ) {
			return false;
		}

		$settings = self::get();
		$block    = $settings['block_ad_redirects'] ?? self::get_defaults()['block_ad_redirects'];

		return ! empty( $block['enabled'] );
	}

	/**
	 * Normalized blocked redirect URL patterns for matching.
	 *
	 * @return string[]
	 */
	public static function get_block_ad_redirect_patterns() {
		if ( ! self::is_block_ad_redirects_enabled() ) {
			return array();
		}

		$settings = self::get();
		$urls     = $settings['block_ad_redirects']['urls'] ?? array();
		$patterns = array();

		foreach ( $urls as $url ) {
			$normalized = EDMINBOOST_ANM_Ad_Redirects::normalize_url_for_match( $url );
			if ( '' !== $normalized ) {
				$patterns[] = $normalized;
			}
		}

		return array_values( array_unique( $patterns ) );
	}

	/**
	 * Sanitize webhook settings from Settings API input.
	 *
	 * @param array $input    Raw webhook settings.
	 * @param array $defaults Webhook defaults from get_defaults().
	 * @return array
	 */
	public static function sanitize_webhook_settings( array $input, array $defaults ) {
		return array(
			'enabled' => ! empty( $input['enabled'] ),
			'url'     => esc_url_raw( $input['url'] ?? '' ),
			'message' => sanitize_textarea_field( $input['message'] ?? '' ),
		);
	}

	/**
	 * Sanitize digest settings from Settings API or preview AJAX input.
	 *
	 * @param array $input    Raw digest settings.
	 * @param array $defaults Digest defaults from get_defaults().
	 * @return array
	 */
	public static function sanitize_digest_settings( array $input, array $defaults ) {
		$recent_count = isset( $input['recent_notices_count'] )
			? absint( $input['recent_notices_count'] )
			: (int) ( $defaults['recent_notices_count'] ?? 10 );

		return array(
			'enabled'                => ! empty( $input['enabled'] ),
			'recipients'             => sanitize_text_field( $input['recipients'] ?? $defaults['recipients'] ),
			'send_weekday'           => EDMINBOOST_ANM_Digest::sanitize_send_weekday( $input['send_weekday'] ?? $defaults['send_weekday'] ),
			'subject'                => sanitize_text_field( $input['subject'] ?? '' ),
			'intro'                  => sanitize_textarea_field( $input['intro'] ?? '' ),
			'include_stats'          => ! empty( $input['include_stats'] ),
			'include_recent_notices' => ! empty( $input['include_recent_notices'] ),
			'recent_notices_count'   => max( 1, min( 25, $recent_count ) ),
			'footer'                 => sanitize_textarea_field( $input['footer'] ?? '' ),
		);
	}

	/**
	 * Sanitize blocked redirect URL list from settings input.
	 *
	 * @param string|array $input Raw URL list.
	 * @return string[]
	 */
	public static function sanitize_block_ad_redirect_urls( $input ) {
		if ( is_string( $input ) ) {
			$lines = preg_split( '/\r\n|\r|\n/', $input );
		} elseif ( is_array( $input ) ) {
			$lines = $input;
		} else {
			return array();
		}

		$urls = array();
		$seen = array();

		foreach ( $lines as $line ) {
			$url = trim( sanitize_text_field( wp_unslash( (string) $line ) ) );

			if ( '' === $url ) {
				continue;
			}

			$key = EDMINBOOST_ANM_Ad_Redirects::normalize_url_for_match( $url );

			if ( '' === $key || isset( $seen[ $key ] ) ) {
				continue;
			}

			$seen[ $key ] = true;
			$urls[]       = $url;
		}

		return $urls;
	}

	/**
	 * Sanitize custom upsell blacklist phrases.
	 *
	 * @param string|array $input Raw phrase list (newline string or array).
	 * @return array
	 */
	public static function sanitize_custom_phrases( $input ) {
		return self::sanitize_word_list( $input, false );
	}

	/**
	 * Sanitize a comma- or newline-separated word list.
	 *
	 * @param string|array $input       Raw word list.
	 * @param bool         $split_commas When true, split on commas as well as newlines.
	 * @return array
	 */
	public static function sanitize_word_list( $input, $split_commas = true ) {
		if ( is_array( $input ) ) {
			$lines = $input;
		} elseif ( is_string( $input ) ) {
			if ( $split_commas ) {
				$lines = preg_split( '/\s*,\s*|\r\n|\r|\n/', $input );
			} else {
				$lines = preg_split( '/\r\n|\r|\n/', $input );
			}
		} else {
			return array();
		}

		$words = array();
		$seen  = array();

		foreach ( $lines as $line ) {
			$word = trim( sanitize_text_field( $line ) );

			if ( '' === $word ) {
				continue;
			}

			$key = strtolower( $word );

			if ( isset( $seen[ $key ] ) ) {
				continue;
			}

			$seen[ $key ] = true;
			$words[]      = $word;
		}

		return $words;
	}

	/**
	 * Whether notice text contains any configured whitelist word.
	 *
	 * @param string $message Notice plain text.
	 * @return bool
	 */
	public static function notice_matches_whitelist( $message ) {
		return self::notice_contains_any_word(
			$message,
			self::get()['word_filters']['whitelist_words'] ?? array()
		);
	}

	/**
	 * Whether notice text contains any configured spam word.
	 *
	 * @param string $message Notice plain text.
	 * @return bool
	 */
	public static function notice_matches_spam_words( $message ) {
		return self::notice_contains_any_word(
			$message,
			self::get()['word_filters']['spam_words'] ?? array()
		);
	}

	/**
	 * Case-insensitive substring check against a word list.
	 *
	 * @param string $message Notice plain text.
	 * @param array  $words   Word list.
	 * @return bool
	 */
	private static function notice_contains_any_word( $message, $words ) {
		if ( empty( $words ) || ! is_array( $words ) ) {
			return false;
		}

		$haystack = strtolower( wp_strip_all_tags( (string) $message ) );

		if ( '' === $haystack ) {
			return false;
		}

		foreach ( $words as $word ) {
			$word = trim( (string) $word );

			if ( '' === $word ) {
				continue;
			}

			if ( false !== strpos( $haystack, strtolower( $word ) ) ) {
				return true;
			}
		}

		return false;
	}
}
