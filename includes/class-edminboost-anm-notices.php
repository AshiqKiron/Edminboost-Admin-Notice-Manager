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
		if ( ! EDMINBOOST_ANM_Settings::is_enabled() ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		if ( EDMINBOOST_ANM_Settings::should_hide_dashboard_notices() ) {
			add_action( 'admin_head', array( $this, 'hide_core_notices' ), 1 );
		}

		if ( EDMINBOOST_ANM_Settings::should_show_admin_bar_notice_panel() ) {
			$settings = EDMINBOOST_ANM_Settings::get();
			$panel    = wp_parse_args(
				$settings['notice_panel'] ?? array(),
				EDMINBOOST_ANM_Settings::get_defaults()['notice_panel']
			);
			// Register before wp_admin_bar_my_account_item (9991) so the bell sits left of Howdy.
			$priority = ( ! empty( $panel['position'] ) && 'before_profile' === $panel['position'] ) ? 9990 : 100;

			add_action( 'admin_bar_menu', array( $this, 'register_admin_bar_node' ), $priority );
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
		if ( ! is_admin() ) {
			return;
		}

		wp_enqueue_style(
			'edminboost-anm-notices',
			EDMINBOOST_ANM_PLUGIN_URL . 'admin/css/edminboost-anm-admin.css',
			array(),
			EDMINBOOST_ANM_VERSION
		);

		$settings = EDMINBOOST_ANM_Settings::get();
		$panel    = wp_parse_args(
			$settings['notice_panel'] ?? array(),
			EDMINBOOST_ANM_Settings::get_defaults()['notice_panel']
		);

		if ( ! empty( $panel['enabled'] ) && current_user_can( EDMINBOOST_ANM_Settings::CAPABILITY ) ) {
			$color_css = self::build_panel_color_css( $panel );
			if ( '' !== $color_css ) {
				wp_add_inline_style( 'edminboost-anm-notices', $color_css );
			}
		}

		if ( ! current_user_can( EDMINBOOST_ANM_Settings::CAPABILITY ) ) {
			return;
		}

		$localized = array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'edminboost_anm_admin' ),
			'i18n'    => array(
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

		if ( empty( $panel['enabled'] ) || 'dropdown' !== ( $panel['style'] ?? 'link' ) ) {
			return;
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
		if ( ! current_user_can( EDMINBOOST_ANM_Settings::CAPABILITY ) ) {
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

		if ( 'dropdown' === $style ) {
			$meta_class .= ' edminboost-anm-notices--dropdown';
		}

		if ( 'before_profile' === $position ) {
			$meta_class .= ' edminboost-anm-notices--before-profile edminboost-anm-notices--facebook';
		}

		$node = array(
			'id'    => 'edminboost-anm-notices',
			'title' => $this->build_admin_bar_title( $panel, $count_all ),
			'meta'  => array(
				'class' => $meta_class,
			),
		);

		if ( 'before_profile' === $position ) {
			$node['parent'] = 'top-secondary';
		}

		if ( 'dropdown' === $style ) {
			$node['href'] = '#';
			ob_start();
			$this->render_dropdown_panel( $count_all, $inbox_url );
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

		if ( 'dropdown' === $style || ( 'link' === $style && 'before_profile' === $position ) ) {
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

		if ( $count_all > 0 ) {
			$count_label = esc_html(
				sprintf(
					/* translators: %d: notice count */
					_n( '%d notice', '%d notices', $count_all, EDMINBOOST_ANM_TEXT_DOMAIN ),
					$count_all
				)
			);

			if ( 'dot' === $badge_style ) {
				$title .= sprintf(
					'<span class="edminboost-anm-notices-badge edminboost-anm-notices-badge--dot"><span class="screen-reader-text">%s</span></span>',
					$count_label
				);
			} else {
				$title .= sprintf(
					'<span class="edminboost-anm-notices-badge"><span class="screen-reader-text">%1$s</span><span aria-hidden="true">%2$d</span></span>',
					$count_label,
					$count_all
				);
			}
		}

		$sr_label = 'dropdown' === $style
			? esc_html__( 'Open notifications', EDMINBOOST_ANM_TEXT_DOMAIN )
			: esc_html__( 'View notice inbox', EDMINBOOST_ANM_TEXT_DOMAIN );

		$title .= sprintf( '<span class="screen-reader-text">%s</span>', $sr_label );

		return $title;
	}

	/**
	 * Render dropdown panel HTML for the admin bar node.
	 *
	 * @param int    $total     Total active inbox count.
	 * @param string $inbox_url Inbox admin URL.
	 * @return void
	 */
	private function render_dropdown_panel( $total, $inbox_url ) {
		$notices = EDMINBOOST_ANM_Inbox::query(
			array(
				'filter'   => 'all',
				'per_page' => self::DROPDOWN_PREVIEW_LIMIT,
				'orderby'  => 'date',
				'order'    => 'desc',
			)
		);

		$edminboost_anm_notices   = $notices;
		$edminboost_anm_inbox_url = $inbox_url;
		$edminboost_anm_total     = (int) $total;

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
