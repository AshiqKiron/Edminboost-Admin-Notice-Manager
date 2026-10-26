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
		if ( ! EDMINBOOST_ANM_Settings::is_enabled() ) {
			return;
		}

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
		$message = wp_strip_all_tags( $html );

		if ( '' === trim( $message ) ) {
			return array( 'suppress' => false );
		}

		if ( self::is_protected_notice( $html, $message ) ) {
			return array( 'suppress' => false );
		}

		$source = ! empty( $meta['source_plugin'] )
			? sanitize_text_field( $meta['source_plugin'] )
			: self::detect_source_plugin();

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

		if ( ! $match ) {
			return array( 'suppress' => false );
		}

		$type = $match['type'] ?? EDMINBOOST_ANM_Rules::classify_type( $message );

		if ( current_user_can( EDMINBOOST_ANM_Settings::CAPABILITY ) ) {
			$notice = EDMINBOOST_ANM_Inbox::add(
				array(
					'message'       => $message,
					'source_plugin' => $source,
					'type'          => $type,
				)
			);

			if ( $notice ) {
				EDMINBOOST_ANM_Stats::record_intercept( $source, $message, $type );
				EDMINBOOST_ANM_Webhook::maybe_send( $notice );
			}
		}

		return array(
			'suppress' => true,
			'match'    => $match,
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
	 * Enqueue JS interceptor on all admin pages.
	 *
	 * @return void
	 */
	public function enqueue_interceptor_script() {
		if ( ! is_admin() || ! EDMINBOOST_ANM_Settings::is_enabled() ) {
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
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'edminboost_anm_admin' ),
			)
		);
	}
}
