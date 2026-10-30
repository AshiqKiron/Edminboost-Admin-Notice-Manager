<?php
/**
 * Block unwanted admin redirects (post-install / upsell pages).
 *
 * @package EdminBoost\AdminNoticeManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ad redirect blocker.
 */
class EDMINBOOST_ANM_Ad_Redirects {

	/**
	 * Whether activation storage filters were registered.
	 *
	 * @var bool
	 */
	private static $activation_storage_filters_registered = false;

	/**
	 * Register hooks when the feature is active.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'admin_init', array( $this, 'remove_blocked_plugin_activation_hooks' ), 0 );
		add_action( 'admin_init', array( $this, 'maybe_block_current_admin_request' ), 1 );
		add_action( 'admin_init', array( $this, 'purge_suppressed_activation_storage' ), 1 );
		add_action( 'activate_plugin', array( $this, 'purge_suppressed_activation_storage' ), 1000 );
		add_action( 'activated_plugin', array( $this, 'purge_suppressed_activation_storage' ), 1000 );
		add_filter( 'wp_redirect', array( $this, 'filter_wp_redirect' ), PHP_INT_MAX - 1, 2 );

		$this->register_activation_storage_filters();
	}

	/**
	 * Register option/transient filters that hide activation flags when their redirect is blocked.
	 *
	 * @return void
	 */
	private function register_activation_storage_filters() {
		if ( self::$activation_storage_filters_registered ) {
			return;
		}

		self::$activation_storage_filters_registered = true;

		foreach ( self::get_activation_redirect_suppressors() as $suppressor ) {
			if ( ! empty( $suppressor['option'] ) ) {
				$option = (string) $suppressor['option'];
				add_filter(
					'option_' . $option,
					function ( $value ) use ( $suppressor ) {
						return $this->filter_suppressed_activation_storage( $value, $suppressor );
					},
					10,
					1
				);
			}

			if ( ! empty( $suppressor['transient'] ) ) {
				$transient = (string) $suppressor['transient'];
				add_filter(
					'transient_' . $transient,
					function ( $value ) use ( $suppressor ) {
						return $this->filter_suppressed_activation_storage( $value, $suppressor );
					},
					10,
					1
				);
			}
		}
	}

	/**
	 * Redirect away when the current admin request matches a blocked URL pattern.
	 *
	 * @return void
	 */
	public function maybe_block_current_admin_request() {
		if ( ! EDMINBOOST_ANM_Settings::is_block_ad_redirects_enabled() ) {
			return;
		}

		if ( ! $this->current_request_is_blocked() ) {
			return;
		}

		wp_safe_redirect( $this->get_blocked_redirect_replacement() );
		exit;
	}

	/**
	 * Prevent wp_redirect from sending users to blocked destinations.
	 *
	 * @param string $location Redirect URL.
	 * @param int    $status   HTTP status code.
	 * @return string
	 */
	public function filter_wp_redirect( $location, $status ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		if ( ! EDMINBOOST_ANM_Settings::is_block_ad_redirects_enabled() ) {
			return $location;
		}

		if ( ! is_string( $location ) || '' === $location ) {
			return $location;
		}

		if ( ! $this->is_local_admin_redirect_location( $location ) ) {
			return $location;
		}

		if ( $this->url_is_blocklisted( $location ) ) {
			return $this->get_blocked_redirect_replacement();
		}

		return $location;
	}

	/**
	 * Remove global admin_init callbacks that send users to blocked welcome URLs.
	 *
	 * @return void
	 */
	public function remove_blocked_plugin_activation_hooks() {
		if ( ! EDMINBOOST_ANM_Settings::is_block_ad_redirects_enabled() ) {
			return;
		}

		foreach ( self::get_activation_redirect_suppressors() as $suppressor ) {
			if ( empty( $suppressor['hooks'] ) || ! is_array( $suppressor['hooks'] ) ) {
				continue;
			}

			if ( ! $this->suppressor_targets_blocklisted_redirect( $suppressor ) ) {
				continue;
			}

			foreach ( $suppressor['hooks'] as $hook ) {
				if ( empty( $hook['callback'] ) || empty( $hook['tag'] ) ) {
					continue;
				}

				remove_action(
					(string) $hook['tag'],
					$hook['callback'],
					isset( $hook['priority'] ) ? (int) $hook['priority'] : 10
				);
			}
		}
	}

	/**
	 * Delete activation options/transients when their redirect destination is blocked.
	 *
	 * @return void
	 */
	public function purge_suppressed_activation_storage() {
		if ( ! EDMINBOOST_ANM_Settings::is_block_ad_redirects_enabled() ) {
			return;
		}

		foreach ( self::get_activation_redirect_suppressors() as $suppressor ) {
			if ( ! $this->suppressor_targets_blocklisted_redirect( $suppressor ) ) {
				continue;
			}

			if ( ! empty( $suppressor['option'] ) ) {
				$option = (string) $suppressor['option'];
				$stored = get_option( $option, false );

				if ( false === $stored ) {
					continue;
				}

				if ( array_key_exists( 'value', $suppressor ) && null !== $suppressor['value'] ) {
					if ( (string) $stored !== (string) $suppressor['value'] ) {
						continue;
					}
				}

				delete_option( $option );
			}

			if ( ! empty( $suppressor['transient'] ) ) {
				delete_transient( (string) $suppressor['transient'] );
			}
		}
	}

	/**
	 * Mask activation storage so third-party plugins skip post-activation redirects.
	 *
	 * @param mixed $value     Stored option/transient value.
	 * @param array $suppressor Suppressor definition.
	 * @return mixed
	 */
	private function filter_suppressed_activation_storage( $value, array $suppressor ) {
		if ( ! EDMINBOOST_ANM_Settings::is_block_ad_redirects_enabled() ) {
			return $value;
		}

		if ( false === $value || null === $value || '' === $value ) {
			return $value;
		}

		if ( ! $this->suppressor_targets_blocklisted_redirect( $suppressor ) ) {
			return $value;
		}

		if ( array_key_exists( 'value', $suppressor ) && null !== $suppressor['value'] ) {
			if ( (string) $value !== (string) $suppressor['value'] ) {
				return $value;
			}
		}

		return false;
	}

	/**
	 * Known plugin activation flags and redirect targets (extensible via filter).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_activation_redirect_suppressors() {
		$items = array(
			array(
				'option'    => 'Restrict_Content_Plugin_Activated',
				'value'     => 'restrict-content',
				'redirects' => array( admin_url( 'admin.php?page=restrict-content-welcome' ) ),
				'hooks'     => array(
					array(
						'tag'      => 'admin_init',
						'callback' => 'restrict_content_plugin_activation_redirect',
						'priority' => 10,
					),
					array(
						'tag'      => 'admin_init',
						'callback' => 'restrict_content_plugin_activation',
						'priority' => 10,
					),
				),
			),
			array(
				'option'    => 'fluent_cart_do_activation_redirect',
				'redirects' => array( admin_url( 'admin.php?page=fluent-cart#/onboarding' ) ),
			),
			array(
				'transient' => 'surecart_activation_redirect',
				'redirects' => array(
					admin_url( 'admin.php?page=sc-getting-started' ),
					admin_url( 'admin.php?page=sc-setup-guide' ),
				),
			),
		);

		/**
		 * Activation redirect flags to suppress when the redirect URL matches the blocklist.
		 *
		 * @param array<int, array<string, mixed>> $items Suppressor definitions.
		 */
		return apply_filters( 'edminboost_anm_block_ad_redirect_activation_flags', $items );
	}

	/**
	 * Whether a suppressor's redirect URL(s) match the configured blocklist.
	 *
	 * @param array $suppressor Suppressor definition.
	 * @return bool
	 */
	private function suppressor_targets_blocklisted_redirect( array $suppressor ) {
		$redirects = array();

		if ( ! empty( $suppressor['redirects'] ) && is_array( $suppressor['redirects'] ) ) {
			$redirects = $suppressor['redirects'];
		} elseif ( ! empty( $suppressor['redirect'] ) ) {
			$redirects = array( $suppressor['redirect'] );
		}

		foreach ( $redirects as $redirect ) {
			if ( $this->url_is_blocklisted( (string) $redirect ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Where to send the user when a blocked redirect is intercepted (prefer plugins.php).
	 *
	 * @return string
	 */
	private function get_blocked_redirect_replacement() {
		$referer = wp_get_referer();

		if ( is_string( $referer ) && '' !== $referer ) {
			$validated = wp_validate_redirect( $referer, false );

			if ( false !== $validated && $this->request_uri_contains_plugins_screen( $validated ) ) {
				return remove_query_arg( array( 'activate', 'activated', 'error', 'charsout' ), $validated );
			}
		}

		if ( ! empty( $_SERVER['REQUEST_URI'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$request_uri = wp_unslash( $_SERVER['REQUEST_URI'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			if ( $this->request_uri_contains_plugins_screen( $request_uri ) ) {
				return admin_url( 'plugins.php' );
			}
		}

		return admin_url( 'plugins.php' );
	}

	/**
	 * Whether a URL/path points at the plugins admin screen.
	 *
	 * @param string $url URL or path.
	 * @return bool
	 */
	private function request_uri_contains_plugins_screen( $url ) {
		$normalized = self::normalize_url_for_match( $url );

		return false !== strpos( $normalized, 'plugins.php' );
	}

	/**
	 * Whether a redirect target points at the local wp-admin (not an external URL).
	 *
	 * @param string $location Redirect URL.
	 * @return bool
	 */
	private function is_local_admin_redirect_location( $location ) {
		$normalized = self::normalize_url_for_match( $location );

		if ( '' === $normalized ) {
			return false;
		}

		return false !== strpos( $normalized, 'wp-admin/' ) || false !== strpos( $normalized, 'admin.php' );
	}

	/**
	 * Whether the current admin request URI matches the blocklist.
	 *
	 * @return bool
	 */
	private function current_request_is_blocked() {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return false;
		}

		$request_uri = wp_unslash( $_SERVER['REQUEST_URI'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		return $this->url_is_blocklisted( $request_uri );
	}

	/**
	 * Check a URL or path against configured blocked patterns (substring + admin page slug).
	 *
	 * @param string $url URL or path.
	 * @return bool
	 */
	private function url_is_blocklisted( $url ) {
		$patterns = EDMINBOOST_ANM_Settings::get_block_ad_redirect_patterns();

		if ( empty( $patterns ) ) {
			return false;
		}

		$haystack = self::normalize_url_for_match( $url );

		if ( '' === $haystack ) {
			return false;
		}

		foreach ( $patterns as $pattern ) {
			if ( '' === $pattern ) {
				continue;
			}

			if ( false !== strpos( $haystack, $pattern ) ) {
				return true;
			}
		}

		foreach ( $this->get_blocklist_page_slugs( $patterns ) as $slug ) {
			if ( false !== strpos( $haystack, 'page=' . $slug ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Extract `page=` slugs from saved blocklist patterns (supports bare slugs too).
	 *
	 * @param string[] $patterns Normalized patterns.
	 * @return string[]
	 */
	private function get_blocklist_page_slugs( array $patterns ) {
		$slugs = array();

		foreach ( $patterns as $pattern ) {
			if ( preg_match( '/(?:^|[?&])page=([a-z0-9_-]+)/i', $pattern, $matches ) ) {
				$slugs[] = strtolower( $matches[1] );
				continue;
			}

			if ( preg_match( '/^[a-z0-9_-]+$/i', $pattern ) ) {
				$slugs[] = strtolower( $pattern );
			}
		}

		return array_values( array_unique( $slugs ) );
	}

	/**
	 * Normalize a URL or admin path for substring matching.
	 *
	 * @param string $url Raw URL or path.
	 * @return string
	 */
	public static function normalize_url_for_match( $url ) {
		$url = trim( (string) $url );

		if ( '' === $url ) {
			return '';
		}

		if ( preg_match( '#^https?://#i', $url ) ) {
			$parsed = wp_parse_url( $url );
			$path   = isset( $parsed['path'] ) ? $parsed['path'] : '';
			$query  = isset( $parsed['query'] ) ? '?' . $parsed['query'] : '';
			$url    = $path . $query;
		}

		$url = rawurldecode( $url );
		$url = str_replace( '\\', '/', $url );
		$url = preg_replace( '#/{2,}#', '/', $url );

		return strtolower( ltrim( $url, '/' ) );
	}
}
