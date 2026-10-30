<?php
/**
 * Analytics and dashboard metrics for intercepted notices.
 *
 * @package EdminBoost\AdminNoticeManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stats tracker.
 */
class EDMINBOOST_ANM_Stats {

	/**
	 * Max intercept feed entries stored in stats.
	 *
	 * @var int
	 */
	const FEED_STORE_LIMIT = 100;

	/**
	 * Max feed rows shown on the dashboard preview.
	 *
	 * @var int
	 */
	const DASHBOARD_FEED_LIMIT = 10;

	/**
	 * Default stats structure.
	 *
	 * @return array
	 */
	public static function get_defaults() {
		return array(
			'monthly_blocked'      => 0,
			'month_key'            => gmdate( 'Y-m' ),
			'plugin_hits'          => array(),
			'feed'                 => array(),
			'preset_phrase_count'  => 0,
		);
	}

	/**
	 * Get merged stats.
	 *
	 * @return array
	 */
	public static function get() {
		$stats = get_option( EDMINBOOST_ANM_Settings::STATS_OPTION, array() );

		if ( ! is_array( $stats ) ) {
			$stats = array();
		}

		$merged = wp_parse_args( $stats, self::get_defaults() );

		if ( $merged['month_key'] !== gmdate( 'Y-m' ) ) {
			$merged['monthly_blocked'] = 0;
			$merged['month_key']       = gmdate( 'Y-m' );
		}

		return $merged;
	}

	/**
	 * Save stats.
	 *
	 * @param array $stats Stats data.
	 * @return bool
	 */
	public static function save( $stats ) {
		return update_option( EDMINBOOST_ANM_Settings::STATS_OPTION, $stats, false );
	}

	/**
	 * Record an intercepted notice.
	 *
	 * @param string $source_plugin Plugin name.
	 * @param string $message       Notice message preview.
	 * @param string $type          Notice type slug.
	 * @return void
	 */
	public static function record_intercept( $source_plugin, $message, $type ) {
		$stats = self::get();

		if ( $stats['month_key'] !== gmdate( 'Y-m' ) ) {
			$stats['monthly_blocked'] = 0;
			$stats['month_key']       = gmdate( 'Y-m' );
		}

		++$stats['monthly_blocked'];

		$plugin_key = sanitize_key( $source_plugin );
		if ( ! isset( $stats['plugin_hits'][ $plugin_key ] ) ) {
			$stats['plugin_hits'][ $plugin_key ] = array(
				'label' => $source_plugin,
				'hits'  => 0,
			);
		}

		++$stats['plugin_hits'][ $plugin_key ]['hits'];

		array_unshift(
			$stats['feed'],
			array(
				'source'  => $source_plugin,
				'message' => wp_html_excerpt( wp_strip_all_tags( $message ), 80, '…' ),
				'time'    => time(),
				'type'    => $type,
			)
		);

		$stats['feed'] = array_slice( $stats['feed'], 0, self::FEED_STORE_LIMIT );

		self::save( $stats );
	}

	/**
	 * Dashboard metrics for the wireframe cards.
	 *
	 * @return array
	 */
	public static function get_dashboard_metrics() {
		$stats  = self::get();
		$rules  = EDMINBOOST_ANM_Rules::get_all();
		$active = 0;

		foreach ( $rules as $rule ) {
			if ( ! empty( $rule['enabled'] ) ) {
				++$active;
			}
		}

		$top_plugin = __( 'None yet', EDMINBOOST_ANM_TEXT_DOMAIN );
		$top_hits   = 0;

		if ( ! empty( $stats['plugin_hits'] ) ) {
			$sorted = $stats['plugin_hits'];
			uasort(
				$sorted,
				static function ( $a, $b ) {
					return ( $b['hits'] ?? 0 ) <=> ( $a['hits'] ?? 0 );
				}
			);
			$first = reset( $sorted );
			if ( $first && ! empty( $first['hits'] ) ) {
				$top_plugin = $first['label'];
				$top_hits   = (int) $first['hits'];
			}
		}

		return array(
			'monthly_blocked'       => (int) $stats['monthly_blocked'],
			'top_spammer'           => $top_plugin,
			'top_spammer_hits'      => $top_hits,
			'active_rules'          => $active,
			'preset_phrase_count'   => EDMINBOOST_ANM_Rules::get_preset_phrase_count(),
			'plugin_hits'           => $stats['plugin_hits'],
			'feed'                  => array_slice( $stats['feed'], 0, self::DASHBOARD_FEED_LIMIT ),
		);
	}

	/**
	 * Top N plugins by hit count.
	 *
	 * @param int $limit Max items.
	 * @return array
	 */
	public static function get_top_plugins( $limit = 5 ) {
		$stats  = self::get();
		$sorted = $stats['plugin_hits'];

		uasort(
			$sorted,
			static function ( $a, $b ) {
				return ( $b['hits'] ?? 0 ) <=> ( $a['hits'] ?? 0 );
			}
		);

		return array_slice( array_values( $sorted ), 0, $limit );
	}
}
