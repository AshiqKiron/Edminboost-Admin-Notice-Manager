<?php
/**
 * WordPress multisite — network policy, shared defaults, and capability helpers.
 *
 * @package EdminBoost\AdminNoticeManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Multisite configuration and merge rules.
 */
class EDMINBOOST_ANM_Multisite {

	/**
	 * Network policy option name (site meta).
	 *
	 * @var string
	 */
	const NETWORK_OPTION = 'edminboost_anm_network';

	/**
	 * Network-wide default settings (same shape as site settings).
	 *
	 * @var string
	 */
	const NETWORK_SETTINGS_OPTION = 'edminboost_anm_network_settings';

	/**
	 * Network-wide auto-rules shared across sites when enabled.
	 *
	 * @var string
	 */
	const NETWORK_RULES_OPTION = 'edminboost_anm_network_rules';

	/**
	 * Settings API group for network policy fields.
	 *
	 * @var string
	 */
	const NETWORK_POLICY_GROUP = 'edminboost_anm_network_policy_group';

	/**
	 * Settings API group for network default settings.
	 *
	 * @var string
	 */
	const NETWORK_SETTINGS_GROUP = 'edminboost_anm_network_settings_group';

	/**
	 * Capability for network admin screens.
	 *
	 * @var string
	 */
	const NETWORK_CAPABILITY = 'manage_network_options';

	/**
	 * Network admin page slug (policy).
	 *
	 * @var string
	 */
	const NETWORK_PAGE_SLUG = EDMINBOOST_ANM_PLUGIN_SLUG . '-network';

	/**
	 * Network admin page slug (default settings).
	 *
	 * @var string
	 */
	const NETWORK_SETTINGS_PAGE_SLUG = EDMINBOOST_ANM_PLUGIN_SLUG . '-network-settings';

	/**
	 * Allowed network policy slugs.
	 *
	 * @var string[]
	 */
	const POLICIES = array( 'per_site', 'network_defaults', 'network_locked' );

	/**
	 * Whether multisite is active.
	 *
	 * @return bool
	 */
	public static function is_active() {
		return is_multisite();
	}

	/**
	 * Register multisite-specific hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		if ( ! self::is_active() ) {
			return;
		}

		self::register_network_option_bridge( self::NETWORK_OPTION );
		self::register_network_option_bridge( self::NETWORK_SETTINGS_OPTION );

		add_filter(
			'default_option_' . self::NETWORK_OPTION,
			static function () {
				return self::get_policy_defaults();
			}
		);
	}

	/**
	 * Wire Settings API reads/writes for a network sitemeta option.
	 *
	 * @param string $option_name Option key stored in sitemeta.
	 * @return void
	 */
	private static function register_network_option_bridge( $option_name ) {
		add_filter(
			'option_' . $option_name,
			static function ( $value ) use ( $option_name ) {
				return self::filter_network_option_from_sitemeta( $value, $option_name );
			},
			10,
			1
		);

		add_filter(
			'pre_update_option_' . $option_name,
			static function ( $value, $old_value ) use ( $option_name ) {
				return self::persist_network_option_to_sitemeta( $value, $old_value, $option_name );
			},
			10,
			2
		);
	}

	/**
	 * Read network option values from sitemeta for Settings API forms.
	 *
	 * @param mixed  $value       Blog option value (unused when sitemeta exists).
	 * @param string $option_name Option key.
	 * @return mixed
	 */
	public static function filter_network_option_from_sitemeta( $value, $option_name ) {
		if ( ! self::is_active() ) {
			return $value;
		}

		$stored = get_site_option( $option_name, false );

		if ( false !== $stored ) {
			return $stored;
		}

		return $value;
	}

	/**
	 * Persist Settings API saves to sitemeta and skip per-blog storage.
	 *
	 * @param mixed  $value       Sanitized option value.
	 * @param mixed  $old_value   Previous blog option value.
	 * @param string $option_name Option key.
	 * @return mixed
	 */
	public static function persist_network_option_to_sitemeta( $value, $old_value, $option_name ) {
		if ( ! current_user_can( self::NETWORK_CAPABILITY ) ) {
			return $old_value;
		}

		if ( self::NETWORK_OPTION === $option_name ) {
			$value = self::normalize_policy_option( is_array( $value ) ? $value : array() );
		} elseif ( ! is_array( $value ) ) {
			$value = array();
		}

		update_site_option( $option_name, $value );

		if ( false !== get_option( $option_name, false ) ) {
			delete_option( $option_name );
		}

		return $old_value;
	}

	/**
	 * Default network policy option values.
	 *
	 * @return array
	 */
	public static function get_policy_defaults() {
		return array(
			'policy'               => 'per_site',
			'allow_site_settings'  => true,
			'allow_site_rules'     => true,
			'share_network_rules'  => false,
		);
	}

	/**
	 * Raw stored network policy option.
	 *
	 * @return array
	 */
	public static function get_policy_option_raw() {
		if ( ! self::is_active() ) {
			return self::get_policy_defaults();
		}

		$stored = get_site_option( self::NETWORK_OPTION, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return self::normalize_policy_option( $stored );
	}

	/**
	 * Normalize network policy option to the canonical stored shape.
	 *
	 * @param array $data Raw or partial policy values.
	 * @return array
	 */
	public static function normalize_policy_option( $data ) {
		if ( ! is_array( $data ) ) {
			$data = array();
		}

		$defaults = self::get_policy_defaults();
		$merged   = wp_parse_args( $data, $defaults );

		$policy = sanitize_key( $merged['policy'] ?? $defaults['policy'] );
		if ( ! in_array( $policy, self::POLICIES, true ) ) {
			$policy = $defaults['policy'];
		}

		$output = array(
			'policy'              => $policy,
			'allow_site_settings' => ! empty( $merged['allow_site_settings'] ),
			'allow_site_rules'    => ! empty( $merged['allow_site_rules'] ),
			'share_network_rules' => ! empty( $merged['share_network_rules'] ),
		);

		if ( 'per_site' === $policy ) {
			$output['share_network_rules'] = false;
		}

		return $output;
	}

	/**
	 * Current network policy slug.
	 *
	 * @return string
	 */
	public static function get_policy() {
		$policy = sanitize_key( self::get_policy_option_raw()['policy'] ?? 'per_site' );

		if ( ! in_array( $policy, self::POLICIES, true ) ) {
			return 'per_site';
		}

		return $policy;
	}

	/**
	 * Human-readable labels for network policies.
	 *
	 * @return array<string, string>
	 */
	public static function get_policy_labels() {
		return array(
			'per_site'         => __( 'Each site manages its own settings', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'network_defaults' => __( 'Network defaults with optional site overrides', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'network_locked'   => __( 'Network-managed (sites inherit network configuration)', EDMINBOOST_ANM_TEXT_DOMAIN ),
		);
	}

	/**
	 * Whether the network layer applies to site behavior.
	 *
	 * @return bool
	 */
	public static function uses_network_layer() {
		return self::is_active() && 'per_site' !== self::get_policy();
	}

	/**
	 * Whether site administrators may save plugin settings.
	 *
	 * @return bool
	 */
	public static function site_can_edit_settings() {
		if ( ! self::is_active() ) {
			return true;
		}

		if ( 'per_site' === self::get_policy() ) {
			return true;
		}

		if ( 'network_locked' === self::get_policy() ) {
			return ! empty( self::get_policy_option_raw()['allow_site_settings'] );
		}

		return true;
	}

	/**
	 * Whether site administrators may change auto-rules on this site.
	 *
	 * @return bool
	 */
	public static function site_can_edit_rules() {
		if ( ! self::is_active() ) {
			return true;
		}

		if ( 'per_site' === self::get_policy() ) {
			return true;
		}

		if ( self::shares_network_rules() && empty( self::get_policy_option_raw()['allow_site_rules'] ) ) {
			return false;
		}

		if ( 'network_locked' === self::get_policy() && empty( self::get_policy_option_raw()['allow_site_rules'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Whether network auto-rules are merged into each site.
	 *
	 * @return bool
	 */
	public static function shares_network_rules() {
		if ( ! self::uses_network_layer() ) {
			return false;
		}

		return ! empty( self::get_policy_option_raw()['share_network_rules'] );
	}

	/**
	 * Raw network default settings stored in sitemeta.
	 *
	 * @return array
	 */
	public static function get_network_settings_raw() {
		if ( ! self::is_active() ) {
			return array();
		}

		$settings = get_site_option( self::NETWORK_SETTINGS_OPTION, array() );

		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * Raw network rules stored in sitemeta.
	 *
	 * @return array
	 */
	public static function get_network_rules_raw() {
		if ( ! self::is_active() ) {
			return array();
		}

		$rules = get_site_option( self::NETWORK_RULES_OPTION, array() );

		return is_array( $rules ) ? $rules : array();
	}

	/**
	 * Merge network and site setting arrays for effective runtime configuration.
	 *
	 * @param array $network_settings Network layer settings.
	 * @param array $site_settings    Site-specific settings.
	 * @return array
	 */
	public static function merge_settings_layers( $network_settings, $site_settings ) {
		return EDMINBOOST_ANM_Settings::merge_stored_layers( $network_settings, $site_settings );
	}

	/**
	 * Resolve effective auto-rules for the current site.
	 *
	 * @param array $site_rules Rules stored on the site.
	 * @return array
	 */
	public static function merge_rules_layers( $site_rules ) {
		if ( ! self::shares_network_rules() ) {
			return $site_rules;
		}

		$network_rules = self::get_network_rules_raw();

		if ( empty( $network_rules ) ) {
			return $site_rules;
		}

		if ( ! self::site_can_edit_rules() ) {
			return $network_rules;
		}

		return self::merge_rule_lists( $network_rules, $site_rules );
	}

	/**
	 * Merge rule lists without duplicating IDs (network entries win on conflict).
	 *
	 * @param array $primary   Primary rule list.
	 * @param array $secondary Secondary rule list.
	 * @return array
	 */
	private static function merge_rule_lists( $primary, $secondary ) {
		$merged = array();
		$seen   = array();

		foreach ( array_merge( $primary, $secondary ) as $rule ) {
			if ( ! is_array( $rule ) ) {
				continue;
			}

			$id = sanitize_key( $rule['id'] ?? '' );
			if ( '' === $id ) {
				$merged[] = $rule;
				continue;
			}

			if ( isset( $seen[ $id ] ) ) {
				continue;
			}

			$seen[ $id ] = true;
			$merged[]    = $rule;
		}

		return $merged;
	}

	/**
	 * Sanitize network policy option on save.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public static function sanitize_policy( $input ) {
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		if ( ! current_user_can( self::NETWORK_CAPABILITY ) ) {
			return self::get_policy_option_raw();
		}

		$current = self::get_policy_option_raw();

		return self::normalize_policy_option(
			array(
				'policy'              => $input['policy'] ?? $current['policy'],
				'allow_site_settings' => ! empty( $input['allow_site_settings'] ),
				'allow_site_rules'    => ! empty( $input['allow_site_rules'] ),
				'share_network_rules' => ! empty( $input['share_network_rules'] ),
			)
		);
	}

	/**
	 * Seed network options on first network setup.
	 *
	 * @return void
	 */
	public static function seed_network_options() {
		if ( ! self::is_active() ) {
			return;
		}

		require_once EDMINBOOST_ANM_PLUGIN_DIR . 'includes/class-edminboost-anm-settings.php';

		if ( false === get_site_option( self::NETWORK_OPTION, false ) ) {
			add_site_option( self::NETWORK_OPTION, self::get_policy_defaults() );
		}

		if ( false === get_site_option( self::NETWORK_SETTINGS_OPTION, false ) ) {
			add_site_option( self::NETWORK_SETTINGS_OPTION, EDMINBOOST_ANM_Settings::get_defaults() );
		}

		if ( false === get_site_option( self::NETWORK_RULES_OPTION, false ) ) {
			add_site_option( self::NETWORK_RULES_OPTION, array() );
		}
	}

	/**
	 * Activate plugin defaults for the current blog.
	 *
	 * @return void
	 */
	public static function activate_site() {
		EDMINBOOST_ANM_Activator::activate_site();
	}

	/**
	 * Apply network default enabled state to a newly created site when configured.
	 *
	 * @param int $blog_id Blog ID.
	 * @return void
	 */
	public static function maybe_seed_new_site( $blog_id ) {
		if ( ! self::is_active() || ! self::uses_network_layer() ) {
			return;
		}

		$blog_id = absint( $blog_id );
		if ( $blog_id < 1 ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::activate_site();

		$network_settings = self::get_network_settings_raw();
		if ( ! empty( $network_settings ) && is_array( $network_settings ) ) {
			$site_settings = EDMINBOOST_ANM_Settings::get_stored_raw();
			$merged        = self::merge_settings_layers( $network_settings, $site_settings );
			EDMINBOOST_ANM_Settings::update_stored( $merged );
		}

		restore_current_blog();
	}

	/**
	 * URL to the network policy settings screen.
	 *
	 * @return string
	 */
	public static function get_network_admin_url() {
		return network_admin_url( 'admin.php?page=' . self::NETWORK_PAGE_SLUG );
	}

	/**
	 * URL to the network default settings screen.
	 *
	 * @return string
	 */
	public static function get_network_settings_admin_url() {
		return network_admin_url( 'admin.php?page=' . self::NETWORK_SETTINGS_PAGE_SLUG );
	}

	/**
	 * Whether the current request targets a plugin network admin screen.
	 *
	 * @param string|null $hook_suffix Optional hook suffix.
	 * @return bool
	 */
	public static function is_network_plugin_screen( $hook_suffix = null ) {
		if ( ! is_network_admin() ) {
			return false;
		}

		if ( is_string( $hook_suffix ) && '' !== $hook_suffix ) {
			return false !== strpos( $hook_suffix, self::NETWORK_PAGE_SLUG );
		}

		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();

			if ( $screen && false !== strpos( $screen->id, self::NETWORK_PAGE_SLUG ) ) {
				return true;
			}
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only routing check.
		if ( isset( $_GET['page'] ) ) {
			$page = sanitize_key( wp_unslash( $_GET['page'] ) );

			return false !== strpos( $page, self::NETWORK_PAGE_SLUG );
		}

		return false;
	}

	/**
	 * Delete all plugin data network-wide (uninstall).
	 *
	 * @return void
	 */
	public static function uninstall_all_sites() {
		if ( ! self::is_active() ) {
			return;
		}

		delete_site_option( self::NETWORK_OPTION );
		delete_site_option( self::NETWORK_SETTINGS_OPTION );
		delete_site_option( self::NETWORK_RULES_OPTION );

		$sites = get_sites(
			array(
				'number' => 0,
				'fields' => 'ids',
			)
		);

		foreach ( $sites as $blog_id ) {
			switch_to_blog( (int) $blog_id );
			EDMINBOOST_ANM_Settings::delete_site_options();
			restore_current_blog();
		}
	}
}
