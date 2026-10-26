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

		$settings = EDMINBOOST_ANM_Settings::get();

		if ( ! empty( $settings['hide_core_notices'] ) ) {
			add_action( 'admin_head', array( $this, 'hide_core_notices' ), 1 );
		}

		if ( ! empty( $settings['notice_panel']['enabled'] ) ) {
			$panel    = wp_parse_args(
				$settings['notice_panel'] ?? array(),
				EDMINBOOST_ANM_Settings::get_defaults()['notice_panel']
			);
			$priority = ( ! empty( $panel['position'] ) && 'before_profile' === $panel['position'] ) ? 9998 : 100;

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

		if ( empty( $panel['enabled'] ) || 'dropdown' !== ( $panel['style'] ?? 'link' ) ) {
			return;
		}

		if ( ! current_user_can( EDMINBOOST_ANM_Settings::CAPABILITY ) ) {
			return;
		}

		wp_enqueue_script(
			'edminboost-anm-notices',
			EDMINBOOST_ANM_PLUGIN_URL . 'admin/js/edminboost-anm-notices.js',
			array(),
			EDMINBOOST_ANM_VERSION,
			true
		);
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
			$meta_class .= ' edminboost-anm-notices--before-profile';
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
			$node['href'] = '#edminboost-anm-notices';
			ob_start();
			$this->render_dropdown_panel( $count_all, $inbox_url );
			$node['meta']['html'] = ob_get_clean();
		} else {
			$node['href'] = $inbox_url;
		}

		$admin_bar->add_node( $node );

		if ( 'before_profile' === $position && $count_all > 0 ) {
			$this->prepend_howdy_notice_dot( $admin_bar, $count_all );
		}
	}

	/**
	 * Prepend a red unread dot to the left of the Howdy profile menu label.
	 *
	 * @param WP_Admin_Bar $admin_bar Admin bar instance.
	 * @param int          $count_all Active inbox count.
	 * @return void
	 */
	private function prepend_howdy_notice_dot( $admin_bar, $count_all ) {
		$account = $admin_bar->get_node( 'my-account' );
		if ( ! $account || empty( $account->title ) ) {
			return;
		}

		if ( false !== strpos( $account->title, 'edminboost-anm-howdy-dot' ) ) {
			return;
		}

		$dot = sprintf(
			'<span class="edminboost-anm-howdy-dot" aria-hidden="true"></span><span class="screen-reader-text">%s</span>',
			esc_html(
				sprintf(
					/* translators: %d: notice count */
					_n( '%d notice in inbox', '%d notices in inbox', $count_all, EDMINBOOST_ANM_TEXT_DOMAIN ),
					$count_all
				)
			)
		);

		$admin_bar->add_node(
			array(
				'id'    => 'my-account',
				'title' => $dot . $account->title,
			)
		);
	}

	/**
	 * Build admin bar node title markup.
	 *
	 * @param array $panel     Notice panel settings.
	 * @param int   $count_all Active inbox count.
	 * @return string
	 */
	private function build_admin_bar_title( $panel, $count_all ) {
		$style       = $panel['style'] ?? 'link';
		$position    = $panel['position'] ?? 'default';
		$badge_style = $panel['badge_style'] ?? 'count';

		if ( 'dropdown' === $style ) {
			$title = '<span class="ab-icon" aria-hidden="true"></span>';

			if ( $count_all > 0 && 'before_profile' !== $position ) {
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

			$title .= sprintf(
				'<span class="screen-reader-text">%s</span>',
				esc_html__( 'Open notice inbox', EDMINBOOST_ANM_TEXT_DOMAIN )
			);

			return $title;
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
}
