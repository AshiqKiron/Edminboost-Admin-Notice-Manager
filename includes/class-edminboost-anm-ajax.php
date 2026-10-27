<?php
/**
 * AJAX handlers for admin UI interactions.
 *
 * @package EdminBoost\AdminNoticeManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX controller.
 */
class EDMINBOOST_ANM_Ajax {

	/**
	 * Register AJAX actions.
	 *
	 * @return void
	 */
	public function register_hooks() {
		$actions = array(
			'edminboost_anm_toggle_enabled',
			'edminboost_anm_toggle_preset',
			'edminboost_anm_toggle_rule',
			'edminboost_anm_save_rule',
			'edminboost_anm_delete_rule',
			'edminboost_anm_convert_wp_preset',
			'edminboost_anm_inbox_action',
			'edminboost_anm_inbox_list',
			'edminboost_anm_capture_notice',
			'edminboost_anm_run_importer',
			'edminboost_anm_import_rules',
			'edminboost_anm_seed_test_notices',
			'edminboost_anm_trigger_test_notices',
			'edminboost_anm_search_users',
			'edminboost_anm_reset_hidden_notices',
			'edminboost_anm_notice_hide',
		);

		foreach ( $actions as $action ) {
			add_action( 'wp_ajax_' . $action, array( $this, str_replace( 'edminboost_anm_', 'handle_', $action ) ) );
		}

		add_action( 'admin_post_edminboost_anm_export_rules', array( $this, 'handle_export_rules' ) );
	}

	/**
	 * Verify AJAX request.
	 *
	 * @return void
	 */
	private function verify_request() {
		check_ajax_referer( 'edminboost_anm_admin', 'nonce' );

		if ( ! current_user_can( EDMINBOOST_ANM_Settings::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', EDMINBOOST_ANM_TEXT_DOMAIN ) ), 403 );
		}
	}

	/**
	 * Verify AJAX request is allowed for WP_DEBUG tooling.
	 *
	 * @return void
	 */
	private function verify_debug_request() {
		$this->verify_request();

		if ( ! class_exists( 'EDMINBOOST_ANM_Debug' ) || ! EDMINBOOST_ANM_Debug::is_available() ) {
			wp_send_json_error( array( 'message' => __( 'Debug tools are unavailable.', EDMINBOOST_ANM_TEXT_DOMAIN ) ), 403 );
		}
	}

	/**
	 * Toggle global plugin enabled state.
	 *
	 * @return void
	 */
	public function handle_toggle_enabled() {
		$this->verify_request();

		$settings          = EDMINBOOST_ANM_Settings::get();
		$enabled           = ! empty( $_POST['enabled'] );
		$settings['enabled'] = $enabled;
		update_option( EDMINBOOST_ANM_Settings::OPTION_NAME, $settings, false );

		wp_send_json_success(
			array(
				'enabled' => $enabled,
			)
		);
	}

	/**
	 * Toggle upsell preset.
	 *
	 * @return void
	 */
	public function handle_toggle_preset() {
		$this->verify_request();

		$settings = EDMINBOOST_ANM_Settings::get();
		$enabled  = ! empty( $_POST['enabled'] );

		$settings['upsell_preset']['enabled'] = $enabled;
		update_option( EDMINBOOST_ANM_Settings::OPTION_NAME, $settings, false );

		wp_send_json_success(
			array(
				'enabled' => $enabled,
				'count'   => EDMINBOOST_ANM_Rules::get_preset_phrase_count(),
			)
		);
	}

	/**
	 * Toggle rule enabled state.
	 *
	 * @return void
	 */
	public function handle_toggle_rule() {
		$this->verify_request();

		$id      = sanitize_key( wp_unslash( $_POST['id'] ?? '' ) );
		$enabled = ! empty( $_POST['enabled'] );

		$rule = EDMINBOOST_ANM_Rules::toggle( $id, $enabled );

		if ( ! $rule ) {
			wp_send_json_error( array( 'message' => __( 'Rule not found.', EDMINBOOST_ANM_TEXT_DOMAIN ) ) );
		}

		wp_send_json_success( array( 'rule' => $rule ) );
	}

	/**
	 * Save rule from modal.
	 *
	 * @return void
	 */
	public function handle_save_rule() {
		$this->verify_request();

		$data = array(
			'id'           => sanitize_key( wp_unslash( $_POST['id'] ?? '' ) ),
			'name'         => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
			'match_type'   => sanitize_key( wp_unslash( $_POST['match_type'] ?? 'keyword' ) ),
			'match_string' => sanitize_text_field( wp_unslash( $_POST['match_string'] ?? '' ) ),
			'action'       => sanitize_key( wp_unslash( $_POST['rule_action'] ?? 'auto_dismiss_inbox' ) ),
			'enabled'      => ! empty( $_POST['enabled'] ),
		);

		$result = EDMINBOOST_ANM_Rules::save( $data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'rule' => $result ) );
	}

	/**
	 * Delete a rule.
	 *
	 * @return void
	 */
	public function handle_delete_rule() {
		$this->verify_request();

		$id = sanitize_key( wp_unslash( $_POST['id'] ?? '' ) );

		if ( ! EDMINBOOST_ANM_Rules::delete( $id ) ) {
			wp_send_json_error( array( 'message' => __( 'Rule not found.', EDMINBOOST_ANM_TEXT_DOMAIN ) ) );
		}

		wp_send_json_success();
	}

	/**
	 * Convert a WordPress core notice preset into a rule.
	 *
	 * @return void
	 */
	public function handle_convert_wp_preset() {
		$this->verify_request();

		$preset_id = sanitize_key( wp_unslash( $_POST['preset_id'] ?? '' ) );
		$undo      = ! empty( $_POST['undo'] );

		if ( $undo ) {
			$result = EDMINBOOST_ANM_Rules::unconvert_wp_preset( $preset_id );
			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}
			wp_send_json_success( array( 'converted' => false ) );
			return;
		}

		$result = EDMINBOOST_ANM_Rules::create_from_wp_preset( $preset_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'rule'      => $result,
				'converted' => true,
			)
		);
	}

	/**
	 * Inbox list refresh (sort / filter without full page reload).
	 *
	 * @return void
	 */
	public function handle_inbox_list() {
		$this->verify_request();

		$list_args = EDMINBOOST_ANM_Admin::parse_inbox_list_args( wp_unslash( $_POST ) );
		$slice     = EDMINBOOST_ANM_Admin::get_inbox_list_slice( $list_args );

		wp_send_json_success(
			array(
				'rows'    => EDMINBOOST_ANM_Admin::render_inbox_table_rows_html( $slice['notices'] ),
				'orderby' => $list_args['orderby'],
				'order'   => $list_args['order'],
				'showing' => sprintf(
					/* translators: 1: start number, 2: end number, 3: total count */
					__( 'Showing %1$d–%2$d of %3$d notices', EDMINBOOST_ANM_TEXT_DOMAIN ),
					$slice['start'],
					$slice['end'],
					$slice['total']
				),
			)
		);
	}

	/**
	 * Inbox row or bulk action.
	 *
	 * @return void
	 */
	public function handle_inbox_action() {
		$this->verify_request();

		$action = sanitize_key( wp_unslash( $_POST['inbox_action'] ?? '' ) );
		$id     = sanitize_key( wp_unslash( $_POST['id'] ?? '' ) );
		$ids    = isset( $_POST['ids'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['ids'] ) ) : array();

		switch ( $action ) {
			case 'read':
				EDMINBOOST_ANM_Inbox::mark_read( $id );
				break;
			case 'snooze':
				$duration = sanitize_key( wp_unslash( $_POST['duration'] ?? '1d' ) );
				$seconds  = '7d' === $duration ? WEEK_IN_SECONDS : DAY_IN_SECONDS;
				EDMINBOOST_ANM_Inbox::snooze( $id, $seconds );
				break;
			case 'convert':
				$result = EDMINBOOST_ANM_Rules::create_from_inbox( $id );
				if ( is_wp_error( $result ) ) {
					wp_send_json_error( array( 'message' => $result->get_error_message() ) );
				}
				wp_send_json_success(
					array(
						'rule'      => $result,
						'converted' => true,
					)
				);
				return;
			case 'unconvert':
				$result = EDMINBOOST_ANM_Rules::unconvert_inbox( $id );
				if ( is_wp_error( $result ) ) {
					wp_send_json_error( array( 'message' => $result->get_error_message() ) );
				}
				wp_send_json_success( array( 'converted' => false ) );
				return;
			case 'display_normal':
			case 'hide_for_me':
			case 'hide_for_all':
			case 'hide_forever':
			case 'panel_dismiss':
				$result = EDMINBOOST_ANM_Inbox::apply_panel_display_choice( $id, $action );
				if ( is_wp_error( $result ) ) {
					wp_send_json_error( array( 'message' => $result->get_error_message() ) );
				}
				wp_send_json_success(
					array(
						'counts'  => EDMINBOOST_ANM_Inbox::get_counts(),
						'reload'  => 'display_normal' === $action,
					)
				);
				return;
			case 'bulk':
				$bulk = sanitize_key( wp_unslash( $_POST['bulk_action'] ?? '' ) );
				$count = EDMINBOOST_ANM_Inbox::bulk_action( $ids, $bulk );
				wp_send_json_success( array( 'count' => $count ) );
				return;
			default:
				wp_send_json_error( array( 'message' => __( 'Invalid action.', EDMINBOOST_ANM_TEXT_DOMAIN ) ) );
		}

		wp_send_json_success();
	}

	/**
	 * Search users for hiding-notifications settings pickers.
	 *
	 * @return void
	 */
	public function handle_search_users() {
		$this->verify_request();

		$term = sanitize_text_field( wp_unslash( $_POST['term'] ?? '' ) );
		$term = trim( $term );

		if ( strlen( $term ) < 2 ) {
			wp_send_json_success( array( 'users' => array() ) );
		}

		$query = new WP_User_Query(
			array(
				'number'         => 20,
				'search'         => '*' . $term . '*',
				'search_columns' => array( 'user_login', 'user_nicename', 'display_name', 'user_email' ),
				'orderby'        => 'display_name',
				'order'          => 'ASC',
				'fields'         => array( 'ID', 'display_name', 'user_login' ),
			)
		);

		$results = array();

		foreach ( $query->get_results() as $user ) {
			if ( empty( $user->ID ) ) {
				continue;
			}

			$results[] = array(
				'id'    => (int) $user->ID,
				'label' => sprintf(
					/* translators: 1: display name, 2: login */
					__( '%1$s (%2$s)', EDMINBOOST_ANM_TEXT_DOMAIN ),
					$user->display_name,
					$user->user_login
				),
			);
		}

		wp_send_json_success( array( 'users' => $results ) );
	}

	/**
	 * Reset per-user hidden notice preferences (hash prefs).
	 *
	 * @return void
	 */
	public function handle_reset_hidden_notices() {
		$this->verify_request();

		$scope = sanitize_key( wp_unslash( $_POST['scope'] ?? 'current_user' ) );
		if ( ! in_array( $scope, array( 'current_user', 'all_users' ), true ) ) {
			$scope = 'current_user';
		}

		if ( 'all_users' === $scope && ! current_user_can( EDMINBOOST_ANM_Settings::CAPABILITY ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to reset hidden notices for all users.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				)
			);
		}

		EDMINBOOST_ANM_Inbox::reset_hash_preferences( $scope );

		wp_send_json_success(
			array(
				'message' => 'all_users' === $scope
					? __( 'Hidden notice preferences were reset for all users.', EDMINBOOST_ANM_TEXT_DOMAIN )
					: __( 'Hidden notice preferences were reset for your account.', EDMINBOOST_ANM_TEXT_DOMAIN ),
			)
		);
	}

	/**
	 * Hide a visible dashboard notice (inline Hide for me / Hide for all).
	 *
	 * @return void
	 */
	public function handle_notice_hide() {
		$this->verify_request();

		if ( ! EDMINBOOST_ANM_Settings::is_enabled() ) {
			wp_send_json_error( array( 'message' => __( 'Plugin disabled.', EDMINBOOST_ANM_TEXT_DOMAIN ) ) );
		}

		$scope = sanitize_key( wp_unslash( $_POST['scope'] ?? '' ) );
		if ( ! in_array( $scope, array( 'hide_for_me', 'hide_for_all' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid action.', EDMINBOOST_ANM_TEXT_DOMAIN ) ) );
		}

		$html = wp_unslash( $_POST['html'] ?? '' );
		$html = wp_kses_post( $html );

		$result = EDMINBOOST_ANM_Inbox::apply_inline_hide( $html, $scope );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'counts' => EDMINBOOST_ANM_Inbox::get_counts(),
			)
		);
	}

	/**
	 * Capture notice from JS interceptor.
	 *
	 * @return void
	 */
	public function handle_capture_notice() {
		$this->verify_request();

		if ( ! EDMINBOOST_ANM_Settings::is_enabled() ) {
			wp_send_json_error( array( 'message' => __( 'Plugin disabled.', EDMINBOOST_ANM_TEXT_DOMAIN ) ) );
		}

		$html   = wp_unslash( $_POST['html'] ?? '' );
		$html   = wp_kses_post( $html );
		$source = sanitize_text_field( wp_unslash( $_POST['source_plugin'] ?? '' ) );

		$interceptor = new EDMINBOOST_ANM_Interceptor();
		$result      = $interceptor->process_notice(
			$html,
			array(
				'source_plugin' => $source,
			)
		);

		wp_send_json_success( $result );
	}

	/**
	 * Run legacy competitor importer.
	 *
	 * @return void
	 */
	public function handle_run_importer() {
		$this->verify_request();

		$result = EDMINBOOST_ANM_Importer::import_legacy();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Import rules from uploaded JSON via AJAX.
	 *
	 * @return void
	 */
	public function handle_import_rules() {
		$this->verify_request();

		if ( empty( $_FILES['import_file']['tmp_name'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No file uploaded.', EDMINBOOST_ANM_TEXT_DOMAIN ) ) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$json   = file_get_contents( wp_unslash( $_FILES['import_file']['tmp_name'] ) );
		$result = EDMINBOOST_ANM_Importer::import_json( $json );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Seed the inbox with sample notices (WP_DEBUG only).
	 *
	 * @return void
	 */
	public function handle_seed_test_notices() {
		$this->verify_debug_request();

		$types = $this->get_debug_notice_types_from_request();

		if ( is_wp_error( $types ) ) {
			wp_send_json_error( array( 'message' => $types->get_error_message() ) );
		}

		$result = EDMINBOOST_ANM_Debug::seed_inbox( $types );

		wp_send_json_success(
			array(
				'added'    => $result['added'],
				'skipped'  => $result['skipped'],
				'inboxUrl' => admin_url( 'admin.php?page=' . EDMINBOOST_ANM_Admin::PAGE_SLUG . '-inbox' ),
			)
		);
	}

	/**
	 * Queue live admin notices and return redirect URL (WP_DEBUG only).
	 *
	 * @return void
	 */
	public function handle_trigger_test_notices() {
		$this->verify_debug_request();

		if ( ! EDMINBOOST_ANM_Settings::is_enabled() ) {
			wp_send_json_error(
				array(
					'message' => __( 'Enable notice interception first using Quick Status on the Dashboard.', EDMINBOOST_ANM_TEXT_DOMAIN ),
				)
			);
		}

		$types = $this->get_debug_notice_types_from_request();

		if ( is_wp_error( $types ) ) {
			wp_send_json_error( array( 'message' => $types->get_error_message() ) );
		}

		$rule = EDMINBOOST_ANM_Debug::ensure_test_rule();

		if ( is_wp_error( $rule ) ) {
			wp_send_json_error( array( 'message' => $rule->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'redirect' => EDMINBOOST_ANM_Debug::queue_live_notices( $types ),
			)
		);
	}

	/**
	 * Parse and validate notice type slugs from a debug AJAX request.
	 *
	 * @return string[]|WP_Error
	 */
	private function get_debug_notice_types_from_request() {
		$types = array();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in verify_debug_request().
		if ( isset( $_POST['notice_types'] ) ) {
			$types = EDMINBOOST_ANM_Debug::sanitize_notice_types( wp_unslash( $_POST['notice_types'] ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in verify_debug_request().
		if ( empty( $types ) && isset( $_POST['edminboost_anm_debug_notice_types'] ) ) {
			$types = EDMINBOOST_ANM_Debug::sanitize_notice_types( wp_unslash( $_POST['edminboost_anm_debug_notice_types'] ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in verify_debug_request().
		if ( empty( $types ) && ! empty( $_POST['notice_types_csv'] ) ) {
			$types = EDMINBOOST_ANM_Debug::sanitize_notice_types( wp_unslash( $_POST['notice_types_csv'] ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in verify_debug_request().
		if ( empty( $types ) && ! empty( $_POST['notice_types_json'] ) ) {
			$decoded = json_decode( wp_unslash( $_POST['notice_types_json'] ), true );
			if ( is_array( $decoded ) ) {
				$types = EDMINBOOST_ANM_Debug::sanitize_notice_types( $decoded );
			}
		}

		if ( empty( $types ) ) {
			return new WP_Error(
				'edminboost_anm_no_notice_types',
				__( 'Select at least one notice level (error, warning, success, and others).', EDMINBOOST_ANM_TEXT_DOMAIN )
			);
		}

		return $types;
	}

	/**
	 * Export rules as JSON download.
	 *
	 * @return void
	 */
	public function handle_export_rules() {
		if ( ! current_user_can( EDMINBOOST_ANM_Settings::CAPABILITY ) ) {
			wp_die( esc_html__( 'Permission denied.', EDMINBOOST_ANM_TEXT_DOMAIN ) );
		}

		check_admin_referer( 'edminboost_anm_export_rules' );

		$json = EDMINBOOST_ANM_Importer::export_json();

		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=edminboost-anm-rules.json' );
		header( 'Content-Length: ' . strlen( $json ) );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $json;
		exit;
	}
}
