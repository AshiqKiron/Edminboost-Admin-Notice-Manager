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
	 * Register hooks when the feature is active.
	 *
	 * @return void
	 */
	public function register_hooks() {
		if ( ! is_admin() ) {
			return;
		}

		if ( ! EDMINBOOST_ANM_Settings::is_enabled() ) {
			return;
		}

		if ( ! EDMINBOOST_ANM_Settings::is_block_ad_redirects_enabled() ) {
			return;
		}

		add_action( 'admin_init', array( $this, 'maybe_block_current_admin_request' ), 0 );
		add_filter( 'wp_redirect', array( $this, 'filter_wp_redirect' ), 10, 2 );
	}

	/**
	 * Redirect away when the current admin request matches a blocked URL pattern.
	 *
	 * @return void
	 */
	public function maybe_block_current_admin_request() {
		if ( ! $this->current_request_is_blocked() ) {
			return;
		}

		wp_safe_redirect( admin_url() );
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
		if ( ! is_string( $location ) || '' === $location ) {
			return $location;
		}

		if ( $this->url_matches_blocklist( $location ) ) {
			return admin_url();
		}

		return $location;
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

		return $this->url_matches_blocklist( $request_uri );
	}

	/**
	 * Check a URL or path against configured blocked patterns.
	 *
	 * @param string $url URL or path.
	 * @return bool
	 */
	private function url_matches_blocklist( $url ) {
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

		return false;
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
