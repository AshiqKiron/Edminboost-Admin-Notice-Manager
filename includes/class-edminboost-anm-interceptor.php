<?php
/**
 * Notice interceptor — PHP output buffering and safety guards.
 *
 * @package EdminBoost\AdminNoticeManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Intercepts admin notices via PHP hooks.
 */
class EDMINBOOST_ANM_Interceptor {

	/**
	 * Output buffer depth per hook.
	 *
	 * @var array
	 */
	private static $buffer_depth = array();

	/**
	 * Register interception hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		if ( ! EDMINBOOST_ANM_Settings::should_intercept_notices() ) {
			return;
		}

		if ( ! EDMINBOOST_ANM_Settings::should_apply_notice_hiding_for_user() ) {
			return;
		}

		add_action( 'admin_init', array( $this, 'maybe_disable_update_nags' ) );

		if ( EDMINBOOST_ANM_Admin::is_plugin_screen() ) {
			return;
		}

		$hooks = array( 'admin_notices', 'network_admin_notices', 'user_admin_notices' );

		foreach ( $hooks as $hook ) {
			add_action( $hook, array( $this, 'start_buffer' ), 0 );
			add_action( $hook, array( $this, 'end_buffer' ), PHP_INT_MAX );
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_interceptor_script' ) );
	}

	/**
	 * Start output buffering for a notice hook.
	 *
	 * @return void
	 */
	public function start_buffer() {
		$hook = current_action();
		ob_start();
		self::$buffer_depth[ $hook ] = ob_get_level();
	}

	/**
	 * End buffer, evaluate, and optionally suppress notice output.
	 *
	 * @return void
	 */
	public function end_buffer() {
		$hook = current_action();

		if ( empty( self::$buffer_depth[ $hook ] ) || ob_get_level() < self::$buffer_depth[ $hook ] ) {
			return;
		}

		$html = ob_get_clean();
		unset( self::$buffer_depth[ $hook ] );

		if ( '' === trim( $html ) ) {
			return;
		}

		$result = $this->process_notice( $html );

		if ( ! $result['suppress'] ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- original notice HTML.
			echo $html;
		}
	}

	/**
	 * Process a notice and decide whether to suppress it.
	 *
	 * @param string $html Notice HTML.
	 * @param array  $meta Optional metadata from JS capture.
	 * @return array
	 */
	public function process_notice( $html, $meta = array() ) {
		if ( ! EDMINBOOST_ANM_Settings::should_apply_notice_hiding_for_user() ) {
			return array( 'suppress' => false );
		}

		$settings = EDMINBOOST_ANM_Settings::get();
		$message  = wp_strip_all_tags( $html );

		if ( '' === trim( $message ) ) {
			return array( 'suppress' => false );
		}

		if ( self::is_protected_notice( $html, $message ) ) {
			return array( 'suppress' => false );
		}

		if ( EDMINBOOST_ANM_Settings::notice_matches_whitelist( $message ) ) {
			return array( 'suppress' => false );
		}

		if ( ! empty( $settings['disable_core_update_notifications'] ) && self::is_core_update_notice( $html, $message ) ) {
			return array( 'suppress' => true, 'source' => 'core_update_setting' );
		}

		if ( ! empty( $settings['disable_plugin_update_notifications'] ) && self::is_plugin_update_notice( $html, $message ) ) {
			return array( 'suppress' => true, 'source' => 'plugin_update_setting' );
		}

		$manage_mode = EDMINBOOST_ANM_Settings::get_manage_notices_mode();
		if ( 'hide_all' === $manage_mode ) {
			return array( 'suppress' => true, 'source' => 'hide_all_mode' );
		}

		if ( EDMINBOOST_ANM_Rules::matches_css_ignore_selectors( $html ) ) {
			return array( 'suppress' => false );
		}

		$source = ! empty( $meta['source_plugin'] )
			? sanitize_text_field( $meta['source_plugin'] )
			: self::detect_source_plugin();

		$hash_pref = EDMINBOOST_ANM_Inbox::get_hash_preference(
			EDMINBOOST_ANM_Inbox::generate_hash( $message, $source )
		);

		if ( EDMINBOOST_ANM_Inbox::HASH_PREF_NORMAL === $hash_pref ) {
			return array( 'suppress' => false );
		}

		if ( EDMINBOOST_ANM_Inbox::HASH_PREF_FOREVER === $hash_pref ) {
			return array( 'suppress' => true );
		}

		if ( EDMINBOOST_ANM_Settings::notice_matches_spam_words( $message ) ) {
			return array( 'suppress' => true, 'source' => 'spam_words' );
		}

		$match = EDMINBOOST_ANM_Rules::evaluate( $message, $html );

		if ( ! $match && ! self::should_shield_for_user() ) {
			return array( 'suppress' => false );
		}

		if ( ! $match && self::should_shield_for_user() ) {
			$type = EDMINBOOST_ANM_Rules::classify_type( $message );
			if ( ! in_array( $type, array( 'upsell', 'info' ), true ) ) {
				return array( 'suppress' => false );
			}
			$match = array(
				'source' => 'client_shield',
				'type'   => $type,
				'action' => 'dismiss_only',
			);
		}

		if ( $match ) {
			$type = $match['type'] ?? EDMINBOOST_ANM_Rules::classify_type( $message );

			return self::finalize_intercept( $message, $source, $type, $html, array( 'match' => $match ) );
		}

		$policy = EDMINBOOST_ANM_Rules::resolve_notice_policy( $html );

		if ( 'default' === $policy['mode'] ) {
			return array( 'suppress' => false );
		}

		if ( 'hide' === $policy['mode'] ) {
			return array(
				'suppress' => true,
				'policy'   => $policy,
			);
		}

		return self::finalize_intercept( $message, $source, $policy['type'], $html, array( 'policy' => $policy ) );
	}

	/**
	 * Add notice to inbox (when allowed) and return suppress response.
	 *
	 * @param string $message Notice text.
	 * @param string $source  Source plugin label.
	 * @param string $type    Inbox type slug.
	 * @param string $html    Original notice HTML.
	 * @param array  $extra   Optional extra response keys.
	 * @return array
	 */
	private static function finalize_intercept( $message, $source, $type, $html = '', $extra = array() ) {
		if ( current_user_can( EDMINBOOST_ANM_Settings::CAPABILITY ) ) {
			$notice = EDMINBOOST_ANM_Inbox::add(
				array(
					'message'       => $message,
					'html'          => $html,
					'source_plugin' => $source,
					'type'          => $type,
				)
			);

			if ( $notice ) {
				EDMINBOOST_ANM_Stats::record_intercept( $source, $message, $type );
				EDMINBOOST_ANM_Webhook::maybe_send( $notice );
			}
		}

		return array_merge(
			array(
				'suppress' => true,
			),
			$extra
		);
	}

	/**
	 * Whether current user is shielded by Client Shield mode.
	 *
	 * @return bool
	 */
	public static function should_shield_for_user() {
		$settings = EDMINBOOST_ANM_Settings::get();

		if ( empty( $settings['client_shield']['enabled'] ) ) {
			return false;
		}

		if ( current_user_can( 'manage_options' ) ) {
			return false;
		}

		$user = wp_get_current_user();
		if ( ! $user || empty( $user->roles ) ) {
			return false;
		}

		$shield_roles = $settings['client_shield']['roles'] ?? array();

		return ! empty( array_intersect( $user->roles, $shield_roles ) );
	}

	/**
	 * Check if notice is protected by safety guard.
	 *
	 * @param string $html    Notice HTML.
	 * @param string $message Plain message.
	 * @return bool
	 */
	public static function is_protected_notice( $html, $message ) {
		$settings = EDMINBOOST_ANM_Settings::get();

		if ( empty( $settings['safety_guard']['enabled'] ) ) {
			return false;
		}

		if ( preg_match( '/\b(fatal error|parse error|ssl certificate|security alert|database error)\b/i', $message ) ) {
			return true;
		}

		if ( preg_match( '/class=["\'][^"\']*\b(error|notice-error)\b/i', $html ) ) {
			if ( preg_match( '/\b(fatal|security|ssl|database)\b/i', $message ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Detect source plugin from backtrace.
	 *
	 * @return string
	 */
	/**
	 * Best-effort source label from notice HTML when backtrace is unavailable.
	 *
	 * @param string $html    Notice HTML.
	 * @param string $message Plain message.
	 * @return string
	 */
	public static function guess_source_for_notice_html( $html, $message ) {
		if ( preg_match( '/plugins\.php\?[^"\']*plugin=([^&"\']+)/i', $html, $matches ) ) {
			$slug = sanitize_key( wp_unslash( $matches[1] ) );
			if ( '' !== $slug ) {
				return ucwords( str_replace( array( '-', '_' ), ' ', dirname( $slug ) !== '.' ? dirname( $slug ) : $slug ) );
			}
		}

		if ( preg_match( '/class=["\'][^"\']*\b(?:notice|update-nag)[^"\']*\b([a-z0-9_-]+)-notice\b/i', $html, $matches ) ) {
			$slug = sanitize_key( $matches[1] );
			if ( '' !== $slug && ! in_array( $slug, array( 'notice', 'updated', 'error', 'info', 'warning', 'success' ), true ) ) {
				return ucwords( str_replace( array( '-', '_' ), ' ', $slug ) );
			}
		}

		unset( $message );

		return __( 'Unknown', EDMINBOOST_ANM_TEXT_DOMAIN );
	}

	/**
	 * Detect source plugin from backtrace.
	 *
	 * @return string
	 */
	public static function detect_source_plugin() {
		$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 20 );

		foreach ( $trace as $frame ) {
			if ( empty( $frame['file'] ) ) {
				continue;
			}

			$file = wp_normalize_path( $frame['file'] );

			if ( false !== strpos( $file, WP_PLUGIN_DIR ) ) {
				$relative = str_replace( wp_normalize_path( WP_PLUGIN_DIR ) . '/', '', $file );
				$parts    = explode( '/', $relative );
				if ( ! empty( $parts[0] ) ) {
					$slug = $parts[0];
					if ( function_exists( 'get_plugin_data' ) ) {
						$plugin_file = $slug . '/' . ( is_dir( WP_PLUGIN_DIR . '/' . $slug ) ? self::find_main_plugin_file( $slug ) : '' );
						if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
							$data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file, false, false );
							if ( ! empty( $data['Name'] ) ) {
								return $data['Name'];
							}
						}
					}
					return ucwords( str_replace( array( '-', '_' ), ' ', $slug ) );
				}
			}
		}

		return __( 'WordPress Core', EDMINBOOST_ANM_TEXT_DOMAIN );
	}

	/**
	 * Find main plugin file in a directory.
	 *
	 * @param string $slug Plugin directory slug.
	 * @return string
	 */
	private static function find_main_plugin_file( $slug ) {
		$files = glob( WP_PLUGIN_DIR . '/' . $slug . '/*.php' );
		if ( ! empty( $files[0] ) ) {
			return basename( $files[0] );
		}
		return $slug . '.php';
	}

	/**
	 * Remove core update nag hooks when the setting is enabled.
	 *
	 * @return void
	 */
	public function maybe_disable_update_nags() {
		$settings = EDMINBOOST_ANM_Settings::get();

		if ( empty( $settings['disable_core_update_notifications'] ) ) {
			return;
		}

		remove_action( 'admin_notices', 'update_nag', 3 );
		remove_action( 'network_admin_notices', 'update_nag', 3 );
	}

	/**
	 * Whether HTML looks like a WordPress core update notification.
	 *
	 * @param string $html    Notice HTML.
	 * @param string $message Plain text message.
	 * @return bool
	 */
	public static function is_core_update_notice( $html, $message ) {
		if ( preg_match( '/update-core\.php/i', $html ) ) {
			return true;
		}

		if ( preg_match( '/\bwordpress\b.*\b(update|available)\b/i', $message ) ) {
			return true;
		}

		if ( preg_match( '/class=["\'][^"\']*\bupdate-nag\b/i', $html ) && preg_match( '/\bwordpress\b/i', $message ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Whether HTML looks like a plugin or theme update notification.
	 *
	 * @param string $html    Notice HTML.
	 * @param string $message Plain text message.
	 * @return bool
	 */
	public static function is_plugin_update_notice( $html, $message ) {
		if ( preg_match( '/plugins\.php\?plugin_status=upgrade/i', $html ) ) {
			return true;
		}

		if ( preg_match( '/\b(plugin|plugins|theme|themes)\b.*\b(update|updates|available|new version)\b/i', $message ) ) {
			return true;
		}

		if ( preg_match( '/class=["\'][^"\']*\bupdate-nag\b/i', $html ) && ! preg_match( '/\bwordpress\b/i', $message ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Enqueue JS interceptor on all admin pages.
	 *
	 * @return void
	 */
	public function enqueue_interceptor_script() {
		if ( ! is_admin() || ! EDMINBOOST_ANM_Settings::should_intercept_notices() ) {
			return;
		}

		if ( ! EDMINBOOST_ANM_Settings::should_apply_notice_hiding_for_user() ) {
			return;
		}

		if ( EDMINBOOST_ANM_Admin::is_plugin_screen() ) {
			return;
		}

		wp_enqueue_script(
			'edminboost-anm-interceptor',
			EDMINBOOST_ANM_PLUGIN_URL . 'admin/js/edminboost-anm-interceptor.js',
			array(),
			EDMINBOOST_ANM_VERSION,
			true
		);

		wp_localize_script(
			'edminboost-anm-interceptor',
			'edminboostAnmInterceptor',
			array(
				'ajaxUrl'              => admin_url( 'admin-ajax.php' ),
				'nonce'                => wp_create_nonce( 'edminboost_anm_admin' ),
				'cssIgnoreSelectors'   => EDMINBOOST_ANM_Settings::get_css_ignore_selectors(),
			)
		);
	}
}
