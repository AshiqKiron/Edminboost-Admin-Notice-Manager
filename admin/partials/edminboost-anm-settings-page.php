<?php
/**
 * Settings page template.
 *
 * @package EdminBoost\AdminNoticeManager
 *
 * @var array  $settings   Plugin settings.
 * @var array  $legacy     Detected legacy plugin sources.
 * @var string $export_url Export JSON URL.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$edminboost_anm_option_name = EDMINBOOST_ANM_Settings::OPTION_NAME;
$edminboost_anm_roles       = array(
	'editor'        => __( 'Editor', EDMINBOOST_ANM_TEXT_DOMAIN ),
	'author'        => __( 'Author', EDMINBOOST_ANM_TEXT_DOMAIN ),
	'shop_manager'  => __( 'Shop Manager', EDMINBOOST_ANM_TEXT_DOMAIN ),
	'administrator' => __( 'Administrator', EDMINBOOST_ANM_TEXT_DOMAIN ),
);
$edminboost_anm_shield_roles  = $settings['client_shield']['roles'] ?? array();
$edminboost_anm_custom_phrases   = $settings['upsell_preset']['custom_phrases'] ?? array();
$edminboost_anm_builtin_count    = count( EDMINBOOST_ANM_Rules::get_builtin_preset_phrases() );
$edminboost_anm_word_filters     = wp_parse_args(
	$settings['word_filters'] ?? array(),
	EDMINBOOST_ANM_Settings::get_defaults()['word_filters']
);
$edminboost_anm_spam_words       = $edminboost_anm_word_filters['spam_words'] ?? array();
$edminboost_anm_whitelist_words  = $edminboost_anm_word_filters['whitelist_words'] ?? array();
$edminboost_anm_settings_tabs  = array(
	'general'       => __( 'General', EDMINBOOST_ANM_TEXT_DOMAIN ),
	'notices'       => __( 'Notices & access', EDMINBOOST_ANM_TEXT_DOMAIN ),
	'admin_bar'     => __( 'Admin bar', EDMINBOOST_ANM_TEXT_DOMAIN ),
	'filtering'     => __( 'Filtering', EDMINBOOST_ANM_TEXT_DOMAIN ),
	'security'      => __( 'Security & Shield', EDMINBOOST_ANM_TEXT_DOMAIN ),
	'notifications' => __( 'Notifications', EDMINBOOST_ANM_TEXT_DOMAIN ),
	'tools'         => __( 'Migration & Tools', EDMINBOOST_ANM_TEXT_DOMAIN ),
);
$edminboost_anm_manage_mode    = EDMINBOOST_ANM_Settings::get_manage_notices_mode();
$edminboost_anm_manage_modes   = array(
	'show_all'       => __( 'Show all', EDMINBOOST_ANM_TEXT_DOMAIN ),
	'hide_all'       => __( 'Hide all', EDMINBOOST_ANM_TEXT_DOMAIN ),
	'hide_selected'  => __( 'Hide selected', EDMINBOOST_ANM_TEXT_DOMAIN ),
	'compact_panel'  => __( 'Compact panel', EDMINBOOST_ANM_TEXT_DOMAIN ),
);
$edminboost_anm_active_tab     = 'general';
if ( isset( $_GET['tab'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab UI only; no privileged action.
	$edminboost_anm_tab_candidate = sanitize_key( wp_unslash( $_GET['tab'] ) );
	if ( 'upsell' === $edminboost_anm_tab_candidate ) {
		$edminboost_anm_tab_candidate = 'filtering';
	}
	if ( isset( $edminboost_anm_settings_tabs[ $edminboost_anm_tab_candidate ] ) ) {
		$edminboost_anm_active_tab = $edminboost_anm_tab_candidate;
	}
}
?>
<div class="wrap edminboost-anm-wrap edminboost-anm-settings" data-edminboost-anm-settings data-settings-tab="<?php echo esc_attr( $edminboost_anm_active_tab ); ?>">
	<header class="edminboost-anm-hero">
		<h1><?php esc_html_e( 'Settings', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h1>
	</header>

	<nav class="edminboost-anm-tabs edminboost-anm-settings-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Settings sections', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>">
		<?php foreach ( $edminboost_anm_settings_tabs as $edminboost_anm_tab_slug => $edminboost_anm_tab_label ) : ?>
			<?php
			$edminboost_anm_tab_is_active = $edminboost_anm_active_tab === $edminboost_anm_tab_slug;
			?>
			<button
				type="button"
				class="edminboost-anm-tabs__tab <?php echo $edminboost_anm_tab_is_active ? 'is-active' : ''; ?>"
				id="<?php echo esc_attr( 'edminboost-anm-settings-tab-' . $edminboost_anm_tab_slug ); ?>"
				role="tab"
				data-tab="<?php echo esc_attr( $edminboost_anm_tab_slug ); ?>"
				aria-selected="<?php echo $edminboost_anm_tab_is_active ? 'true' : 'false'; ?>"
				aria-controls="<?php echo esc_attr( 'edminboost-anm-settings-panel-' . $edminboost_anm_tab_slug ); ?>"
				tabindex="<?php echo $edminboost_anm_tab_is_active ? '0' : '-1'; ?>"
			>
				<?php echo esc_html( $edminboost_anm_tab_label ); ?>
			</button>
		<?php endforeach; ?>
	</nav>

	<div class="edminboost-anm-settings-panels">
	<form method="post" action="options.php" class="edminboost-anm-settings-form<?php echo 'tools' === $edminboost_anm_active_tab ? ' is-tab-tools' : ''; ?>">
		<?php settings_fields( EDMINBOOST_ANM_Settings::SETTINGS_GROUP ); ?>

		<div
			id="<?php echo esc_attr( 'edminboost-anm-settings-panel-general' ); ?>"
			class="edminboost-anm-tab-panel"
			role="tabpanel"
			data-tab="general"
			aria-labelledby="<?php echo esc_attr( 'edminboost-anm-settings-tab-general' ); ?>"
			<?php echo 'general' !== $edminboost_anm_active_tab ? ' hidden' : ''; ?>
		>
		<div class="edminboost-anm-card">
			<h2 class="screen-reader-text"><?php esc_html_e( 'General', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'When off, admin notices work as usual and this plugin stays idle.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
						<label for="edminboost-anm-enabled"><?php esc_html_e( 'Enable plugin', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
					</th>
					<td>
						<input type="checkbox" id="edminboost-anm-enabled" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[enabled]" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?> />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'How notices appear in the dashboard. “Hide selected” uses your Notices & access and Auto-Rules settings.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
						<?php esc_html_e( 'Notice display', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
					</th>
					<td>
						<fieldset class="edminboost-anm-segmented" data-edminboost-anm-manage-mode>
							<legend class="screen-reader-text"><?php esc_html_e( 'Notice display', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></legend>
							<?php foreach ( $edminboost_anm_manage_modes as $edminboost_anm_mode_slug => $edminboost_anm_mode_label ) : ?>
								<?php
								$edminboost_anm_mode_id = 'edminboost-anm-manage-' . $edminboost_anm_mode_slug;
								?>
								<input
									type="radio"
									name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[manage_notices_mode]"
									id="<?php echo esc_attr( $edminboost_anm_mode_id ); ?>"
									value="<?php echo esc_attr( $edminboost_anm_mode_slug ); ?>"
									<?php checked( $edminboost_anm_manage_mode, $edminboost_anm_mode_slug ); ?>
								/>
								<label for="<?php echo esc_attr( $edminboost_anm_mode_id ); ?>"><?php echo esc_html( $edminboost_anm_mode_label ); ?></label>
							<?php endforeach; ?>
						</fieldset>
						<p class="edminboost-anm-inline-notice" data-edminboost-anm-compact-panel-tip <?php echo 'compact_panel' === $edminboost_anm_manage_mode ? '' : 'hidden'; ?>>
							<?php esc_html_e( 'Puts all notices in one panel with counts. Click the panel to expand them.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Hides plugin and theme update reminders. Safety Guard can still show urgent security alerts.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
						<label for="edminboost-anm-disable-plugin-updates"><?php esc_html_e( 'Hide plugin & theme updates', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
					</th>
					<td>
						<?php
						$edminboost_anm_disable_plugin_updates = ! empty( $settings['disable_plugin_update_notifications'] );
						?>
						<input type="checkbox" id="edminboost-anm-disable-plugin-updates" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[disable_plugin_update_notifications]" value="1" <?php checked( $edminboost_anm_disable_plugin_updates ); ?> />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Hides the WordPress update banner. Update anytime under Dashboard → Updates.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
						<label for="edminboost-anm-disable-core-updates"><?php esc_html_e( 'Hide WordPress update notice', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
					</th>
					<td>
						<?php
						$edminboost_anm_disable_core_updates = ! empty( $settings['disable_core_update_notifications'] );
						?>
						<input type="checkbox" id="edminboost-anm-disable-core-updates" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[disable_core_update_notifications]" value="1" <?php checked( $edminboost_anm_disable_core_updates ); ?> />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Keeps hidden notices in the admin bar so you can still open them.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
						<label for="edminboost-anm-show-hidden-toolbar"><?php esc_html_e( 'Hidden notices in toolbar', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
					</th>
					<td>
						<?php
						$edminboost_anm_show_hidden_toolbar = ! empty( $settings['show_hidden_notices_in_toolbar'] );
						?>
						<input type="checkbox" id="edminboost-anm-show-hidden-toolbar" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[show_hidden_notices_in_toolbar]" value="1" <?php checked( $edminboost_anm_show_hidden_toolbar ); ?> />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Brings back notices you hid from the toolbar or from actions on each notice.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
						<?php esc_html_e( 'Reset hidden notices', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
					</th>
					<td>
						<div class="edminboost-anm-reset-hidden" data-edminboost-anm-reset-hidden>
							<fieldset>
								<legend class="screen-reader-text"><?php esc_html_e( 'Reset hidden notices scope', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></legend>
								<label class="edminboost-anm-reset-hidden__scope">
									<input type="radio" name="edminboost-anm-reset-hidden-scope" value="current_user" checked />
									<?php esc_html_e( 'Me only', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
								</label>
								<label class="edminboost-anm-reset-hidden__scope">
									<input type="radio" name="edminboost-anm-reset-hidden-scope" value="all_users" />
									<?php esc_html_e( 'All users', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
								</label>
							</fieldset>
							<p class="edminboost-anm-reset-hidden__actions">
								<button type="button" class="button edminboost-anm-reset-hidden__button"><?php esc_html_e( 'Reset', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
							</p>
							<p class="description edminboost-anm-reset-hidden__feedback" data-edminboost-anm-reset-hidden-feedback hidden></p>
						</div>
					</td>
				</tr>
			</table>
		</div>
		</div>

		<div
			id="<?php echo esc_attr( 'edminboost-anm-settings-panel-notices' ); ?>"
			class="edminboost-anm-tab-panel"
			role="tabpanel"
			data-tab="notices"
			aria-labelledby="<?php echo esc_attr( 'edminboost-anm-settings-tab-notices' ); ?>"
			<?php echo 'notices' !== $edminboost_anm_active_tab ? ' hidden' : ''; ?>
		>
		<?php
		$edminboost_anm_notice_modes = array(
			'inbox'   => __( 'Hide from the WordPress dashboard and show them in the plugin\'s popup', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'hide'    => __( 'Hide them completely (do not show in the WordPress dashboard or in the plugin\'s popup)', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'default' => __( 'Do not do anything (they will appear on the WordPress dashboard as per usual)', EDMINBOOST_ANM_TEXT_DOMAIN ),
		);
		$edminboost_anm_notice_mode_labels = array(
			'inbox'   => __( 'Inbox', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'hide'    => __( 'Hide', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'default' => __( 'Default', EDMINBOOST_ANM_TEXT_DOMAIN ),
		);
		$edminboost_anm_standard_levels = array(
			'success' => __( 'Success level notices', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'error'   => __( 'Error level notices', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'warning' => __( 'Warning level notices', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'info'    => __( 'Information level notices', EDMINBOOST_ANM_TEXT_DOMAIN ),
		);
		$edminboost_anm_standard_modes    = $settings['standard_admin_notices'] ?? EDMINBOOST_ANM_Settings::get_standard_admin_notice_defaults();
		$edminboost_anm_non_standard      = $settings['non_standard_admin_notices'] ?? array();
		$edminboost_anm_no_level_mode     = $edminboost_anm_non_standard['no_level'] ?? 'inbox';
		?>
		<div class="edminboost-anm-card">
			<h2><?php esc_html_e( 'Notice handling', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
			<p class="description"><?php esc_html_e( 'Used when Notice display (General tab) is set to Hide selected. Choose what happens to each notice type on the dashboard and in the admin bar panel.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
			<ul class="edminboost-anm-notice-mode-legend description">
				<?php foreach ( $edminboost_anm_notice_modes as $edminboost_anm_mode_slug => $edminboost_anm_mode_label ) : ?>
					<li><strong><?php echo esc_html( $edminboost_anm_notice_mode_labels[ $edminboost_anm_mode_slug ] ?? $edminboost_anm_mode_slug ); ?>:</strong> <?php echo esc_html( $edminboost_anm_mode_label ); ?></li>
				<?php endforeach; ?>
			</ul>
			<h3 class="edminboost-anm-settings-subheading"><?php esc_html_e( 'Standard notices (severity levels)', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h3>
			<table class="form-table edminboost-anm-notice-policy-table edminboost-anm-notice-handling-table" role="presentation">
				<?php foreach ( $edminboost_anm_standard_levels as $edminboost_anm_level_slug => $edminboost_anm_level_label ) : ?>
					<?php
					$edminboost_anm_level_mode = $edminboost_anm_standard_modes[ $edminboost_anm_level_slug ] ?? 'inbox';
					$edminboost_anm_field_id   = 'edminboost-anm-standard-' . $edminboost_anm_level_slug;
					?>
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( $edminboost_anm_field_id ); ?>"><?php echo esc_html( $edminboost_anm_level_label ); ?></label>
						</th>
						<td>
							<select id="<?php echo esc_attr( $edminboost_anm_field_id ); ?>" name="<?php echo esc_attr( $edminboost_anm_option_name . '[standard_admin_notices][' . $edminboost_anm_level_slug . ']' ); ?>">
								<?php foreach ( $edminboost_anm_notice_modes as $edminboost_anm_mode_slug => $edminboost_anm_mode_label ) : ?>
									<option value="<?php echo esc_attr( $edminboost_anm_mode_slug ); ?>" <?php selected( $edminboost_anm_level_mode, $edminboost_anm_mode_slug ); ?>><?php echo esc_html( $edminboost_anm_notice_mode_labels[ $edminboost_anm_mode_slug ] ?? $edminboost_anm_mode_slug ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<h3 class="edminboost-anm-settings-subheading"><?php esc_html_e( 'Non-standard notices', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h3>
			<p class="description"><?php esc_html_e( 'Notices from plugins and themes without a success, error, warning, or info class.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
			<table class="form-table edminboost-anm-notice-policy-table edminboost-anm-notice-handling-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="edminboost-anm-non-standard-no-level"><?php esc_html_e( 'No level notices', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
					</th>
					<td>
						<select id="edminboost-anm-non-standard-no-level" name="<?php echo esc_attr( $edminboost_anm_option_name . '[non_standard_admin_notices][no_level]' ); ?>">
							<?php foreach ( $edminboost_anm_notice_modes as $edminboost_anm_mode_slug => $edminboost_anm_mode_label ) : ?>
								<option value="<?php echo esc_attr( $edminboost_anm_mode_slug ); ?>" <?php selected( $edminboost_anm_no_level_mode, $edminboost_anm_mode_slug ); ?>><?php echo esc_html( $edminboost_anm_notice_mode_labels[ $edminboost_anm_mode_slug ] ?? $edminboost_anm_mode_slug ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="edminboost-anm-css-ignore-selectors"><?php esc_html_e( 'CSS ignore selectors', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
					</th>
					<td>
						<input
							type="text"
							id="edminboost-anm-css-ignore-selectors"
							class="large-text code"
							name="<?php echo esc_attr( $edminboost_anm_option_name . '[non_standard_admin_notices][css_ignore_selectors]' ); ?>"
							value="<?php echo esc_attr( $edminboost_anm_non_standard['css_ignore_selectors'] ?? '' ); ?>"
						/>
						<p class="description"><?php esc_html_e( 'Skip interception for notices matching these jQuery-compatible selectors (comma-separated).', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<?php
		$edminboost_anm_acl_roles   = $settings['acl']['roles'] ?? array();
		$edminboost_anm_acl_choices = EDMINBOOST_ANM_Settings::get_acl_role_choices();
		$edminboost_anm_hiding_defaults = EDMINBOOST_ANM_Settings::get_defaults()['hiding_notifications'];
		$edminboost_anm_hiding          = wp_parse_args(
			$settings['hiding_notifications'] ?? array(),
			$edminboost_anm_hiding_defaults
		);
		$edminboost_anm_hiding_mode     = $edminboost_anm_hiding['mode'] ?? 'all';
		$edminboost_anm_hiding_users    = EDMINBOOST_ANM_Settings::get_hiding_notification_user_choices();
		$edminboost_anm_hiding_modes    = array(
			'all'    => __( 'Hide notifications from all users', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'only'   => __( 'Hide notifications only from these users', EDMINBOOST_ANM_TEXT_DOMAIN ),
			'except' => __( 'Hide notifications to all users but not these', EDMINBOOST_ANM_TEXT_DOMAIN ),
		);
		?>
		<div class="edminboost-anm-card">
			<h2><?php esc_html_e( 'Access & visibility', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
			<p class="description"><?php esc_html_e( 'Control which roles and individual accounts are affected by notice hiding. Role rules apply first; user rules refine who still gets interception.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
			<h3 class="edminboost-anm-settings-subheading"><?php esc_html_e( 'By role (ACL)', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h3>
			<table class="form-table edminboost-anm-acl-table" role="presentation">
				<?php foreach ( $edminboost_anm_acl_choices as $edminboost_anm_acl_role ) : ?>
					<?php
					$edminboost_anm_acl_slug       = $edminboost_anm_acl_role['slug'];
					$edminboost_anm_acl_visibility = $edminboost_anm_acl_roles[ $edminboost_anm_acl_slug ] ?? 'display';
					$edminboost_anm_acl_field_id   = 'edminboost-anm-acl-' . $edminboost_anm_acl_slug;
					?>
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( $edminboost_anm_acl_field_id ); ?>"><?php echo esc_html( $edminboost_anm_acl_slug ); ?></label>
						</th>
						<td>
							<select id="<?php echo esc_attr( $edminboost_anm_acl_field_id ); ?>" name="<?php echo esc_attr( $edminboost_anm_option_name . '[acl][roles][' . $edminboost_anm_acl_slug . ']' ); ?>">
								<option value="display" <?php selected( $edminboost_anm_acl_visibility, 'display' ); ?>><?php esc_html_e( 'Display notifications', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></option>
								<option value="hide" <?php selected( $edminboost_anm_acl_visibility, 'hide' ); ?>><?php esc_html_e( 'Do not display notifications', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></option>
							</select>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<h3 class="edminboost-anm-settings-subheading"><?php esc_html_e( 'By user account', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h3>
			<table class="form-table edminboost-anm-notice-policy-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Visibility', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
					<td>
						<fieldset class="edminboost-anm-hiding-visibility-options" data-edminboost-anm-hiding-visibility>
							<legend class="screen-reader-text"><?php esc_html_e( 'Visibility', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></legend>
							<?php foreach ( $edminboost_anm_hiding_modes as $edminboost_anm_hiding_slug => $edminboost_anm_hiding_label ) : ?>
								<?php
								$edminboost_anm_hiding_field_id = 'edminboost-anm-hiding-' . $edminboost_anm_hiding_slug;
								$edminboost_anm_picker_active   = in_array( $edminboost_anm_hiding_slug, array( 'only', 'except' ), true );
								$edminboost_anm_picker_enabled   = $edminboost_anm_hiding_mode === $edminboost_anm_hiding_slug;
								?>
								<label class="edminboost-anm-hiding-visibility-options__mode" for="<?php echo esc_attr( $edminboost_anm_hiding_field_id ); ?>">
									<input
										type="radio"
										id="<?php echo esc_attr( $edminboost_anm_hiding_field_id ); ?>"
										name="<?php echo esc_attr( $edminboost_anm_option_name . '[hiding_notifications][mode]' ); ?>"
										value="<?php echo esc_attr( $edminboost_anm_hiding_slug ); ?>"
										<?php checked( $edminboost_anm_hiding_mode, $edminboost_anm_hiding_slug ); ?>
									/>
									<?php echo esc_html( $edminboost_anm_hiding_label ); ?>
								</label>
								<?php if ( $edminboost_anm_picker_active ) : ?>
									<div
										class="edminboost-anm-user-picker"
										data-edminboost-anm-user-picker
										data-picker-mode="<?php echo esc_attr( $edminboost_anm_hiding_slug ); ?>"
										<?php echo $edminboost_anm_picker_enabled ? '' : ' hidden'; ?>
									>
										<div class="edminboost-anm-user-picker__selected" data-edminboost-anm-user-selected>
											<?php foreach ( $edminboost_anm_hiding_users as $edminboost_anm_hiding_user ) : ?>
												<span class="edminboost-anm-user-picker__chip" data-user-id="<?php echo esc_attr( (string) $edminboost_anm_hiding_user['id'] ); ?>">
													<span class="edminboost-anm-user-picker__chip-text"><?php echo esc_html( $edminboost_anm_hiding_user['label'] ); ?></span>
													<button type="button" class="edminboost-anm-user-picker__remove" aria-label="<?php esc_attr_e( 'Remove user', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>">&times;</button>
													<input
														type="hidden"
														name="<?php echo esc_attr( $edminboost_anm_option_name . '[hiding_notifications][users][]' ); ?>"
														value="<?php echo esc_attr( (string) $edminboost_anm_hiding_user['id'] ); ?>"
														<?php disabled( ! $edminboost_anm_picker_enabled ); ?>
													/>
												</span>
											<?php endforeach; ?>
										</div>
										<input
											type="search"
											class="edminboost-anm-user-picker__search regular-text"
											placeholder="<?php esc_attr_e( 'select user(s)', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>"
											autocomplete="off"
											aria-label="<?php esc_attr_e( 'Search users', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>"
											<?php disabled( ! $edminboost_anm_picker_enabled ); ?>
										/>
										<ul class="edminboost-anm-user-picker__results" data-edminboost-anm-user-results hidden role="listbox"></ul>
									</div>
								<?php endif; ?>
							<?php endforeach; ?>
						</fieldset>
					</td>
				</tr>
			</table>
		</div>
		</div>

		<div
			id="<?php echo esc_attr( 'edminboost-anm-settings-panel-admin_bar' ); ?>"
			class="edminboost-anm-tab-panel"
			role="tabpanel"
			data-tab="admin_bar"
			aria-labelledby="<?php echo esc_attr( 'edminboost-anm-settings-tab-admin_bar' ); ?>"
			<?php echo 'admin_bar' !== $edminboost_anm_active_tab ? ' hidden' : ''; ?>
		>
		<?php
		$edminboost_anm_panel_style       = $settings['notice_panel']['style'] ?? 'link';
		$edminboost_anm_panel_colors       = EDMINBOOST_ANM_Settings::get_notice_panel_colors( $settings['notice_panel'] ?? array() );
		$edminboost_anm_dropdown_hidden    = 'dropdown' !== $edminboost_anm_panel_style ? ' hidden' : '';
		?>
		<div class="edminboost-anm-card">
			<h2 class="screen-reader-text"><?php esc_html_e( 'Admin bar', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Admin bar panel', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
					<td>
						<label for="edminboost-anm-notice-panel">
							<input type="checkbox" id="edminboost-anm-notice-panel" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[notice_panel][enabled]" value="1" <?php checked( ! empty( $settings['notice_panel']['enabled'] ) ); ?> />
							<?php esc_html_e( 'Show notices in the WordPress admin bar', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="edminboost-anm-notice-panel-style"><?php esc_html_e( 'Panel style', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label></th>
					<td>
						<select id="edminboost-anm-notice-panel-style" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[notice_panel][style]">
							<option value="link" <?php selected( $settings['notice_panel']['style'] ?? 'link', 'link' ); ?>><?php esc_html_e( 'Text link with count', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></option>
							<option value="dropdown" <?php selected( $settings['notice_panel']['style'] ?? 'link', 'dropdown' ); ?>><?php esc_html_e( 'Notification dropdown (bell icon)', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Dropdown style opens a Facebook-like notice box listing recent inbox items.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
					</td>
				</tr>
				<tr class="edminboost-anm-notice-panel-dropdown-row" <?php echo $edminboost_anm_dropdown_hidden; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<th scope="row"><label for="edminboost-anm-notice-panel-badge"><?php esc_html_e( 'Unread indicator', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label></th>
					<td>
						<select id="edminboost-anm-notice-panel-badge" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[notice_panel][badge_style]">
							<option value="count" <?php selected( $settings['notice_panel']['badge_style'] ?? 'count', 'count' ); ?>><?php esc_html_e( 'Number badge', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></option>
							<option value="dot" <?php selected( $settings['notice_panel']['badge_style'] ?? 'count', 'dot' ); ?>><?php esc_html_e( 'Dot only (no count)', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'How unread notices appear on the bell icon when using the notification dropdown.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
					</td>
				</tr>
				<tr class="edminboost-anm-notice-panel-dropdown-row" <?php echo $edminboost_anm_dropdown_hidden; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<th scope="row"><?php esc_html_e( 'Panel colors', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
					<td>
						<fieldset class="edminboost-anm-notice-panel-colors">
							<legend class="screen-reader-text"><?php esc_html_e( 'Panel colors', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></legend>
							<p class="edminboost-anm-notice-panel-colors__row">
								<label for="edminboost-anm-notice-color-badge"><?php esc_html_e( 'Dot / badge', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
								<input type="color" id="edminboost-anm-notice-color-badge" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[notice_panel][colors][badge]" value="<?php echo esc_attr( $edminboost_anm_panel_colors['badge'] ); ?>" />
							</p>
							<p class="edminboost-anm-notice-panel-colors__row">
								<label for="edminboost-anm-notice-color-badge-text"><?php esc_html_e( 'Badge number text', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
								<input type="color" id="edminboost-anm-notice-color-badge-text" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[notice_panel][colors][badge_text]" value="<?php echo esc_attr( $edminboost_anm_panel_colors['badge_text'] ); ?>" />
							</p>
							<p class="edminboost-anm-notice-panel-colors__row">
								<label for="edminboost-anm-notice-color-accent"><?php esc_html_e( 'Accent (count pill & links)', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
								<input type="color" id="edminboost-anm-notice-color-accent" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[notice_panel][colors][accent]" value="<?php echo esc_attr( $edminboost_anm_panel_colors['accent'] ); ?>" />
							</p>
							<p class="edminboost-anm-notice-panel-colors__row">
								<label for="edminboost-anm-notice-color-accent-hover"><?php esc_html_e( 'Link hover', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
								<input type="color" id="edminboost-anm-notice-color-accent-hover" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[notice_panel][colors][accent_hover]" value="<?php echo esc_attr( $edminboost_anm_panel_colors['accent_hover'] ); ?>" />
							</p>
							<p class="edminboost-anm-notice-panel-colors__row">
								<label for="edminboost-anm-notice-color-bell-active"><?php esc_html_e( 'Open bell highlight', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
								<input type="color" id="edminboost-anm-notice-color-bell-active" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[notice_panel][colors][bell_active]" value="<?php echo esc_attr( $edminboost_anm_panel_colors['bell_active'] ); ?>" />
							</p>
						</fieldset>
						<p class="description"><?php esc_html_e( 'Colors apply to the bell badge, Howdy dot, dropdown count pill, and footer links.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="edminboost-anm-notice-panel-position"><?php esc_html_e( 'Panel position', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label></th>
					<td>
						<select id="edminboost-anm-notice-panel-position" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[notice_panel][position]">
							<option value="default" <?php selected( $settings['notice_panel']['position'] ?? 'default', 'default' ); ?>><?php esc_html_e( 'Default admin bar slot', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></option>
							<option value="before_profile" <?php selected( $settings['notice_panel']['position'] ?? 'default', 'before_profile' ); ?>><?php esc_html_e( 'Top right, beside “Howdy” profile menu', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></option>
						</select>
					</td>
				</tr>
			</table>
		</div>
		</div>

		<div
			id="<?php echo esc_attr( 'edminboost-anm-settings-panel-filtering' ); ?>"
			class="edminboost-anm-tab-panel"
			role="tabpanel"
			data-tab="filtering"
			aria-labelledby="<?php echo esc_attr( 'edminboost-anm-settings-tab-filtering' ); ?>"
			<?php echo 'filtering' !== $edminboost_anm_active_tab ? ' hidden' : ''; ?>
		>
		<div class="edminboost-anm-card">
			<h2><?php esc_html_e( 'Upsell blacklist', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
			<p><?php esc_html_e( 'Suppress review requests, promotional nags, and upsell notices that match these phrases.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Upsell preset', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
					<td>
						<label for="edminboost-anm-upsell-preset">
							<input type="checkbox" id="edminboost-anm-upsell-preset" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[upsell_preset][enabled]" value="1" <?php checked( ! empty( $settings['upsell_preset']['enabled'] ) ); ?> />
							<?php esc_html_e( 'Enable "Kill All Upsells" preset', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="edminboost-anm-custom-phrases"><?php esc_html_e( 'Custom phrases', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
					</th>
					<td>
						<textarea id="edminboost-anm-custom-phrases" class="large-text code" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[upsell_preset][custom_phrases]" rows="8" placeholder="<?php esc_attr_e( 'One phrase per line, e.g.&#10;try our pro version&#10;limited offer', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>"><?php echo esc_textarea( implode( "\n", $edminboost_anm_custom_phrases ) ); ?></textarea>
						<p class="description">
							<?php
							echo esc_html(
								sprintf(
									/* translators: %d: built-in phrase count */
									__( '%d built-in phrases are always active. Add your own below (one per line). Duplicates are ignored.', EDMINBOOST_ANM_TEXT_DOMAIN ),
									$edminboost_anm_builtin_count,
								)
							);
							if ( ! empty( $edminboost_anm_custom_phrases ) ) {
								echo ' ';
								echo esc_html(
									sprintf(
										/* translators: %d: custom phrase count */
										__( 'You have %d custom phrase(s).', EDMINBOOST_ANM_TEXT_DOMAIN ),
										count( $edminboost_anm_custom_phrases )
									)
								);
							}
							?>
						</p>
					</td>
				</tr>
			</table>
		</div>

		<div class="edminboost-anm-card">
			<h2><?php esc_html_e( 'Spam words & whitelist', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Spam words', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
						<p class="description"><?php esc_html_e( 'Set words considered spam. Admin notices that contain any of these words will not be shown.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
					</th>
					<td>
						<label class="screen-reader-text" for="edminboost-anm-spam-words"><?php esc_html_e( 'Spam words', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
						<textarea
							id="edminboost-anm-spam-words"
							class="large-text code"
							name="<?php echo esc_attr( $edminboost_anm_option_name . '[word_filters][spam_words]' ); ?>"
							rows="6"
							placeholder="<?php esc_attr_e( 'e.g. limited offer, buy now, rate us', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>"
						><?php echo esc_textarea( implode( ', ', $edminboost_anm_spam_words ) ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Enter words separated by commas.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Whitelist', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
						<p class="description"><?php esc_html_e( 'Notices that contain any whitelist word are always shown on screen and are not intercepted by this plugin.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
					</th>
					<td>
						<label class="screen-reader-text" for="edminboost-anm-whitelist-words"><?php esc_html_e( 'Whitelist', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
						<textarea
							id="edminboost-anm-whitelist-words"
							class="large-text code"
							name="<?php echo esc_attr( $edminboost_anm_option_name . '[word_filters][whitelist_words]' ); ?>"
							rows="6"
							placeholder="<?php esc_attr_e( 'e.g. security update, action required', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>"
						><?php echo esc_textarea( implode( ', ', $edminboost_anm_whitelist_words ) ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Enter words separated by commas.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
					</td>
				</tr>
			</table>
		</div>
		</div>

		<div
			id="<?php echo esc_attr( 'edminboost-anm-settings-panel-security' ); ?>"
			class="edminboost-anm-tab-panel"
			role="tabpanel"
			data-tab="security"
			aria-labelledby="<?php echo esc_attr( 'edminboost-anm-settings-tab-security' ); ?>"
			<?php echo 'security' !== $edminboost_anm_active_tab ? ' hidden' : ''; ?>
		>
		<div class="edminboost-anm-card">
			<h2 class="screen-reader-text"><?php esc_html_e( 'Core Safety & Client Shield', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>

			<div class="edminboost-anm-feature-block">
				<div class="edminboost-anm-feature-block__header">
					<span class="dashicons dashicons-shield" aria-hidden="true"></span>
					<h3><?php esc_html_e( 'Critical Safety Guard', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h3>
				</div>
				<p><?php esc_html_e( 'Protect WordPress core security alerts, PHP fatal errors, and SSL warnings from accidental filtering.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Safety guard', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
						<td>
							<label for="edminboost-anm-safety-guard">
								<input type="checkbox" id="edminboost-anm-safety-guard" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[safety_guard][enabled]" value="1" <?php checked( ! empty( $settings['safety_guard']['enabled'] ) ); ?> />
								<?php esc_html_e( 'Enable strict protection', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
							</label>
							<input type="hidden" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[safety_guard][mode]" value="strict" />
							<?php if ( ! empty( $settings['safety_guard']['enabled'] ) ) : ?>
								<p class="description"><span class="edminboost-anm-status is-active"><?php esc_html_e( 'ENABLED (Strict Protection)', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></span></p>
							<?php endif; ?>
						</td>
					</tr>
				</table>
			</div>

			<div class="edminboost-anm-feature-block">
				<div class="edminboost-anm-feature-block__header">
					<span class="dashicons dashicons-groups" aria-hidden="true"></span>
					<h3><?php esc_html_e( 'Client Shield Mode', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h3>
				</div>
				<p><?php esc_html_e( 'Hide all plugin/theme update notices and promotional nags from non-admin roles (Editors, Authors, Managers).', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Client shield', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
						<td>
							<label for="edminboost-anm-client-shield">
								<input type="checkbox" id="edminboost-anm-client-shield" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[client_shield][enabled]" value="1" <?php checked( ! empty( $settings['client_shield']['enabled'] ) ); ?> />
								<?php esc_html_e( 'Enable Client Shield Mode', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Roles to shield', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
						<td>
							<?php foreach ( $edminboost_anm_roles as $role_slug => $role_label ) : ?>
								<label style="display:inline-block;margin-right:16px;">
									<input type="checkbox" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[client_shield][roles][]" value="<?php echo esc_attr( $role_slug ); ?>" <?php checked( in_array( $role_slug, $edminboost_anm_shield_roles, true ) ); ?> />
									<?php echo esc_html( $role_label ); ?>
								</label>
							<?php endforeach; ?>
						</td>
					</tr>
				</table>
			</div>

			<?php
			$edminboost_anm_redirect_block      = wp_parse_args(
				$settings['block_ad_redirects'] ?? array(),
				EDMINBOOST_ANM_Settings::get_defaults()['block_ad_redirects']
			);
			$edminboost_anm_redirect_block_urls = $edminboost_anm_redirect_block['urls'] ?? array();
			if ( empty( $edminboost_anm_redirect_block_urls ) ) {
				$edminboost_anm_redirect_block_urls = array( '' );
			}
			?>
			<div class="edminboost-anm-feature-block">
				<div class="edminboost-anm-feature-block__header">
					<span class="dashicons dashicons-admin-links" aria-hidden="true"></span>
					<h3><?php esc_html_e( 'Block ad redirects', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h3>
				</div>
				<p class="description"><?php esc_html_e( 'Some plugins open a page automatically after installation or update to show announcements, promotions, or other information. Enter the URLs of those pages here to prevent them from opening automatically.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Block ad redirects', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
						<td>
							<label for="edminboost-anm-block-ad-redirects">
								<input type="checkbox" id="edminboost-anm-block-ad-redirects" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[block_ad_redirects][enabled]" value="1" <?php checked( ! empty( $edminboost_anm_redirect_block['enabled'] ) ); ?> />
								<?php esc_html_e( 'Prevent listed admin URLs from loading automatically', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
							</label>
						</td>
					</tr>
				</table>
				<table class="widefat edminboost-anm-redirect-block-table" role="presentation">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Enter URL to block', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
							<th scope="col" class="edminboost-anm-redirect-block-table__action"><?php esc_html_e( 'Action', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
						</tr>
					</thead>
					<tbody data-edminboost-anm-redirect-block-rows>
						<?php foreach ( $edminboost_anm_redirect_block_urls as $edminboost_anm_redirect_index => $edminboost_anm_redirect_url ) : ?>
							<tr>
								<td>
									<label class="screen-reader-text" for="<?php echo esc_attr( 'edminboost-anm-redirect-block-' . $edminboost_anm_redirect_index ); ?>"><?php esc_html_e( 'Enter URL to block', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
									<input
										type="text"
										class="large-text code"
										id="<?php echo esc_attr( 'edminboost-anm-redirect-block-' . $edminboost_anm_redirect_index ); ?>"
										name="<?php echo esc_attr( $edminboost_anm_option_name . '[block_ad_redirects][urls][]' ); ?>"
										value="<?php echo esc_attr( $edminboost_anm_redirect_url ); ?>"
										placeholder="<?php esc_attr_e( 'e.g. admin.php?page=my-plugin-welcome', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>"
									/>
								</td>
								<td class="edminboost-anm-redirect-block-table__action">
									<button type="button" class="button-link-delete edminboost-anm-redirect-block-remove"><?php esc_html_e( 'Remove', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p>
					<button type="button" class="button" data-edminboost-anm-add-redirect-block"><?php esc_html_e( 'Add URL', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
				</p>
				<template id="edminboost-anm-redirect-block-row-template">
					<tr>
						<td>
							<label class="screen-reader-text"><?php esc_html_e( 'Enter URL to block', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
							<input
								type="text"
								class="large-text code"
								name="<?php echo esc_attr( $edminboost_anm_option_name . '[block_ad_redirects][urls][]' ); ?>"
								value=""
								placeholder="<?php esc_attr_e( 'e.g. admin.php?page=my-plugin-welcome', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>"
							/>
						</td>
						<td class="edminboost-anm-redirect-block-table__action">
							<button type="button" class="button-link-delete edminboost-anm-redirect-block-remove"><?php esc_html_e( 'Remove', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
						</td>
					</tr>
				</template>
			</div>
		</div>
		</div>

		<div
			id="<?php echo esc_attr( 'edminboost-anm-settings-panel-notifications' ); ?>"
			class="edminboost-anm-tab-panel"
			role="tabpanel"
			data-tab="notifications"
			aria-labelledby="<?php echo esc_attr( 'edminboost-anm-settings-tab-notifications' ); ?>"
			<?php echo 'notifications' !== $edminboost_anm_active_tab ? ' hidden' : ''; ?>
		>
		<div class="edminboost-anm-card">
			<h2 class="screen-reader-text"><?php esc_html_e( 'Notifications & Digests', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>

			<div class="edminboost-anm-feature-block edminboost-anm-digest-layout">
				<div class="edminboost-anm-feature-block__header">
					<span class="dashicons dashicons-email" aria-hidden="true"></span>
					<h3><?php esc_html_e( 'Weekly "Notice Diet" Email Digest', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h3>
				</div>
				<div class="edminboost-anm-digest-layout__columns">
				<div class="edminboost-anm-digest-layout__settings">
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Email digest', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
						<td>
							<label for="edminboost-anm-digest-enabled">
								<input type="checkbox" id="edminboost-anm-digest-enabled" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[digest][enabled]" value="1" <?php checked( ! empty( $settings['digest']['enabled'] ) ); ?> />
								<?php esc_html_e( 'Send a weekly summary report to site admins detailing silenced notices.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="edminboost-anm-digest-recipients"><?php esc_html_e( 'Recipients', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label></th>
						<td>
							<input type="text" id="edminboost-anm-digest-recipients" class="regular-text" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[digest][recipients]" value="<?php echo esc_attr( $settings['digest']['recipients'] ?? '' ); ?>" />
							<p class="description"><?php esc_html_e( 'Comma-separated email addresses.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="edminboost-anm-digest-subject"><?php esc_html_e( 'Email subject', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label></th>
						<td>
							<input type="text" id="edminboost-anm-digest-subject" class="large-text" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[digest][subject]" value="<?php echo esc_attr( $settings['digest']['subject'] ?? '' ); ?>" placeholder="<?php echo esc_attr( EDMINBOOST_ANM_Digest::get_default_subject( wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ) ); ?>" />
							<p class="description">
								<?php
								printf(
									/* translators: %s: placeholder token */
									esc_html__( 'Leave blank for the default subject. Use %s for the site name.', EDMINBOOST_ANM_TEXT_DOMAIN ),
									esc_html( EDMINBOOST_ANM_Digest::SITE_NAME_TOKEN )
								);
								?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="edminboost-anm-digest-intro"><?php esc_html_e( 'Opening message', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label></th>
						<td>
							<textarea id="edminboost-anm-digest-intro" class="large-text" rows="3" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[digest][intro]"><?php echo esc_textarea( $settings['digest']['intro'] ?? '' ); ?></textarea>
							<p class="description">
								<?php
								printf(
									/* translators: %s: placeholder token */
									esc_html__( 'Leave blank for the default opening line. Use %s for the site name.', EDMINBOOST_ANM_TEXT_DOMAIN ),
									esc_html( EDMINBOOST_ANM_Digest::SITE_NAME_TOKEN )
								);
								?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Email sections', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><?php esc_html_e( 'Email sections', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></legend>
								<label for="edminboost-anm-digest-include-stats">
									<input type="checkbox" id="edminboost-anm-digest-include-stats" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[digest][include_stats]" value="1" <?php checked( ! isset( $settings['digest']['include_stats'] ) || ! empty( $settings['digest']['include_stats'] ) ); ?> />
									<?php esc_html_e( 'Include monthly blocked count and active auto-rules', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
								</label>
								<br />
								<label for="edminboost-anm-digest-include-recent">
									<input type="checkbox" id="edminboost-anm-digest-include-recent" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[digest][include_recent_notices]" value="1" <?php checked( ! isset( $settings['digest']['include_recent_notices'] ) || ! empty( $settings['digest']['include_recent_notices'] ) ); ?> />
									<?php esc_html_e( 'Include recent intercepted notices from the inbox', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="edminboost-anm-digest-recent-count"><?php esc_html_e( 'Recent notices limit', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label></th>
						<td>
							<input type="number" id="edminboost-anm-digest-recent-count" class="small-text" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[digest][recent_notices_count]" value="<?php echo esc_attr( (string) ( $settings['digest']['recent_notices_count'] ?? 10 ) ); ?>" min="1" max="25" step="1" />
							<p class="description"><?php esc_html_e( 'How many inbox notices to list when the recent notices section is enabled (1–25).', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="edminboost-anm-digest-footer"><?php esc_html_e( 'Closing message', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label></th>
						<td>
							<textarea id="edminboost-anm-digest-footer" class="large-text" rows="2" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[digest][footer]"><?php echo esc_textarea( $settings['digest']['footer'] ?? '' ); ?></textarea>
							<p class="description">
								<?php
								printf(
									/* translators: %s: placeholder token */
									esc_html__( 'Optional text appended to the email. Use %s for the site name.', EDMINBOOST_ANM_TEXT_DOMAIN ),
									esc_html( EDMINBOOST_ANM_Digest::SITE_NAME_TOKEN )
								);
								?>
							</p>
						</td>
					</tr>
				</table>
				</div>
				<aside class="edminboost-anm-digest-layout__preview" aria-labelledby="edminboost-anm-digest-preview-heading">
					<div id="edminboost-anm-digest-preview" class="edminboost-anm-digest-preview" aria-live="polite">
						<div class="edminboost-anm-digest-preview__toolbar">
							<h4 id="edminboost-anm-digest-preview-heading" class="edminboost-anm-digest-preview__heading"><?php esc_html_e( 'Email preview', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h4>
							<button type="button" class="button button-small" id="edminboost-anm-digest-preview-btn"><?php esc_html_e( 'Refresh', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
						</div>
						<p class="description edminboost-anm-digest-preview__hint"><?php esc_html_e( 'Updates as you edit. Matches the plain-text email WordPress sends each week.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
						<div class="edminboost-anm-digest-preview__mail">
							<dl class="edminboost-anm-digest-preview__meta">
								<div class="edminboost-anm-digest-preview__meta-row">
									<dt><?php esc_html_e( 'To', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></dt>
									<dd data-edminboost-anm-digest-preview-to></dd>
								</div>
								<div class="edminboost-anm-digest-preview__meta-row">
									<dt><?php esc_html_e( 'From', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></dt>
									<dd data-edminboost-anm-digest-preview-from></dd>
								</div>
							</dl>
							<p class="edminboost-anm-digest-preview__subject-line" data-edminboost-anm-digest-preview-subject></p>
							<div class="edminboost-anm-digest-preview__content" data-edminboost-anm-digest-preview-content></div>
						</div>
					</div>
				</aside>
				</div>
			</div>

			<div class="edminboost-anm-feature-block">
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Webhook alerts', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
						<td>
							<label for="edminboost-anm-webhook-enabled">
								<input type="checkbox" id="edminboost-anm-webhook-enabled" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[webhook][enabled]" value="1" <?php checked( ! empty( $settings['webhook']['enabled'] ) ); ?> />
								<?php esc_html_e( 'Enable webhook alert integration (opt-in external transmission)', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="edminboost-anm-webhook-url"><?php esc_html_e( 'Webhook URL', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label></th>
						<td>
							<input type="url" id="edminboost-anm-webhook-url" class="large-text" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[webhook][url]" value="<?php echo esc_attr( $settings['webhook']['url'] ?? '' ); ?>" placeholder="https://hooks.slack.com/services/..." />
							<p class="description"><?php esc_html_e( 'When enabled, notice summaries are sent to this URL (Slack, Discord, etc.). No data is sent unless you enable this option.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="edminboost-anm-webhook-message"><?php esc_html_e( 'Alert message', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label></th>
						<td>
							<textarea id="edminboost-anm-webhook-message" class="large-text" rows="3" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[webhook][message]"><?php echo esc_textarea( $settings['webhook']['message'] ?? '' ); ?></textarea>
							<p class="description">
								<?php esc_html_e( 'Plain-text alert shown in the JSON `text` field (Slack/Discord). Leave blank for the default. Placeholders:', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
								<code><?php echo esc_html( EDMINBOOST_ANM_Webhook::TOKEN_SOURCE ); ?></code>,
								<code><?php echo esc_html( EDMINBOOST_ANM_Webhook::TOKEN_MESSAGE ); ?></code>,
								<code><?php echo esc_html( EDMINBOOST_ANM_Webhook::TOKEN_MESSAGE_EXCERPT ); ?></code>,
								<code><?php echo esc_html( EDMINBOOST_ANM_Webhook::TOKEN_TYPE ); ?></code>,
								<code><?php echo esc_html( EDMINBOOST_ANM_Webhook::TOKEN_SITE ); ?></code>,
								<code><?php echo esc_html( EDMINBOOST_ANM_Webhook::TOKEN_SITE_NAME ); ?></code>
							</p>
						</td>
					</tr>
				</table>
			</div>
		</div>
		</div>

		<?php submit_button(); ?>
	</form>

	<div
		id="<?php echo esc_attr( 'edminboost-anm-settings-panel-tools' ); ?>"
		class="edminboost-anm-tab-panel"
		role="tabpanel"
		data-tab="tools"
		aria-labelledby="<?php echo esc_attr( 'edminboost-anm-settings-tab-tools' ); ?>"
		<?php echo 'tools' !== $edminboost_anm_active_tab ? ' hidden' : ''; ?>
	>
	<div class="edminboost-anm-card">
		<h2 class="screen-reader-text"><?php esc_html_e( 'Migration & Agency Tools', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>

		<div class="edminboost-anm-feature-block">
			<div class="edminboost-anm-feature-block__header">
				<span class="dashicons dashicons-update" aria-hidden="true"></span>
				<h3><?php esc_html_e( 'Competitor Importer', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h3>
			</div>
			<?php if ( ! empty( $legacy ) ) : ?>
				<p><?php esc_html_e( 'Detected legacy plugin data. Click to import existing rules instantly.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
				<p class="edminboost-anm-muted"><?php echo esc_html( implode( ', ', $legacy ) ); ?></p>
			<?php else : ?>
				<p><?php esc_html_e( 'No legacy Admin Notices Manager data detected. You can still run the importer to seed default rules.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
			<?php endif; ?>
			<p><button type="button" class="button button-primary edminboost-anm-run-importer"><?php esc_html_e( 'Run One-Click Importer', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button></p>
		</div>

		<div class="edminboost-anm-feature-block">
			<div class="edminboost-anm-feature-block__header">
				<span class="dashicons dashicons-media-code" aria-hidden="true"></span>
				<h3><?php esc_html_e( 'Agency JSON Configuration', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h3>
			</div>
			<p><?php esc_html_e( 'Export your custom ruleset for client site deployment or upload an existing template JSON file.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
			<p>
				<a href="<?php echo esc_url( $export_url ); ?>" class="button"><?php esc_html_e( 'Export Rules (JSON)', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></a>
			</p>
			<form class="edminboost-anm-import-form" enctype="multipart/form-data">
				<label for="edminboost-anm-import-file"><?php esc_html_e( 'Choose File:', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
				<input type="file" id="edminboost-anm-import-file" name="import_file" accept=".json,application/json" />
				<button type="submit" class="button"><?php esc_html_e( 'Import Rules', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
			</form>
		</div>
	</div>
	</div>
	</div>
</div>
