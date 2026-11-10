<?php
/**
 * Notification source registry and per-source channel preferences.
 *
 * @package EdminBoost\AdminNoticeManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds source × channel matrix data and resolves notices to registry IDs.
 */
class EDMINBOOST_ANM_Notification_Sources {

	/**
	 * Channel slugs stored in settings.
	 *
	 * @var string[]
	 */
	const CHANNELS = array( 'admin', 'email', 'webhook' );

	/**
	 * Maximum active plugins listed beyond known commerce integrations.
	 */
	const MAX_OTHER_PLUGINS = 30;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_filter( 'edminboost_anm_notification_source_registry', array( __CLASS__, 'append_active_plugin_sections' ), 20 );
	}

	/**
	 * Channel labels for settings UI.
	 *
	 * @return array<string, string>
	 */
	public static function get_channel_labels() {
		return array(
			'admin'   => __( 'Admin', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'email'   => __( 'Email', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'webhook' => __( 'Webhook', EDMINBOOST_ANM_TEXT_DOMAIN ),
		);
	}

	/**
	 * Base registry (WordPress + conditional WooCommerce / EDD).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_base_registry() {
		$sections = array(
			array(
				'id'       => 'wordpress',
				'label'    => __( 'WordPress', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'children' => array(
					array(
						'id'    => 'wordpress.posts_pages',
						'label' => __( 'Posts & pages (published, edited, etc.)', EDMINBOOST_ANM_TEXT_DOMAIN ),
					),
					array(
						'id'    => 'wordpress.comments',
						'label' => __( 'Comments', EDMINBOOST_ANM_TEXT_DOMAIN ),
					),
					array(
						'id'    => 'wordpress.site_health',
						'label' => __( 'Site health', EDMINBOOST_ANM_TEXT_DOMAIN ),
					),
					array(
						'id'    => 'wordpress.collaboration',
						'label' => __( 'Collaboration', EDMINBOOST_ANM_TEXT_DOMAIN ),
					),
					array(
						'id'    => 'wordpress.updates',
						'label' => __( 'Updates & maintenance', EDMINBOOST_ANM_TEXT_DOMAIN ),
					),
					array(
						'id'    => 'wordpress.security',
						'label' => __( 'Security', EDMINBOOST_ANM_TEXT_DOMAIN ),
					),
				),
			),
		);

		if ( self::is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			$sections[] = array(
				'id'       => 'woocommerce',
				'label'    => __( 'WooCommerce', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'children' => array(
					array(
						'id'    => 'woocommerce.orders',
						'label' => __( 'Orders', EDMINBOOST_ANM_TEXT_DOMAIN ),
					),
					array(
						'id'    => 'woocommerce.payments',
						'label' => __( 'Payments', EDMINBOOST_ANM_TEXT_DOMAIN ),
					),
					array(
						'id'    => 'woocommerce.stock',
						'label' => __( 'Stock', EDMINBOOST_ANM_TEXT_DOMAIN ),
					),
					array(
						'id'    => 'woocommerce.store_health',
						'label' => __( 'Store health', EDMINBOOST_ANM_TEXT_DOMAIN ),
					),
					array(
						'id'    => 'woocommerce.updates',
						'label' => __( 'Updates', EDMINBOOST_ANM_TEXT_DOMAIN ),
					),
					array(
						'id'    => 'woocommerce.tips_offers',
						'label' => __( 'Tips and offers', EDMINBOOST_ANM_TEXT_DOMAIN ),
					),
				),
			);
		}

		if ( self::is_edd_active() ) {
			$sections[] = array(
				'id'       => 'easy_digital_downloads',
				'label'    => __( 'Easy Digital Downloads', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'children' => array(
					array(
						'id'    => 'easy_digital_downloads.orders',
						'label' => __( 'Orders & sales', EDMINBOOST_ANM_TEXT_DOMAIN ),
					),
					array(
						'id'    => 'easy_digital_downloads.payments',
						'label' => __( 'Payments', EDMINBOOST_ANM_TEXT_DOMAIN ),
					),
					array(
						'id'    => 'easy_digital_downloads.extensions',
						'label' => __( 'Extensions', EDMINBOOST_ANM_TEXT_DOMAIN ),
					),
					array(
						'id'    => 'easy_digital_downloads.updates',
						'label' => __( 'Updates', EDMINBOOST_ANM_TEXT_DOMAIN ),
					),
					array(
						'id'    => 'easy_digital_downloads.tips_offers',
						'label' => __( 'Tips and offers', EDMINBOOST_ANM_TEXT_DOMAIN ),
					),
				),
			);
		}

		/**
		 * Filter notification source registry sections (before active plugins are appended).
		 *
		 * @param array $sections Registry sections.
		 */
		return apply_filters( 'edminboost_anm_notification_source_registry_base', $sections );
	}

	/**
	 * Full registry including active plugins.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_registry() {
		$sections = self::get_base_registry();

		/**
		 * Filter the full notification source registry.
		 *
		 * @param array $sections Registry sections.
		 */
		return apply_filters( 'edminboost_anm_notification_source_registry', $sections );
	}

	/**
	 * Append one section per other active plugin.
	 *
	 * @param array $sections Existing sections.
	 * @return array
	 */
	public static function append_active_plugin_sections( $sections ) {
		if ( ! is_array( $sections ) ) {
			$sections = array();
		}

		$skip_slugs = array(
			'edminboost-admin-notice-manager',
			'edminboost-admin-notice-manager-pro',
			'woocommerce',
			'easy-digital-downloads',
		);

		$active = self::get_active_plugin_files();
		$added  = 0;

		foreach ( $active as $plugin_file ) {
			if ( $added >= self::MAX_OTHER_PLUGINS ) {
				break;
			}

			$slug = self::plugin_file_slug( $plugin_file );
			if ( '' === $slug || in_array( $slug, $skip_slugs, true ) ) {
				continue;
			}

			$label = self::get_plugin_name_from_file( $plugin_file );
			$sections[] = array(
				'id'       => 'plugin.' . $slug,
				'label'    => $label,
				'children' => array(
					array(
						'id'    => 'plugin.' . $slug . '.general',
						'label' => __( 'General notices', EDMINBOOST_ANM_TEXT_DOMAIN ),
					),
					array(
						'id'    => 'plugin.' . $slug . '.updates',
						'label' => __( 'Updates', EDMINBOOST_ANM_TEXT_DOMAIN ),
					),
					array(
						'id'    => 'plugin.' . $slug . '.tips_offers',
						'label' => __( 'Tips and offers', EDMINBOOST_ANM_TEXT_DOMAIN ),
					),
				),
			);

			++$added;
		}

		return $sections;
	}

	/**
	 * Flat list of leaf source IDs in the registry.
	 *
	 * @return string[]
	 */
	public static function get_leaf_source_ids() {
		$ids = array();

		foreach ( self::get_registry() as $section ) {
			$children = isset( $section['children'] ) && is_array( $section['children'] ) ? $section['children'] : array();
			foreach ( $children as $child ) {
				if ( empty( $child['id'] ) ) {
					continue;
				}
				$ids[] = sanitize_key( (string) $child['id'] );
			}
		}

		return array_values( array_unique( array_filter( $ids ) ) );
	}

	/**
	 * Sanitize stored channel preferences from Settings API input.
	 *
	 * @param array $input   Posted notification_channels array.
	 * @param array $stored  Previously stored notification_channels.
	 * @return array
	 */
	public static function sanitize_preferences( $input, $stored = array() ) {
		$stored_sources = isset( $stored['sources'] ) && is_array( $stored['sources'] ) ? $stored['sources'] : array();
		$posted_sources = isset( $input['sources'] ) && is_array( $input['sources'] ) ? $input['sources'] : null;

		if ( null === $posted_sources ) {
			return array(
				'sources' => self::sanitize_sources_map( $stored_sources ),
			);
		}

		$leaf_ids = self::get_leaf_source_ids();
		$output   = array();
		$is_pro   = function_exists( 'edminboost_anm_is_pro' ) && edminboost_anm_is_pro();

		foreach ( $leaf_ids as $source_id ) {
			$row = isset( $posted_sources[ $source_id ] ) && is_array( $posted_sources[ $source_id ] )
				? $posted_sources[ $source_id ]
				: array();

			$output[ $source_id ] = array();
			foreach ( self::CHANNELS as $channel ) {
				// Free edition: webhook cells are disabled in the UI (not posted). Preserve stored/default webhook prefs.
				if ( 'webhook' === $channel && ! $is_pro ) {
					if ( array_key_exists( $channel, $row ) ) {
						$output[ $source_id ][ $channel ] = ! empty( $row[ $channel ] );
					} elseif ( isset( $stored_sources[ $source_id ][ $channel ] ) ) {
						$output[ $source_id ][ $channel ] = ! empty( $stored_sources[ $source_id ][ $channel ] );
					} else {
						$output[ $source_id ][ $channel ] = true;
					}
					continue;
				}

				$output[ $source_id ][ $channel ] = ! empty( $row[ $channel ] );
			}
		}

		return array(
			'sources' => $output,
		);
	}

	/**
	 * Normalize a sources map (drop unknown keys).
	 *
	 * @param array $sources Raw sources map.
	 * @return array
	 */
	private static function sanitize_sources_map( $sources ) {
		if ( ! is_array( $sources ) ) {
			return array();
		}

		$allowed = array_flip( self::get_leaf_source_ids() );
		$clean   = array();
		$is_pro  = function_exists( 'edminboost_anm_is_pro' ) && edminboost_anm_is_pro();

		foreach ( $sources as $source_id => $channels ) {
			$source_id = sanitize_key( (string) $source_id );
			if ( ! isset( $allowed[ $source_id ] ) || ! is_array( $channels ) ) {
				continue;
			}
			$clean[ $source_id ] = array();
			foreach ( self::CHANNELS as $channel ) {
				if ( 'webhook' === $channel && ! $is_pro ) {
					$clean[ $source_id ][ $channel ] = array_key_exists( $channel, $channels )
						? ! empty( $channels[ $channel ] )
						: true;
					continue;
				}
				$clean[ $source_id ][ $channel ] = ! empty( $channels[ $channel ] );
			}
		}

		return $clean;
	}

	/**
	 * Merged preferences for all leaf sources (defaults: all channels on).
	 *
	 * @return array<string, array<string, bool>>
	 */
	public static function get_merged_source_preferences() {
		$settings = EDMINBOOST_ANM_Settings::get();
		$stored   = isset( $settings['notification_channels']['sources'] ) && is_array( $settings['notification_channels']['sources'] )
			? $settings['notification_channels']['sources']
			: array();

		$merged = array();
		foreach ( self::get_leaf_source_ids() as $source_id ) {
			$row = isset( $stored[ $source_id ] ) && is_array( $stored[ $source_id ] ) ? $stored[ $source_id ] : array();
			$merged[ $source_id ] = array();
			foreach ( self::CHANNELS as $channel ) {
				$merged[ $source_id ][ $channel ] = array_key_exists( $channel, $row )
					? ! empty( $row[ $channel ] )
					: true;
			}
		}

		return $merged;
	}

	/**
	 * Whether a channel is enabled for a registry source ID.
	 *
	 * @param string $source_id Leaf source ID.
	 * @param string $channel   Channel slug.
	 * @return bool
	 */
	public static function is_channel_enabled_for_source( $source_id, $channel ) {
		$channel   = sanitize_key( $channel );
		$source_id = sanitize_key( $source_id );

		if ( ! in_array( $channel, self::CHANNELS, true ) ) {
			return true;
		}

		// Free: per-source webhook matrix is Pro-only; type-based routing uses Notifications defaults.
		if ( 'webhook' === $channel && function_exists( 'edminboost_anm_is_pro' ) && ! edminboost_anm_is_pro() ) {
			/**
			 * Filter whether a notification channel is enabled for a source ID.
			 *
			 * @param bool   $enabled   Whether enabled.
			 * @param string $source_id Source ID.
			 * @param string $channel   Channel slug.
			 */
			return (bool) apply_filters( 'edminboost_anm_notification_channel_enabled', true, $source_id, $channel );
		}

		$prefs = self::get_merged_source_preferences();
		$enabled = ! empty( $prefs[ $source_id ][ $channel ] );

		/**
		 * Filter whether a notification channel is enabled for a source ID.
		 *
		 * @param bool   $enabled   Whether enabled.
		 * @param string $source_id Source ID.
		 * @param string $channel   Channel slug.
		 */
		return (bool) apply_filters( 'edminboost_anm_notification_channel_enabled', $enabled, $source_id, $channel );
	}

	/**
	 * Whether a notice may use a delivery channel.
	 *
	 * @param array  $notice  Notice row or capture payload.
	 * @param string $channel Channel slug.
	 * @return bool
	 */
	public static function notice_allows_channel( $notice, $channel ) {
		if ( ! is_array( $notice ) ) {
			return true;
		}

		$source_id = self::resolve_source_id_for_notice( $notice );

		return self::is_channel_enabled_for_source( $source_id, $channel );
	}

	/**
	 * Plugin directory slugs treated as ecommerce for the ecommerce display experience.
	 *
	 * @return string[]
	 */
	public static function get_ecommerce_plugin_slugs() {
		$slugs = array(
			'woocommerce',
			'easy-digital-downloads',
			'easy-digital-downloads-pro',
			'surecart',
			'surecart-pro',
			'give',
			'givewp',
			'bigcommerce',
			'shopify',
			'wp-e-commerce',
			'wp-ecommerce',
			'cartflows',
			'fluent-cart',
			'ecwid-shopping-cart',
		);

		/**
		 * Filter plugin slugs treated as ecommerce for display experience and routing.
		 *
		 * @param string[] $slugs Known ecommerce plugin directory slugs.
		 */
		return array_values( array_unique( array_filter( apply_filters( 'edminboost_anm_ecommerce_plugin_slugs', $slugs ) ) ) );
	}

	/**
	 * Whether a notice should stay on the dashboard in ecommerce-only display mode.
	 *
	 * @param array $notice Notice row or capture payload.
	 * @return bool
	 */
	public static function notice_is_ecommerce_display( $notice ) {
		if ( ! is_array( $notice ) ) {
			return false;
		}

		$source_id = self::resolve_source_id_for_notice( $notice );
		$bucket    = self::get_ecommerce_display_bucket( $notice, $source_id );

		if ( '' === $bucket ) {
			$is_ecommerce = false;
		} else {
			$scope        = EDMINBOOST_ANM_Settings::get_ecommerce_display_scope();
			$is_ecommerce = ! empty( $scope[ $bucket ] );
		}

		/**
		 * Filter whether a notice counts as ecommerce for the ecommerce display experience.
		 *
		 * @param bool   $is_ecommerce Whether the notice is treated as ecommerce.
		 * @param array  $notice       Notice payload.
		 * @param string $source_id    Resolved registry source ID.
		 */
		return (bool) apply_filters( 'edminboost_anm_notice_is_ecommerce_display', $is_ecommerce, $notice, $source_id );
	}

	/**
	 * Ecommerce display bucket for scope checkboxes (woocommerce, edd, other).
	 *
	 * @param array  $notice    Notice payload.
	 * @param string $source_id Resolved registry source ID.
	 * @return string woocommerce|edd|other|''
	 */
	private static function get_ecommerce_display_bucket( $notice, $source_id ) {
		$edd_slugs = array( 'easy-digital-downloads', 'easy-digital-downloads-pro' );

		if ( 0 === strpos( $source_id, 'woocommerce.' ) ) {
			return 'woocommerce';
		}

		if ( 0 === strpos( $source_id, 'easy_digital_downloads.' ) ) {
			return 'edd';
		}

		$slug = self::match_plugin_slug_from_source_label( (string) ( $notice['source_plugin'] ?? '' ) );

		if ( 'woocommerce' === $slug ) {
			return 'woocommerce';
		}

		if ( in_array( $slug, $edd_slugs, true ) ) {
			return 'edd';
		}

		$ecom_slugs = self::get_ecommerce_plugin_slugs();

		if ( in_array( $slug, $ecom_slugs, true ) ) {
			return 'other';
		}

		if ( 0 === strpos( $source_id, 'plugin.' ) ) {
			$parts = explode( '.', $source_id );
			if ( ! empty( $parts[1] ) ) {
				if ( 'woocommerce' === $parts[1] ) {
					return 'woocommerce';
				}
				if ( in_array( $parts[1], $edd_slugs, true ) ) {
					return 'edd';
				}
				if ( in_array( $parts[1], $ecom_slugs, true ) ) {
					return 'other';
				}
			}
		}

		return '';
	}

	/**
	 * Map a captured notice to a registry source ID.
	 *
	 * @param array $notice Notice data.
	 * @return string
	 */
	public static function resolve_source_id_for_notice( $notice ) {
		$source  = sanitize_text_field( (string) ( $notice['source_plugin'] ?? '' ) );
		$message = strtolower( wp_strip_all_tags( (string) ( $notice['message'] ?? '' ) ) );
		$type    = sanitize_key( (string) ( $notice['type'] ?? 'info' ) );

		$slug = self::match_plugin_slug_from_source_label( $source );

		if ( 'woocommerce' === $slug ) {
			return self::match_woocommerce_source( $message, $type );
		}

		if ( in_array( $slug, array( 'easy-digital-downloads', 'edd' ), true ) ) {
			return self::match_edd_source( $message, $type );
		}

		if ( self::is_wordpress_core_source_label( $source ) ) {
			return self::match_wordpress_source( $message, $type );
		}

		if ( '' !== $slug ) {
			return self::match_generic_plugin_source( $slug, $message, $type );
		}

		/**
		 * Filter resolved notification source ID for a notice.
		 *
		 * @param string $source_id Default/other ID.
		 * @param array  $notice    Notice data.
		 */
		return apply_filters(
			'edminboost_anm_notification_source_id_for_notice',
			'other.' . sanitize_key( $source ),
			$notice
		);
	}

	/**
	 * @param string $message Message haystack.
	 * @param string $type    Notice type.
	 * @return string
	 */
	private static function match_wordpress_source( $message, $type ) {
		if ( self::message_matches_any( $message, array( 'comment', 'moderation', 'spam comment' ) ) ) {
			return 'wordpress.comments';
		}
		if ( self::message_matches_any( $message, array( 'site health', 'critical issue', 'health check' ) ) ) {
			return 'wordpress.site_health';
		}
		if ( self::message_matches_any( $message, array( 'security', 'ssl', 'vulnerabilit', 'login attempt' ) ) ) {
			return 'wordpress.security';
		}
		if ( self::message_matches_any( $message, array( 'update', 'upgrade', 'new version', 'maintenance' ) ) ) {
			return 'wordpress.updates';
		}
		if ( self::message_matches_any( $message, array( 'collaboration', 'invite', 'shared with', 'worked on' ) ) ) {
			return 'wordpress.collaboration';
		}
		if ( self::message_matches_any( $message, array( 'published', 'draft', 'post', 'page', 'revision', 'scheduled' ) ) ) {
			return 'wordpress.posts_pages';
		}

		return 'wordpress.posts_pages';
	}

	/**
	 * @param string $message Message haystack.
	 * @param string $type    Notice type.
	 * @return string
	 */
	private static function match_woocommerce_source( $message, $type ) {
		if ( 'upsell' === $type || self::message_matches_any( $message, array( 'tip', 'offer', 'recommend', 'try woocommerce', 'go pro' ) ) ) {
			return 'woocommerce.tips_offers';
		}
		if ( self::message_matches_any( $message, array( 'update', 'upgrade', 'new version' ) ) ) {
			return 'woocommerce.updates';
		}
		if ( self::message_matches_any( $message, array( 'stock', 'inventory', 'out of stock', 'low stock' ) ) ) {
			return 'woocommerce.stock';
		}
		if ( self::message_matches_any( $message, array( 'payment', 'refund', 'stripe', 'paypal', 'gateway' ) ) ) {
			return 'woocommerce.payments';
		}
		if ( self::message_matches_any( $message, array( 'order', 'checkout', 'fulfillment', 'shipment' ) ) ) {
			return 'woocommerce.orders';
		}
		if ( self::message_matches_any( $message, array( 'store health', 'woocommerce status', 'setup wizard' ) ) ) {
			return 'woocommerce.store_health';
		}

		return 'woocommerce.orders';
	}

	/**
	 * @param string $message Message haystack.
	 * @param string $type    Notice type.
	 * @return string
	 */
	private static function match_edd_source( $message, $type ) {
		if ( 'upsell' === $type || self::message_matches_any( $message, array( 'tip', 'offer', 'extension pass', 'upgrade' ) ) ) {
			return 'easy_digital_downloads.tips_offers';
		}
		if ( self::message_matches_any( $message, array( 'update', 'upgrade', 'new version' ) ) ) {
			return 'easy_digital_downloads.updates';
		}
		if ( self::message_matches_any( $message, array( 'extension', 'add-on', 'addon', 'license' ) ) ) {
			return 'easy_digital_downloads.extensions';
		}
		if ( self::message_matches_any( $message, array( 'payment', 'refund', 'stripe', 'paypal' ) ) ) {
			return 'easy_digital_downloads.payments';
		}
		if ( self::message_matches_any( $message, array( 'order', 'sale', 'purchase', 'download' ) ) ) {
			return 'easy_digital_downloads.orders';
		}

		return 'easy_digital_downloads.orders';
	}

	/**
	 * @param string $slug    Plugin directory slug.
	 * @param string $message Message haystack.
	 * @param string $type    Notice type.
	 * @return string
	 */
	private static function match_generic_plugin_source( $slug, $message, $type ) {
		$base = 'plugin.' . sanitize_key( $slug );

		if ( 'upsell' === $type || self::message_matches_any( $message, array( 'tip', 'offer', 'upgrade to pro', 'go premium' ) ) ) {
			return $base . '.tips_offers';
		}
		if ( self::message_matches_any( $message, array( 'update', 'upgrade', 'new version' ) ) ) {
			return $base . '.updates';
		}

		return $base . '.general';
	}

	/**
	 * @param string $message Message haystack.
	 * @param array  $needles Needle phrases.
	 * @return bool
	 */
	private static function message_matches_any( $message, $needles ) {
		foreach ( $needles as $needle ) {
			if ( '' !== $needle && false !== strpos( $message, $needle ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $source Source plugin label.
	 * @return bool
	 */
	private static function is_wordpress_core_source_label( $source ) {
		$normalized = strtolower( $source );
		$labels     = array(
			'wordpress',
			'wordpress core',
			strtolower( __( 'WordPress', EDMINBOOST_ANM_TEXT_DOMAIN ) ),
			strtolower( __( 'WordPress Core', EDMINBOOST_ANM_TEXT_DOMAIN ) ),
		);

		return in_array( $normalized, $labels, true );
	}

	/**
	 * Resolve plugin slug from a stored source label.
	 *
	 * @param string $source Source label.
	 * @return string Directory slug or empty.
	 */
	public static function match_plugin_slug_from_source_label( $source ) {
		$source = trim( (string) $source );
		if ( '' === $source ) {
			return '';
		}

		foreach ( self::get_active_plugin_files() as $plugin_file ) {
			$name = self::get_plugin_name_from_file( $plugin_file );
			if ( $name === $source ) {
				return self::plugin_file_slug( $plugin_file );
			}
		}

		$guess = sanitize_title( $source );
		$guess = str_replace( '-', '_', $guess );

		foreach ( self::get_active_plugin_files() as $plugin_file ) {
			$slug = self::plugin_file_slug( $plugin_file );
			if ( $slug === $guess || false !== strpos( $slug, str_replace( '_', '-', $guess ) ) ) {
				return $slug;
			}
		}

		return sanitize_key( str_replace( ' ', '-', strtolower( $source ) ) );
	}

	/**
	 * @return string[]
	 */
	private static function get_active_plugin_files() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$active = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$network = (array) get_site_option( 'active_sitewide_plugins', array() );
			foreach ( array_keys( $network ) as $plugin_file ) {
				$active[] = $plugin_file;
			}
		}

		return array_values( array_unique( array_filter( $active ) ) );
	}

	/**
	 * @param string $plugin_file Plugin bootstrap file.
	 * @return bool
	 */
	private static function is_plugin_active( $plugin_file ) {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( $plugin_file );
	}

	/**
	 * @return bool
	 */
	private static function is_edd_active() {
		return self::is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' )
			|| self::is_plugin_active( 'easy-digital-downloads-pro/easy-digital-downloads.php' );
	}

	/**
	 * @param string $plugin_file Plugin file.
	 * @return string
	 */
	private static function plugin_file_slug( $plugin_file ) {
		$dir = dirname( $plugin_file );
		return ( '.' === $dir || '/' === $dir ) ? sanitize_key( basename( $plugin_file, '.php' ) ) : sanitize_key( $dir );
	}

	/**
	 * @param string $plugin_file Plugin file.
	 * @return string
	 */
	private static function get_plugin_name_from_file( $plugin_file ) {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$path = WP_PLUGIN_DIR . '/' . $plugin_file;
		if ( ! file_exists( $path ) ) {
			return ucwords( str_replace( array( '-', '_' ), ' ', self::plugin_file_slug( $plugin_file ) ) );
		}

		$data = get_plugin_data( $path, false, false );
		if ( ! empty( $data['Name'] ) ) {
			return (string) $data['Name'];
		}

		return ucwords( str_replace( array( '-', '_' ), ' ', self::plugin_file_slug( $plugin_file ) ) );
	}
}
