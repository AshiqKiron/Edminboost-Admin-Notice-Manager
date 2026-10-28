<?php
/**
 * Admin notice handling — runtime hooks for notice management.
 *
 * Purpose: Register front-end/admin hooks that control how notices are displayed.
 *
 * @package EdminBoost\AdminNoticeManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Notice manager.
 */
class EDMINBOOST_ANM_Notices {

	/**
	 * Max notices shown in the admin bar dropdown preview.
	 *
	 * @var int
	 */
	const DROPDOWN_PREVIEW_LIMIT = 8;

	/**
	 * Register runtime hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		$settings  = EDMINBOOST_ANM_Settings::get();
		$panel     = wp_parse_args(
			$settings['notice_panel'] ?? array(),
			EDMINBOOST_ANM_Settings::get_defaults()['notice_panel']
		);
		$placement = EDMINBOOST_ANM_Settings::get_notice_panel_admin_bar_placement( $panel['position'] ?? 'default' );
		add_action( 'admin_bar_menu', array( $this, 'register_admin_bar_node' ), $placement['hook_priority'] );

		add_action( 'admin_head', array( $this, 'hide_notices_on_plugin_screens' ), 1 );

		if ( ! EDMINBOOST_ANM_Settings::is_enabled() ) {
			return;
		}

		if ( EDMINBOOST_ANM_Settings::should_hide_dashboard_notices() ) {
			add_action( 'admin_head', array( $this, 'hide_core_notices' ), 1 );
		}

		/**
		 * Fires after notice manager hooks are registered.
		 *
		 * @param EDMINBOOST_ANM_Notices $notices Notice manager instance.
		 */
		do_action( 'edminboost_anm_notices_loaded', $this );
	}

	/**
	 * Enqueue admin assets when notice features are active.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( ! EDMINBOOST_ANM_Settings::is_enabled() ) {
			return;
		}

		$can_manage      = current_user_can( EDMINBOOST_ANM_Settings::CAPABILITY );
		$show_admin_bar  = EDMINBOOST_ANM_Settings::should_show_admin_bar_notice_panel();
		$needs_admin_bar = $can_manage && $show_admin_bar && ( is_admin() || is_admin_bar_showing() );

		if ( ! is_admin() && ! $needs_admin_bar ) {
			return;
		}

		$style_deps = array();
		if ( $needs_admin_bar ) {
			$style_deps[] = 'dashicons';
		}

		wp_enqueue_style(
			'edminboost-anm-notices',
			EDMINBOOST_ANM_PLUGIN_URL . 'admin/css/edminboost-anm-admin.css',
			$style_deps,
			EDMINBOOST_ANM_VERSION
		);

		$settings = EDMINBOOST_ANM_Settings::get();
		$panel    = wp_parse_args(
			$settings['notice_panel'] ?? array(),
			EDMINBOOST_ANM_Settings::get_defaults()['notice_panel']
		);

		if ( $needs_admin_bar ) {
			$color_css = self::build_panel_color_css( $panel );
			if ( '' !== $color_css ) {
				wp_add_inline_style( 'edminboost-anm-notices', $color_css );
			}
		}

		if ( ! $can_manage ) {
			return;
		}

		if ( ! is_admin() ) {
			if ( $needs_admin_bar && EDMINBOOST_ANM_Settings::notice_panel_style_is_dropdown( $panel['style'] ?? 'link' ) ) {
				$this->enqueue_admin_bar_dropdown_script( $panel );
			}
			return;
		}

		$localized = array(
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'edminboost_anm_admin' ),
			'badgeStyle'  => sanitize_key( $panel['badge_style'] ?? 'count' ),
			'i18n'        => array(
				'actionError' => __( 'Could not update this notice. Please try again.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'hideForMe'   => __( 'Hide for me', EDMINBOOST_ANM_TEXT_DOMAIN ),
				'hideForAll'  => __( 'Hide for all', EDMINBOOST_ANM_TEXT_DOMAIN ),
			),
		);

		if ( ! EDMINBOOST_ANM_Admin::is_plugin_screen() ) {
			wp_enqueue_script(
				'edminboost-anm-notice-inline',
				EDMINBOOST_ANM_PLUGIN_URL . 'admin/js/edminboost-anm-notice-inline.js',
				array(),
				EDMINBOOST_ANM_VERSION,
				true
			);

			wp_localize_script( 'edminboost-anm-notice-inline', 'edminboostAnmNotices', $localized );
			wp_localize_script(
				'edminboost-anm-notice-inline',
				'edminboostAnmNoticeInline',
				array(
					'cssIgnoreSelectors' => EDMINBOOST_ANM_Settings::get_css_ignore_selectors(),
				)
			);
		}

		if ( ! $show_admin_bar || ! EDMINBOOST_ANM_Settings::notice_panel_style_is_dropdown( $panel['style'] ?? 'link' ) ) {
			return;
		}

		$this->enqueue_admin_bar_dropdown_script( $panel, $localized );
	}

	/**
	 * Enqueue dropdown panel behavior for the admin bar node.
	 *
	 * @param array $panel     Notice panel settings.
	 * @param array $localized Optional pre-built script data.
	 * @return void
	 */
	private function enqueue_admin_bar_dropdown_script( $panel, $localized = null ) {
		if ( null === $localized ) {
			$localized = array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'edminboost_anm_admin' ),
				'badgeStyle' => sanitize_key( $panel['badge_style'] ?? 'count' ),
				'i18n'       => array(
					'actionError' => __( 'Could not update this notice. Please try again.', EDMINBOOST_ANM_TEXT_DOMAIN ),
					'hideForMe'   => __( 'Hide for me', EDMINBOOST_ANM_TEXT_DOMAIN ),
					'hideForAll'  => __( 'Hide for all', EDMINBOOST_ANM_TEXT_DOMAIN ),
				),
			);
		}

		wp_enqueue_script(
			'edminboost-anm-notices',
			EDMINBOOST_ANM_PLUGIN_URL . 'admin/js/edminboost-anm-notices.js',
			array(),
			EDMINBOOST_ANM_VERSION,
			true
		);

		wp_localize_script( 'edminboost-anm-notices', 'edminboostAnmNotices', $localized );
	}

	/**
	 * CSS that hides WordPress admin notices on plugin screens (restores panel preview mocks).
	 *
	 * @return string
	 */
	public static function get_plugin_screen_notice_hide_css() {
		return '#wpbody-content > .notice,#wpbody-content > .update-nag,#wpbody-content > div.error,#wpbody-content > div.updated,'
			. '.edminboost-anm-wrap .notice,.edminboost-anm-wrap .update-nag,.edminboost-anm-wrap div.error,.edminboost-anm-wrap div.updated{'
			. 'display:none!important;}'
			. '.edminboost-anm-notice-panel-preview .edminboost-anm-notices-panel__notice-body .notice,'
			. '.edminboost-anm-notice-panel-preview .edminboost-anm-notices-panel__notice-body .update-nag,'
			. '.edminboost-anm-display-experience-preview .edminboost-anm-display-experience-preview__notice,'
			. '.edminboost-anm-general-options-preview .edminboost-anm-display-experience-preview__notice,'
			. '.edminboost-anm-display-experience-preview .edminboost-anm-notices-panel__notice-body .notice,'
			. '.edminboost-anm-display-experience-preview .edminboost-anm-notices-panel__notice-body .update-nag,'
			. '.edminboost-anm-general-options-preview .update-nag{'
			. 'display:block!important;}';
	}

	/**
	 * Hide WordPress admin notices on this plugin's admin screens (keeps panel preview mocks visible).
	 *
	 * @return void
	 */
	public function hide_notices_on_plugin_screens() {
		if ( ! EDMINBOOST_ANM_Admin::is_plugin_screen() ) {
			return;
		}

		if ( ! current_user_can( EDMINBOOST_ANM_Settings::CAPABILITY ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static plugin-authored CSS.
		echo '<style>' . self::get_plugin_screen_notice_hide_css() . '</style>';
	}

	/**
	 * Hide default admin notices outside plugin screens.
	 *
	 * @return void
	 */
	public function hide_core_notices() {
		if ( EDMINBOOST_ANM_Admin::is_plugin_screen() ) {
			return;
		}

		if ( ! EDMINBOOST_ANM_Settings::should_apply_notice_hiding_for_user() ) {
			return;
		}

		echo '<style>.notice, .update-nag, div.error, div.updated { display: none !important; }</style>';
	}

	/**
	 * Add an admin bar entry for the notice panel.
	 *
	 * @param WP_Admin_Bar $admin_bar Admin bar instance.
	 * @return void
	 */
	public function register_admin_bar_node( $admin_bar ) {
		if ( ! EDMINBOOST_ANM_Settings::is_enabled() ) {
			return;
		}

		if ( ! current_user_can( EDMINBOOST_ANM_Settings::CAPABILITY ) ) {
			return;
		}

		if ( ! EDMINBOOST_ANM_Settings::should_show_admin_bar_notice_panel() ) {
			return;
		}

		$settings   = EDMINBOOST_ANM_Settings::get();
		$panel      = wp_parse_args(
			$settings['notice_panel'] ?? array(),
			EDMINBOOST_ANM_Settings::get_defaults()['notice_panel']
		);
		$style      = $panel['style'] ?? 'link';
		$position   = $panel['position'] ?? 'default';
		$counts     = EDMINBOOST_ANM_Inbox::get_counts();
		$inbox_url  = admin_url( 'admin.php?page=' . EDMINBOOST_ANM_Admin::PAGE_SLUG . '-inbox' );
		$count_all  = (int) ( $counts['all'] ?? 0 );
		$meta_class = 'edminboost-anm-notices-trigger';

		if ( EDMINBOOST_ANM_Settings::notice_panel_style_is_dropdown( $style ) ) {
			$meta_class .= ' edminboost-anm-notices--dropdown';
		}

		if ( EDMINBOOST_ANM_Settings::notice_panel_style_uses_bell( $style, $position )
			&& ! EDMINBOOST_ANM_Settings::notice_panel_position_uses_facebook_slot( $position ) ) {
			$meta_class .= ' edminboost-anm-notices--bell';
		}

		if ( EDMINBOOST_ANM_Settings::notice_panel_position_uses_facebook_slot( $position ) ) {
			$meta_class .= ' edminboost-anm-notices--before-profile edminboost-anm-notices--facebook';
		}

		$node = array(
			'id'    => 'edminboost-anm-notices',
			'title' => $this->build_admin_bar_title( $panel, $count_all ),
			'meta'  => array(
				'class' => $meta_class,
			),
		);

		$placement = EDMINBOOST_ANM_Settings::get_notice_panel_admin_bar_placement( $position );
		if ( ! empty( $placement['parent'] ) ) {
			$node['parent'] = $placement['parent'];
		}

		if ( EDMINBOOST_ANM_Settings::notice_panel_style_is_dropdown( $style ) ) {
			$node['href'] = '#';
			ob_start();
			$this->render_dropdown_panel( $count_all, $inbox_url, $style );
			$node['meta']['html'] = ob_get_clean();
		} else {
			$node['href'] = $inbox_url;
		}

		$admin_bar->add_node( $node );
	}

	/**
	 * Build admin bar node title markup.
	 *
	 * @param array $panel     Notice panel settings.
	 * @param int   $count_all Active inbox count.
	 * @return string
	 */
	private function build_admin_bar_title( $panel, $count_all ) {
		$style    = $panel['style'] ?? 'link';
		$position = $panel['position'] ?? 'default';

		if ( EDMINBOOST_ANM_Settings::notice_panel_style_uses_bell( $style, $position ) ) {
			return $this->build_facebook_bell_title( $panel, $count_all );
		}

		$title = esc_html__( 'Notices', EDMINBOOST_ANM_TEXT_DOMAIN );

		if ( $count_all > 0 ) {
			$title .= sprintf(
				' <span class="awaiting-mod count-%1$d"><span class="pending-count">%1$d</span></span>',
				$count_all
			);
		}

		return $title;
	}

	/**
	 * Facebook-style bell trigger with optional unread badge.
	 *
	 * @param array $panel     Notice panel settings.
	 * @param int   $count_all Active inbox count.
	 * @return string
	 */
	private function build_facebook_bell_title( $panel, $count_all ) {
		$style       = $panel['style'] ?? 'link';
		$badge_style = $panel['badge_style'] ?? 'count';
		$title       = '<span class="ab-icon edminboost-anm-fb-bell" aria-hidden="true"></span>';

		$title .= $this->build_notice_badge_markup( $panel, $count_all );

		$sr_label = EDMINBOOST_ANM_Settings::notice_panel_style_is_dropdown( $style )
			? __( 'Open notifications', EDMINBOOST_ANM_TEXT_DOMAIN )
			: __( 'View notice inbox', EDMINBOOST_ANM_TEXT_DOMAIN );

		if ( $count_all > 0 && EDMINBOOST_ANM_Settings::notice_panel_badge_style_is_hidden( $badge_style ) ) {
			$sr_label = sprintf(
				/* translators: 1: action label, 2: notice count phrase */
				__( '%1$s (%2$s)', EDMINBOOST_ANM_TEXT_DOMAIN ),
				$sr_label,
				sprintf(
					/* translators: %d: notice count */
					_n( '%d notice', '%d notices', $count_all, EDMINBOOST_ANM_TEXT_DOMAIN ),
					$count_all
				)
			);
		}

		$title .= sprintf( '<span class="screen-reader-text">%s</span>', esc_html( $sr_label ) );

		return $title;
	}

	/**
	 * Build bell unread badge markup (dot, number, or none).
	 *
	 * @param array $panel     Notice panel settings.
	 * @param int   $count_all Active inbox count.
	 * @return string
	 */
	private function build_notice_badge_markup( $panel, $count_all ) {
		if ( $count_all <= 0 ) {
			return '';
		}

		$badge_style = $panel['badge_style'] ?? 'count';

		if ( EDMINBOOST_ANM_Settings::notice_panel_badge_style_is_hidden( $badge_style ) ) {
			return '';
		}

		$count_label = esc_html(
			sprintf(
				/* translators: %d: notice count */
				_n( '%d notice', '%d notices', $count_all, EDMINBOOST_ANM_TEXT_DOMAIN ),
				$count_all
			)
		);

		if ( EDMINBOOST_ANM_Settings::notice_panel_badge_style_uses_dot( $badge_style ) ) {
			$classes = 'edminboost-anm-notices-badge edminboost-anm-notices-badge--dot';
			if ( 'dot_pulse' === sanitize_key( $badge_style ) ) {
				$classes .= ' edminboost-anm-notices-badge--pulse';
			}

			return sprintf(
				'<span class="%1$s"><span class="screen-reader-text">%2$s</span></span>',
				esc_attr( $classes ),
				$count_label
			);
		}

		$display = EDMINBOOST_ANM_Settings::format_notice_panel_badge_display_count( $count_all, $badge_style );

		return sprintf(
			'<span class="edminboost-anm-notices-badge"><span class="screen-reader-text">%1$s</span><span aria-hidden="true">%2$s</span></span>',
			$count_label,
			esc_html( $display )
		);
	}

	/**
	 * Render dropdown panel HTML for the admin bar node.
	 *
	 * @param int    $total     Total active inbox count.
	 * @param string $inbox_url Inbox admin URL.
	 * @param string $style     Panel style slug.
	 * @return void
	 */
	private function render_dropdown_panel( $total, $inbox_url, $style = 'dropdown' ) {
		$notices = EDMINBOOST_ANM_Inbox::query(
			array(
				'filter'   => 'all',
				'per_page' => self::DROPDOWN_PREVIEW_LIMIT,
				'orderby'  => 'date',
				'order'    => 'desc',
			)
		);

		$edminboost_anm_notices        = $notices;
		$edminboost_anm_inbox_url      = $inbox_url;
		$edminboost_anm_total          = (int) $total;
		$edminboost_anm_panel_layout   = 'dropdown_compact' === sanitize_key( $style ) ? 'compact' : 'detailed';

		include EDMINBOOST_ANM_PLUGIN_DIR . 'admin/partials/edminboost-anm-notice-panel-dropdown.php';
	}

	/**
	 * Build inline CSS custom properties for notice panel colors.
	 *
	 * @param array $panel Notice panel settings.
	 * @return string
	 */
	private static function build_panel_color_css( $panel ) {
		$colors = EDMINBOOST_ANM_Settings::get_notice_panel_colors( $panel );

		return sprintf(
			'#wpadminbar{--edminboost-anm-notice-badge-bg:%1$s;--edminboost-anm-notice-badge-text:%2$s;--edminboost-anm-notice-accent:%3$s;--edminboost-anm-notice-accent-hover:%4$s;--edminboost-anm-notice-bell-active:%5$s;}',
			esc_attr( $colors['badge'] ),
			esc_attr( $colors['badge_text'] ),
			esc_attr( $colors['accent'] ),
			esc_attr( $colors['accent_hover'] ),
			esc_attr( $colors['bell_active'] )
		);
	}
}
