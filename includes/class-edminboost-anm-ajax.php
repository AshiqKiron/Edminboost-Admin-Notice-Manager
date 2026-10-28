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
			'edminboost_anm_digest_preview',
			'edminboost_anm_webhook_preview',
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

		$context = sanitize_key( wp_unslash( $_POST['context'] ?? 'site' ) );
		if ( 'network' === $context ) {
			if ( ! EDMINBOOST_ANM_Multisite::is_active() || ! current_user_can( EDMINBOOST_ANM_Multisite::NETWORK_CAPABILITY ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied.', EDMINBOOST_ANM_TEXT_DOMAIN ) ), 403 );
			}

			return;
		}

		if ( ! current_user_can( EDMINBOOST_ANM_Settings::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', EDMINBOOST_ANM_TEXT_DOMAIN ) ), 403 );
		}
	}

	/**
	 * Verify the current user may change site-level settings.
	 *
	 * @return void
	 */
	private function verify_site_settings_mutation() {
		$this->verify_request();

		if ( EDMINBOOST_ANM_Multisite::is_active() && ! EDMINBOOST_ANM_Multisite::site_can_edit_settings() ) {
			wp_send_json_error(
				array( 'message' => __( 'Settings are managed by the network administrator.', EDMINBOOST_ANM_TEXT_DOMAIN ) ),
				403
			);
		}
	}

	/**
	 * Verify the current user may mutate auto-rules for the posted context.
	 *
	 * @return void
	 */
	private function verify_rules_mutation() {
		$this->verify_request();

		$context = sanitize_key( wp_unslash( $_POST['context'] ?? 'site' ) );
		if ( 'network' === $context ) {
			return;
		}

		if ( EDMINBOOST_ANM_Multisite::is_active() && ! EDMINBOOST_ANM_Multisite::site_can_edit_rules() ) {
			wp_send_json_error(
				array( 'message' => __( 'Auto-rules are managed by the network administrator.', EDMINBOOST_ANM_TEXT_DOMAIN ) ),
				403
			);
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
		$this->verify_site_settings_mutation();

		$stored            = EDMINBOOST_ANM_Settings::get_stored_raw();
		$enabled           = ! empty( $_POST['enabled'] );
		$stored['enabled'] = $enabled;
		EDMINBOOST_ANM_Settings::update_stored( $stored );

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
		$this->verify_site_settings_mutation();

		$settings = EDMINBOOST_ANM_Settings::merge_settings( EDMINBOOST_ANM_Settings::get_stored_raw() );
		$enabled  = ! empty( $_POST['enabled'] );

		$settings['upsell_preset']['enabled'] = $enabled;
		EDMINBOOST_ANM_Settings::update_stored( $settings );

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
		$this->verify_rules_mutation();

		$id      = sanitize_key( wp_unslash( $_POST['id'] ?? '' ) );
		$enabled = ! empty( $_POST['enabled'] );
		$context = sanitize_key( wp_unslash( $_POST['context'] ?? 'site' ) );

		$rule = EDMINBOOST_ANM_Rules::toggle( $id, $enabled, $context );

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
		$this->verify_rules_mutation();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in verify_request().
		$post = wp_unslash( $_POST );

		$data = array(
			'id'           => sanitize_key( $post['id'] ?? '' ),
			'name'         => sanitize_text_field( $post['name'] ?? '' ),
			'match_type'   => sanitize_key( $post['match_type'] ?? 'keyword' ),
			'match_string' => sanitize_text_field( $post['match_string'] ?? '' ),
			'action'       => sanitize_key( $post['rule_action'] ?? 'auto_dismiss_inbox' ),
			'enabled'      => ! empty( $post['enabled'] ),
			'schedule'     => EDMINBOOST_ANM_Rules::schedule_from_request( $post ),
			'context'      => sanitize_key( $post['context'] ?? 'site' ),
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
		$this->verify_rules_mutation();

		$id      = sanitize_key( wp_unslash( $_POST['id'] ?? '' ) );
		$context = sanitize_key( wp_unslash( $_POST['context'] ?? 'site' ) );
		$rule    = EDMINBOOST_ANM_Rules::get( $id );

		if ( ! $rule ) {
			wp_send_json_error( array( 'message' => __( 'Rule not found.', EDMINBOOST_ANM_TEXT_DOMAIN ) ) );
		}

		$wp_notice_preset = sanitize_key( $rule['wp_notice_preset'] ?? '' );

		if ( ! EDMINBOOST_ANM_Rules::delete( $id, $context ) ) {
			wp_send_json_error( array( 'message' => __( 'Rule not found.', EDMINBOOST_ANM_TEXT_DOMAIN ) ) );
		}

		wp_send_json_success(
			array(
				'wp_notice_preset' => $wp_notice_preset,
			)
		);
	}

	/**
	 * Convert a WordPress core notice preset into a rule.
	 *
	 * @return void
	 */
	public function handle_convert_wp_preset() {
		$this->verify_rules_mutation();

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
		$list_args['page'] = $slice['page'];

		wp_send_json_success(
			array(
				'rows'        => EDMINBOOST_ANM_Admin::render_inbox_table_rows_html( $slice['notices'] ),
				'orderby'     => $list_args['orderby'],
				'order'       => $list_args['order'],
				'page'        => $slice['page'],
				'total_pages' => $slice['total_pages'],
				'pager'       => EDMINBOOST_ANM_Admin::render_inbox_pager_html( $list_args, $slice ),
				'showing'     => sprintf(
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
				if ( '' === $id || ! EDMINBOOST_ANM_Inbox::mark_read( $id ) ) {
					wp_send_json_error(
						array(
							'message' => __( 'Could not mark notice as read.', EDMINBOOST_ANM_TEXT_DOMAIN ),
						)
					);
				}
				wp_send_json_success( array( 'read' => true ) );
				return;
			case 'snooze':
				if ( '' === $id ) {
					wp_send_json_error(
						array(
							'message' => __( 'Notice not found.', EDMINBOOST_ANM_TEXT_DOMAIN ),
						)
					);
				}
				$duration_key = sanitize_key( wp_unslash( $_POST['duration'] ?? '1d' ) );
				$duration_key = '7d' === $duration_key ? '7d' : '1d';
				$seconds      = '7d' === $duration_key ? WEEK_IN_SECONDS : DAY_IN_SECONDS;
				$notice       = EDMINBOOST_ANM_Inbox::get( $id );
				if ( $notice && EDMINBOOST_ANM_Inbox::is_actively_snoozed( $notice )
					&& EDMINBOOST_ANM_Inbox::get_snooze_duration_key( $notice ) === $duration_key ) {
					EDMINBOOST_ANM_Inbox::unsnooze( $id );
					wp_send_json_success(
						array(
							'snoozed'  => false,
							'duration' => '',
						)
					);
					return;
				}
				EDMINBOOST_ANM_Inbox::snooze( $id, $seconds, $duration_key );
				wp_send_json_success(
					array(
						'snoozed'  => true,
						'duration' => $duration_key,
					)
				);
				return;
			case 'unsnooze':
				if ( '' === $id ) {
					wp_send_json_error(
						array(
							'message' => __( 'Notice not found.', EDMINBOOST_ANM_TEXT_DOMAIN ),
						)
					);
				}
				EDMINBOOST_ANM_Inbox::unsnooze( $id );
				wp_send_json_success(
					array(
						'snoozed'  => false,
						'duration' => '',
					)
				);
				return;
			case 'convert':
				if ( '' === $id ) {
					wp_send_json_error(
						array(
							'message' => __( 'Notice not found.', EDMINBOOST_ANM_TEXT_DOMAIN ),
						)
					);
				}
				if ( EDMINBOOST_ANM_Multisite::is_active() && ! EDMINBOOST_ANM_Multisite::site_can_edit_rules() ) {
					wp_send_json_error(
						array(
							'message' => __( 'Auto-rules are managed by the network administrator.', EDMINBOOST_ANM_TEXT_DOMAIN ),
						),
						403
					);
				}
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
				if ( '' === $id ) {
					wp_send_json_error(
						array(
							'message' => __( 'Notice not found.', EDMINBOOST_ANM_TEXT_DOMAIN ),
						)
					);
				}
				if ( EDMINBOOST_ANM_Multisite::is_active() && ! EDMINBOOST_ANM_Multisite::site_can_edit_rules() ) {
					wp_send_json_error(
						array(
							'message' => __( 'Auto-rules are managed by the network administrator.', EDMINBOOST_ANM_TEXT_DOMAIN ),
						),
						403
					);
				}
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
				if ( '' === $id ) {
					wp_send_json_error(
						array(
							'message' => __( 'Notice not found.', EDMINBOOST_ANM_TEXT_DOMAIN ),
						)
					);
				}
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
				$bulk    = sanitize_key( wp_unslash( $_POST['bulk_action'] ?? '' ) );
				$allowed = array( 'read', 'snooze', 'archive' );
				if ( ! in_array( $bulk, $allowed, true ) ) {
					wp_send_json_error( array( 'message' => __( 'Invalid action.', EDMINBOOST_ANM_TEXT_DOMAIN ) ) );
				}
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
				'label' => sanitize_text_field(
					sprintf(
						/* translators: 1: display name, 2: login */
						__( '%1$s (%2$s)', EDMINBOOST_ANM_TEXT_DOMAIN ),
						$user->display_name,
						$user->user_login
					)
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
		if ( strlen( $html ) > EDMINBOOST_ANM_Inbox::MAX_CAPTURE_HTML_BYTES ) {
			wp_send_json_error( array( 'message' => __( 'Notice payload too large.', EDMINBOOST_ANM_TEXT_DOMAIN ) ) );
		}

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

		$html = wp_unslash( $_POST['html'] ?? '' );
		if ( strlen( $html ) > EDMINBOOST_ANM_Inbox::MAX_CAPTURE_HTML_BYTES ) {
			wp_send_json_error( array( 'message' => __( 'Notice payload too large.', EDMINBOOST_ANM_TEXT_DOMAIN ) ) );
		}

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
		$this->verify_rules_mutation();

		if ( empty( $_FILES['import_file']['tmp_name'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No file uploaded.', EDMINBOOST_ANM_TEXT_DOMAIN ) ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		$upload = $_FILES['import_file'];
		if ( ! empty( $upload['error'] ) && UPLOAD_ERR_OK !== (int) $upload['error'] ) {
			wp_send_json_error( array( 'message' => __( 'Upload failed.', EDMINBOOST_ANM_TEXT_DOMAIN ) ) );
		}

		$filename = isset( $upload['name'] ) ? sanitize_file_name( wp_unslash( $upload['name'] ) ) : '';
		$filetype = wp_check_filetype( $filename, array( 'json' => 'application/json' ) );
		if ( empty( $filetype['ext'] ) || 'json' !== $filetype['ext'] ) {
			wp_send_json_error( array( 'message' => __( 'Please upload a JSON file.', EDMINBOOST_ANM_TEXT_DOMAIN ) ) );
		}

		$tmp_name = wp_unslash( $upload['tmp_name'] );
		if ( ! is_uploaded_file( $tmp_name ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid upload.', EDMINBOOST_ANM_TEXT_DOMAIN ) ) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$json   = file_get_contents( $tmp_name );
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
	 * Preview weekly digest email using saved or unsaved settings fields.
	 *
	 * @return void
	 */
	public function handle_digest_preview() {
		$this->verify_request();

		$settings = EDMINBOOST_ANM_Settings::get();

		if ( isset( $_POST['digest'] ) && is_array( $_POST['digest'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$digest_input = wp_unslash( $_POST['digest'] );
			$settings['digest'] = EDMINBOOST_ANM_Settings::sanitize_digest_settings(
				$digest_input,
				$settings['digest']
			);
		}

		wp_send_json_success( EDMINBOOST_ANM_Digest::get_preview( $settings ) );
	}

	/**
	 * Preview webhook alert text and JSON payload using saved or unsaved message field.
	 *
	 * @return void
	 */
	public function handle_webhook_preview() {
		$this->verify_request();

		$settings = EDMINBOOST_ANM_Settings::get();

		if ( isset( $_POST['webhook'] ) && is_array( $_POST['webhook'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$webhook_input = wp_unslash( $_POST['webhook'] );
			$settings['webhook'] = EDMINBOOST_ANM_Settings::sanitize_webhook_settings(
				array(
					'message' => $webhook_input['message'] ?? '',
				),
				$settings['webhook']
			);
		}

		wp_send_json_success( EDMINBOOST_ANM_Webhook::get_preview( $settings ) );
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
