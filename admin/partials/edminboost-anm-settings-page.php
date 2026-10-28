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

$edminboost_anm_option_name        = $edminboost_anm_option_name ?? EDMINBOOST_ANM_Settings::OPTION_NAME;
$edminboost_anm_settings_group     = $edminboost_anm_settings_group ?? EDMINBOOST_ANM_Settings::SETTINGS_GROUP;
$edminboost_anm_settings_readonly  = ! empty( $edminboost_anm_settings_readonly );
$edminboost_anm_hide_multisite_tab = ! empty( $edminboost_anm_hide_multisite_tab );
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
	'notices'       => __( 'Notices', EDMINBOOST_ANM_TEXT_DOMAIN ),
	'access'        => __( 'Access', EDMINBOOST_ANM_TEXT_DOMAIN ),
	'admin_bar'     => __( 'Admin bar', EDMINBOOST_ANM_TEXT_DOMAIN ),
	'filtering'     => __( 'Filtering', EDMINBOOST_ANM_TEXT_DOMAIN ),
	'security'      => __( 'Security & Shield', EDMINBOOST_ANM_TEXT_DOMAIN ),
	'notifications' => __( 'Notifications', EDMINBOOST_ANM_TEXT_DOMAIN ),
	'white_label'   => __( 'White label', EDMINBOOST_ANM_TEXT_DOMAIN ),
	'tools'         => __( 'Migration', EDMINBOOST_ANM_TEXT_DOMAIN ),
);
if ( ! $edminboost_anm_hide_multisite_tab && EDMINBOOST_ANM_Multisite::is_active() ) {
	$edminboost_anm_settings_tabs['multisite'] = __( 'Multisite', EDMINBOOST_ANM_TEXT_DOMAIN );
}
$edminboost_anm_white_label = wp_parse_args(
	$settings['white_label'] ?? array(),
	EDMINBOOST_ANM_Settings::get_defaults()['white_label']
);
$edminboost_anm_manage_mode              = EDMINBOOST_ANM_Settings::get_manage_notices_mode();
$edminboost_anm_display_presets          = EDMINBOOST_ANM_Settings::get_display_experience_presets();
$edminboost_anm_active_display_preset    = EDMINBOOST_ANM_Settings::get_display_experience_preset_for_mode( $edminboost_anm_manage_mode );
$edminboost_anm_manage_mode_labels       = EDMINBOOST_ANM_Settings::get_manage_notices_mode_labels();
$edminboost_anm_display_experience_open  = '' === $edminboost_anm_active_display_preset;
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

	<?php if ( $edminboost_anm_settings_readonly ) : ?>
		<div class="notice notice-warning">
			<p>
				<?php esc_html_e( 'These settings are managed by your network administrator. Contact a super admin to request changes.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
			</p>
		</div>
	<?php endif; ?>

	<nav class="nav-tab-wrapper wp-clearfix edminboost-anm-settings-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Settings sections', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>">
		<?php foreach ( $edminboost_anm_settings_tabs as $edminboost_anm_tab_slug => $edminboost_anm_tab_label ) : ?>
			<?php
			$edminboost_anm_tab_is_active = $edminboost_anm_active_tab === $edminboost_anm_tab_slug;
			?>
			<button
				type="button"
				class="nav-tab <?php echo $edminboost_anm_tab_is_active ? 'nav-tab-active' : ''; ?>"
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
	<form method="post" action="options.php" class="edminboost-anm-settings-form<?php echo 'tools' === $edminboost_anm_active_tab ? ' is-tab-tools' : ''; ?><?php echo $edminboost_anm_settings_readonly ? ' is-settings-readonly' : ''; ?>">
		<?php settings_fields( $edminboost_anm_settings_group ); ?>
		<fieldset <?php echo $edminboost_anm_settings_readonly ? 'disabled="disabled"' : ''; ?>>

		<div
			id="<?php echo esc_attr( 'edminboost-anm-settings-panel-general' ); ?>"
			class="edminboost-anm-tab-panel"
			role="tabpanel"
			data-tab="general"
			aria-labelledby="<?php echo esc_attr( 'edminboost-anm-settings-tab-general' ); ?>"
			<?php echo 'general' !== $edminboost_anm_active_tab ? ' hidden' : ''; ?>
		>
		<div class="edminboost-anm-card edminboost-anm-display-experience-layout">
			<h2 class="edminboost-anm-display-experience-layout__heading">
				<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Choose how notices appear on the dashboard. Per-type rules live on the Notices tab; who is affected on Access; how to open the inbox on Admin bar.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
				<?php esc_html_e( 'Display experience', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
			</h2>
			<div class="edminboost-anm-display-experience-layout__columns">
				<div class="edminboost-anm-display-experience-layout__settings">
					<input
						type="hidden"
						id="edminboost-anm-manage-notices-mode"
						name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[manage_notices_mode]"
						value="<?php echo esc_attr( $edminboost_anm_manage_mode ); ?>"
					/>
					<fieldset class="edminboost-anm-display-experience" data-edminboost-anm-display-experience>
						<legend class="screen-reader-text"><?php esc_html_e( 'Display experience', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></legend>
						<?php foreach ( $edminboost_anm_display_presets as $edminboost_anm_preset_slug => $edminboost_anm_preset ) : ?>
							<?php
							$edminboost_anm_preset_id    = 'edminboost-anm-display-' . $edminboost_anm_preset_slug;
							$edminboost_anm_preset_mode  = $edminboost_anm_preset['mode'] ?? '';
							$edminboost_anm_preset_class = 'edminboost-anm-display-experience__option';
							if ( $edminboost_anm_active_display_preset === $edminboost_anm_preset_slug ) {
								$edminboost_anm_preset_class .= ' is-selected';
							}
							if ( 'clean_inbox' === $edminboost_anm_preset_slug ) {
								$edminboost_anm_preset_class .= ' is-recommended';
							}
							?>
							<div class="<?php echo esc_attr( $edminboost_anm_preset_class ); ?>">
								<label class="edminboost-anm-display-experience__label" for="<?php echo esc_attr( $edminboost_anm_preset_id ); ?>">
									<input
										type="radio"
										class="edminboost-anm-display-experience__input"
										id="<?php echo esc_attr( $edminboost_anm_preset_id ); ?>"
										name="edminboost_anm_display_experience_preset"
										value="<?php echo esc_attr( $edminboost_anm_preset_slug ); ?>"
										data-manage-mode="<?php echo esc_attr( $edminboost_anm_preset_mode ); ?>"
										<?php checked( $edminboost_anm_active_display_preset, $edminboost_anm_preset_slug ); ?>
									/>
									<span class="edminboost-anm-display-experience__title">
										<?php echo esc_html( $edminboost_anm_preset['label'] ?? '' ); ?>
										<?php if ( 'clean_inbox' === $edminboost_anm_preset_slug ) : ?>
											<span class="edminboost-anm-display-experience__badge"><?php esc_html_e( 'Recommended', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></span>
										<?php endif; ?>
									</span>
									<span class="edminboost-anm-display-experience__description description"><?php echo esc_html( $edminboost_anm_preset['description'] ?? '' ); ?></span>
								</label>
							</div>
						<?php endforeach; ?>
					</fieldset>
					<details class="edminboost-anm-display-experience-advanced" data-edminboost-anm-display-experience-advanced <?php echo $edminboost_anm_display_experience_open ? ' open' : ''; ?>>
						<summary><?php esc_html_e( 'Advanced display options', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></summary>
						<p class="description"><?php esc_html_e( 'Fine-grained modes for power users. Hide all suppresses every notice without inbox capture unless a rule matches first.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
						<fieldset class="edminboost-anm-display-experience-advanced__modes">
							<legend class="screen-reader-text"><?php esc_html_e( 'Advanced notice display mode', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></legend>
							<?php foreach ( $edminboost_anm_manage_mode_labels as $edminboost_anm_mode_slug => $edminboost_anm_mode_label ) : ?>
								<?php $edminboost_anm_advanced_id = 'edminboost-anm-manage-advanced-' . $edminboost_anm_mode_slug; ?>
								<label class="edminboost-anm-display-experience-advanced__mode" for="<?php echo esc_attr( $edminboost_anm_advanced_id ); ?>">
									<input
										type="radio"
										class="edminboost-anm-display-experience-advanced__input"
										id="<?php echo esc_attr( $edminboost_anm_advanced_id ); ?>"
										name="edminboost_anm_display_experience_advanced_mode"
										value="<?php echo esc_attr( $edminboost_anm_mode_slug ); ?>"
										<?php checked( $edminboost_anm_manage_mode === $edminboost_anm_mode_slug && '' === $edminboost_anm_active_display_preset ); ?>
									/>
									<?php echo esc_html( $edminboost_anm_mode_label ); ?>
								</label>
							<?php endforeach; ?>
						</fieldset>
					</details>
				</div>
				<aside class="edminboost-anm-display-experience-layout__preview" aria-labelledby="edminboost-anm-display-experience-preview-heading">
					<div id="edminboost-anm-display-experience-preview" class="edminboost-anm-display-experience-preview edminboost-anm-notice-panel-preview" aria-live="polite">
						<h4 id="edminboost-anm-display-experience-preview-heading" class="edminboost-anm-display-experience-preview__heading"><?php esc_html_e( 'Live preview', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h4>
						<div
							class="edminboost-anm-display-experience-preview__frame"
							data-edminboost-anm-display-experience-preview-frame
							data-preview-mode="<?php echo esc_attr( $edminboost_anm_manage_mode ); ?>"
						>
							<div class="edminboost-anm-display-experience-preview__stage" data-edminboost-anm-display-experience-preview-stage></div>
						</div>
						<p class="edminboost-anm-display-experience-preview__caption description" data-edminboost-anm-display-experience-preview-caption></p>
					</div>
				</aside>
			</div>
		</div>
		<div class="edminboost-anm-card edminboost-anm-general-options-layout" data-edminboost-anm-general-options>
			<h2 class="edminboost-anm-general-options-layout__heading">
				<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Control update nags and restore notices hidden with Hide for me or Hide for all.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
				<?php esc_html_e( 'Update & hidden notices', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
			</h2>
			<div class="edminboost-anm-general-options-layout__columns">
				<div class="edminboost-anm-general-options-layout__settings">
					<table class="form-table" role="presentation">
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
				<aside class="edminboost-anm-general-options-layout__preview" aria-labelledby="edminboost-anm-general-options-preview-heading">
					<div id="edminboost-anm-general-options-preview" class="edminboost-anm-general-options-preview edminboost-anm-display-experience-preview" aria-live="polite">
						<h4 id="edminboost-anm-general-options-preview-heading" class="edminboost-anm-display-experience-preview__heading"><?php esc_html_e( 'Live preview', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h4>
						<div
							class="edminboost-anm-display-experience-preview__frame edminboost-anm-general-options-preview__frame"
							data-edminboost-anm-general-options-preview-frame
							data-hide-plugin="<?php echo $edminboost_anm_disable_plugin_updates ? '1' : '0'; ?>"
							data-hide-core="<?php echo $edminboost_anm_disable_core_updates ? '1' : '0'; ?>"
							data-reset-scope="current_user"
							data-hidden-notice-visible="0"
						>
							<div class="edminboost-anm-display-experience-preview__stage" data-edminboost-anm-general-options-preview-stage></div>
						</div>
						<p class="edminboost-anm-display-experience-preview__caption description" data-edminboost-anm-general-options-preview-caption></p>
					</div>
				</aside>
			</div>
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
		$edminboost_anm_notice_handling_tip = __( 'Used for Clean dashboard and Toolbar only experiences (and advanced Hide selected / Compact panel). Inbox hides on the dashboard and shows in the plugin panel; Hide removes everywhere; Default keeps normal WordPress behavior.', EDMINBOOST_ANM_TEXT_DOMAIN );
		?>
		<div class="edminboost-anm-card">
			<h2><?php esc_html_e( 'Notice handling', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
			<p class="description"><?php esc_html_e( 'Applies when General → Display experience is Clean dashboard + inbox / admin bar or Toolbar only (or matching advanced modes). Choose what happens to each notice type on the dashboard and in the admin bar panel.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
			<h3 class="edminboost-anm-settings-subheading"><?php esc_html_e( 'Standard notices (severity levels)', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h3>
			<table class="form-table edminboost-anm-notice-policy-table edminboost-anm-notice-handling-table" role="presentation">
				<?php foreach ( $edminboost_anm_standard_levels as $edminboost_anm_level_slug => $edminboost_anm_level_label ) : ?>
					<?php
					$edminboost_anm_level_mode = $edminboost_anm_standard_modes[ $edminboost_anm_level_slug ] ?? 'inbox';
					$edminboost_anm_field_id   = 'edminboost-anm-standard-' . $edminboost_anm_level_slug;
					?>
					<tr>
						<th scope="row">
							<?php echo EDMINBOOST_ANM_Admin::help_tip( $edminboost_anm_notice_handling_tip ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
							<label for="<?php echo esc_attr( $edminboost_anm_field_id ); ?>"><?php echo esc_html( $edminboost_anm_level_label ); ?></label>
						</th>
						<td>
							<select id="<?php echo esc_attr( $edminboost_anm_field_id ); ?>" name="<?php echo esc_attr( $edminboost_anm_option_name . '[standard_admin_notices][' . $edminboost_anm_level_slug . ']' ); ?>">
								<?php foreach ( $edminboost_anm_notice_mode_labels as $edminboost_anm_mode_slug => $edminboost_anm_mode_label ) : ?>
									<option value="<?php echo esc_attr( $edminboost_anm_mode_slug ); ?>" <?php selected( $edminboost_anm_level_mode, $edminboost_anm_mode_slug ); ?>><?php echo esc_html( $edminboost_anm_mode_label ); ?></option>
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
						<?php echo EDMINBOOST_ANM_Admin::help_tip( $edminboost_anm_notice_handling_tip ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
						<label for="edminboost-anm-non-standard-no-level"><?php esc_html_e( 'No level notices', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
					</th>
					<td>
						<select id="edminboost-anm-non-standard-no-level" name="<?php echo esc_attr( $edminboost_anm_option_name . '[non_standard_admin_notices][no_level]' ); ?>">
							<?php foreach ( $edminboost_anm_notice_mode_labels as $edminboost_anm_mode_slug => $edminboost_anm_mode_label ) : ?>
								<option value="<?php echo esc_attr( $edminboost_anm_mode_slug ); ?>" <?php selected( $edminboost_anm_no_level_mode, $edminboost_anm_mode_slug ); ?>><?php echo esc_html( $edminboost_anm_mode_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Skip interception for notices matching these jQuery-compatible selectors (comma-separated).', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
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
					</td>
				</tr>
			</table>
		</div>
		</div>

		<div
			id="<?php echo esc_attr( 'edminboost-anm-settings-panel-access' ); ?>"
			class="edminboost-anm-tab-panel"
			role="tabpanel"
			data-tab="access"
			aria-labelledby="<?php echo esc_attr( 'edminboost-anm-settings-tab-access' ); ?>"
			<?php echo 'access' !== $edminboost_anm_active_tab ? ' hidden' : ''; ?>
		>
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
		$edminboost_anm_acl_role_tip    = __( 'Whether users with this role still see intercepted notices on the dashboard.', EDMINBOOST_ANM_TEXT_DOMAIN );
		?>
		<div class="edminboost-anm-card">
			<h2 class="screen-reader-text"><?php esc_html_e( 'Access', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
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
							<?php echo EDMINBOOST_ANM_Admin::help_tip( $edminboost_anm_acl_role_tip ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
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
					<th scope="row">
						<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Refine notice hiding per user account. Role ACL applies first; these options limit which accounts are affected.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
						<?php esc_html_e( 'Visibility', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
					</th>
					<td>
						<fieldset class="edminboost-anm-hiding-visibility-options" data-edminboost-anm-hiding-visibility>
							<legend class="screen-reader-text"><?php esc_html_e( 'Visibility', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></legend>
							<?php foreach ( $edminboost_anm_hiding_modes as $edminboost_anm_hiding_slug => $edminboost_anm_hiding_label ) : ?>
								<?php
								$edminboost_anm_hiding_field_id = 'edminboost-anm-hiding-' . $edminboost_anm_hiding_slug;
								$edminboost_anm_picker_active   = in_array( $edminboost_anm_hiding_slug, array( 'only', 'except' ), true );
								$edminboost_anm_picker_enabled   = $edminboost_anm_hiding_mode === $edminboost_anm_hiding_slug;
								?>
								<div class="edminboost-anm-hiding-visibility-options__option">
									<label class="edminboost-anm-hiding-visibility-options__mode" for="<?php echo esc_attr( $edminboost_anm_hiding_field_id ); ?>">
										<input
											type="radio"
											id="<?php echo esc_attr( $edminboost_anm_hiding_field_id ); ?>"
											name="<?php echo esc_attr( $edminboost_anm_option_name . '[hiding_notifications][mode]' ); ?>"
											value="<?php echo esc_attr( $edminboost_anm_hiding_slug ); ?>"
											<?php checked( $edminboost_anm_hiding_mode, $edminboost_anm_hiding_slug ); ?>
										/>
										<span class="edminboost-anm-hiding-visibility-options__label"><?php echo esc_html( $edminboost_anm_hiding_label ); ?></span>
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
								</div>
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
		$edminboost_anm_panel_style     = $settings['notice_panel']['style'] ?? 'link';
		$edminboost_anm_panel_position  = $settings['notice_panel']['position'] ?? 'default';
		$edminboost_anm_panel_colors    = EDMINBOOST_ANM_Settings::get_notice_panel_colors( $settings['notice_panel'] ?? array() );
		$edminboost_anm_panel_styles    = EDMINBOOST_ANM_Settings::get_notice_panel_styles();
		$edminboost_anm_badge_styles    = EDMINBOOST_ANM_Settings::get_notice_panel_badge_styles();
		$edminboost_anm_panel_positions   = EDMINBOOST_ANM_Settings::get_notice_panel_positions();
		$edminboost_anm_bell_opts_hidden = EDMINBOOST_ANM_Settings::notice_panel_style_uses_bell( $edminboost_anm_panel_style, $edminboost_anm_panel_position ) ? '' : ' hidden';
		?>
		<div class="edminboost-anm-card edminboost-anm-notice-panel-layout">
			<h2 class="screen-reader-text"><?php esc_html_e( 'Admin bar', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
			<div class="edminboost-anm-notice-panel-layout__columns">
			<div class="edminboost-anm-notice-panel-layout__settings">
			<h2 class="edminboost-anm-settings-subheading"><?php esc_html_e( 'Admin bar panel', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
			<p class="description">
				<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Adds a WordPress admin bar entry so users can open captured notices without visiting the Inbox.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
				<?php esc_html_e( 'Configure how captured notices appear in the WordPress admin bar.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
			</p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Choose how the admin bar entry looks: text link, bell icon, or a dropdown panel with recent inbox notices.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
						<label for="edminboost-anm-notice-panel-style"><?php esc_html_e( 'Panel style', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
					</th>
					<td>
						<select id="edminboost-anm-notice-panel-style" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[notice_panel][style]">
							<?php foreach ( $edminboost_anm_panel_styles as $edminboost_anm_style_slug => $edminboost_anm_style_label ) : ?>
								<option value="<?php echo esc_attr( $edminboost_anm_style_slug ); ?>" <?php selected( $edminboost_anm_panel_style, $edminboost_anm_style_slug ); ?>><?php echo esc_html( $edminboost_anm_style_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr class="edminboost-anm-notice-panel-bell-row" <?php echo $edminboost_anm_bell_opts_hidden; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<th scope="row">
						<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'How unread notices appear on the bell icon for bell and dropdown panel styles.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
						<label for="edminboost-anm-notice-panel-badge"><?php esc_html_e( 'Unread indicator', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
					</th>
					<td>
						<select id="edminboost-anm-notice-panel-badge" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[notice_panel][badge_style]">
							<?php foreach ( $edminboost_anm_badge_styles as $edminboost_anm_badge_slug => $edminboost_anm_badge_label ) : ?>
								<option value="<?php echo esc_attr( $edminboost_anm_badge_slug ); ?>" <?php selected( $settings['notice_panel']['badge_style'] ?? 'count', $edminboost_anm_badge_slug ); ?>><?php echo esc_html( $edminboost_anm_badge_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr class="edminboost-anm-notice-panel-bell-row" <?php echo $edminboost_anm_bell_opts_hidden; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<th scope="row">
						<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Colors apply to the bell badge, Howdy dot, dropdown count pill, and footer links.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
						<?php esc_html_e( 'Panel colors', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
					</th>
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
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Choose where the panel appears in the WordPress admin bar — left cluster, top-right area, or beside the profile menu.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
						<label for="edminboost-anm-notice-panel-position"><?php esc_html_e( 'Panel position', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
					</th>
					<td>
						<select id="edminboost-anm-notice-panel-position" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[notice_panel][position]">
							<?php foreach ( $edminboost_anm_panel_positions as $edminboost_anm_position_slug => $edminboost_anm_position_label ) : ?>
								<option value="<?php echo esc_attr( $edminboost_anm_position_slug ); ?>" <?php selected( $edminboost_anm_panel_position, $edminboost_anm_position_slug ); ?>><?php echo esc_html( $edminboost_anm_position_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</table>
			</div>
			<aside class="edminboost-anm-notice-panel-layout__preview" aria-labelledby="edminboost-anm-notice-panel-preview-heading">
				<div id="edminboost-anm-notice-panel-preview" class="edminboost-anm-notice-panel-preview" aria-live="polite">
					<h4 id="edminboost-anm-notice-panel-preview-heading" class="edminboost-anm-notice-panel-preview__heading"><?php esc_html_e( 'Panel preview', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h4>
					<p class="description edminboost-anm-notice-panel-preview__hint"><?php esc_html_e( 'Updates as you change style, position, badge, and colors. Sample notices shown for layout only.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
					<div class="edminboost-anm-notice-panel-preview__stage" data-edminboost-anm-notice-panel-preview-stage></div>
				</div>
			</aside>
			</div>
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
					<th scope="row">
						<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Suppress review requests, promotional nags, and upsell notices that match built-in and custom phrases.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
						<?php esc_html_e( 'Upsell preset', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
					</th>
					<td>
						<label for="edminboost-anm-upsell-preset">
							<input type="checkbox" id="edminboost-anm-upsell-preset" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[upsell_preset][enabled]" value="1" <?php checked( ! empty( $settings['upsell_preset']['enabled'] ) ); ?> />
							<?php esc_html_e( 'Enable "Kill All Upsells" preset', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php
						echo EDMINBOOST_ANM_Admin::help_tip( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper.
							sprintf(
								/* translators: %d: built-in phrase count */
								__( 'One phrase per line. Custom phrases always suppress matching notices. %d built-in phrases apply when "Kill All Upsells" is enabled; duplicates are ignored.', EDMINBOOST_ANM_TEXT_DOMAIN ),
								$edminboost_anm_builtin_count
							)
						);
						?>
						<label for="edminboost-anm-custom-phrases"><?php esc_html_e( 'Custom phrases', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
					</th>
					<td>
						<textarea id="edminboost-anm-custom-phrases" class="large-text code" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[upsell_preset][custom_phrases]" rows="8" placeholder="<?php esc_attr_e( 'One phrase per line, e.g.&#10;try our pro version&#10;limited offer', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>"><?php echo esc_textarea( implode( "\n", $edminboost_anm_custom_phrases ) ); ?></textarea>
						<?php if ( ! empty( $edminboost_anm_custom_phrases ) ) : ?>
							<p class="description">
								<?php
								echo esc_html(
									sprintf(
										/* translators: %d: custom phrase count */
										__( 'You have %d custom phrase(s).', EDMINBOOST_ANM_TEXT_DOMAIN ),
										count( $edminboost_anm_custom_phrases )
									)
								);
								?>
							</p>
						<?php endif; ?>
					</td>
				</tr>
			</table>
		</div>

		<div class="edminboost-anm-card">
			<h2><?php esc_html_e( 'Spam words & whitelist', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Notices containing any of these words (comma-separated) will not be shown on the dashboard.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
						<?php esc_html_e( 'Spam words', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
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
						<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Notices with any whitelist word stay on screen and are not intercepted by this plugin.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
						<?php esc_html_e( 'Whitelist', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
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
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Protect WordPress core security alerts, PHP fatal errors, and SSL warnings from accidental filtering.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
							<?php esc_html_e( 'Safety guard', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
						</th>
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
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Hide plugin and theme update notices and promotional nags from selected non-admin roles.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
							<?php esc_html_e( 'Client shield', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
						</th>
						<td>
							<label for="edminboost-anm-client-shield">
								<input type="checkbox" id="edminboost-anm-client-shield" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[client_shield][enabled]" value="1" <?php checked( ! empty( $settings['client_shield']['enabled'] ) ); ?> />
								<?php esc_html_e( 'Enable Client Shield Mode', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Which roles receive the Client Shield experience when shield mode is enabled.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
							<?php esc_html_e( 'Roles to shield', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
						</th>
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
			<div class="edminboost-anm-feature-block" data-edminboost-anm-redirect-block>
				<div class="edminboost-anm-feature-block__header">
					<span class="dashicons dashicons-admin-links" aria-hidden="true"></span>
					<h3><?php esc_html_e( 'Block ad redirects', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h3>
				</div>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Block admin URLs that plugins open automatically after install or update (announcements, promotions, etc.).', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
							<?php esc_html_e( 'Block ad redirects', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
						</th>
						<td>
							<label for="edminboost-anm-block-ad-redirects">
								<input type="checkbox" id="edminboost-anm-block-ad-redirects" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[block_ad_redirects][enabled]" value="1" <?php checked( ! empty( $edminboost_anm_redirect_block['enabled'] ) ); ?> />
								<?php esc_html_e( 'Prevent listed admin URLs from loading automatically', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Blocked URLs', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
						</th>
						<td>
							<p class="description">
								<?php esc_html_e( 'Some plugins open a page automatically after installation or update to show announcements, promotions, or other information. Enter the URLs of those pages here to prevent them from opening automatically.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
							</p>
							<table class="wp-list-table widefat fixed striped edminboost-anm-redirect-block-table">
								<thead>
									<tr>
										<th scope="col" class="column-primary"><?php esc_html_e( 'URL to block', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
										<th scope="col" class="column-actions"><?php esc_html_e( 'Actions', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
									</tr>
								</thead>
								<tbody data-edminboost-anm-redirect-block-rows="">
									<?php foreach ( $edminboost_anm_redirect_block_urls as $edminboost_anm_redirect_index => $edminboost_anm_redirect_url ) : ?>
										<tr>
											<td class="column-primary" data-colname="<?php esc_attr_e( 'URL to block', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>">
												<label class="screen-reader-text" for="<?php echo esc_attr( 'edminboost-anm-redirect-block-' . $edminboost_anm_redirect_index ); ?>"><?php esc_html_e( 'URL to block', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
												<input
													type="text"
													class="large-text code"
													id="<?php echo esc_attr( 'edminboost-anm-redirect-block-' . $edminboost_anm_redirect_index ); ?>"
													name="<?php echo esc_attr( $edminboost_anm_option_name . '[block_ad_redirects][urls][]' ); ?>"
													value="<?php echo esc_attr( $edminboost_anm_redirect_url ); ?>"
													placeholder="<?php esc_attr_e( 'e.g. admin.php?page=my-plugin-welcome', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>"
												/>
											</td>
											<td class="column-actions" data-colname="<?php esc_attr_e( 'Actions', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>">
												<button type="button" class="button button-secondary edminboost-anm-redirect-block-remove"><?php esc_html_e( 'Remove', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
											</td>
										</tr>
									<?php endforeach; ?>
									<tr class="edminboost-anm-redirect-block-row-template" hidden aria-hidden="true">
										<td class="column-primary">
											<label class="screen-reader-text"><?php esc_html_e( 'URL to block', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
											<input
												type="text"
												class="large-text code"
												name="<?php echo esc_attr( $edminboost_anm_option_name . '[block_ad_redirects][urls][]' ); ?>"
												value=""
												placeholder="<?php esc_attr_e( 'e.g. admin.php?page=my-plugin-welcome', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>"
												disabled
												tabindex="-1"
											/>
										</td>
										<td class="column-actions">
											<button type="button" class="button button-secondary edminboost-anm-redirect-block-remove" tabindex="-1" aria-hidden="true"><?php esc_html_e( 'Remove', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
										</td>
									</tr>
								</tbody>
							</table>
							<p class="edminboost-anm-redirect-block-add">
								<button type="button" class="button button-secondary" data-edminboost-anm-add-redirect-block=""><?php esc_html_e( 'Add URL', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
							</p>
						</td>
					</tr>
				</table>
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
						<th scope="row">
							<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Sends a weekly plain-text email summary of silenced notices via WordPress wp_mail().', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
							<?php esc_html_e( 'Email digest', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
						</th>
						<td>
							<label for="edminboost-anm-digest-enabled">
								<input type="checkbox" id="edminboost-anm-digest-enabled" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[digest][enabled]" value="1" <?php checked( ! empty( $settings['digest']['enabled'] ) ); ?> />
								<?php esc_html_e( 'Send a weekly summary report to site admins detailing silenced notices.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Comma-separated email addresses that receive the weekly digest.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
							<label for="edminboost-anm-digest-recipients"><?php esc_html_e( 'Recipients', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
						</th>
						<td>
							<input type="text" id="edminboost-anm-digest-recipients" class="regular-text" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[digest][recipients]" value="<?php echo esc_attr( $settings['digest']['recipients'] ?? '' ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Day of the week the digest is sent, using your site timezone. Emails go out around 9:00 AM local time on that day.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
							<label for="edminboost-anm-digest-send-weekday"><?php esc_html_e( 'Send on', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
						</th>
						<td>
							<?php
							$edminboost_anm_digest_send_weekday = EDMINBOOST_ANM_Digest::sanitize_send_weekday(
								$settings['digest']['send_weekday'] ?? EDMINBOOST_ANM_Digest::DEFAULT_SEND_WEEKDAY
							);
							?>
							<select id="edminboost-anm-digest-send-weekday" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[digest][send_weekday]">
								<?php foreach ( EDMINBOOST_ANM_Digest::get_send_weekday_choices() as $edminboost_anm_weekday_slug => $edminboost_anm_weekday_label ) : ?>
									<option value="<?php echo esc_attr( $edminboost_anm_weekday_slug ); ?>" <?php selected( $edminboost_anm_digest_send_weekday, $edminboost_anm_weekday_slug ); ?>>
										<?php echo esc_html( $edminboost_anm_weekday_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Leave blank to use the default subject line for the digest email.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
							<label for="edminboost-anm-digest-subject"><?php esc_html_e( 'Email subject', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
						</th>
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
						<th scope="row">
							<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Opening paragraph at the top of the digest. Leave blank for the default text.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
							<label for="edminboost-anm-digest-intro"><?php esc_html_e( 'Opening message', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
						</th>
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
						<th scope="row">
							<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Choose whether the email includes stats, recent inbox notices, or both.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
							<?php esc_html_e( 'Email sections', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
						</th>
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
						<th scope="row">
							<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'How many inbox notices to list when the recent notices section is enabled (1–25).', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
							<label for="edminboost-anm-digest-recent-count"><?php esc_html_e( 'Recent notices limit', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
						</th>
						<td>
							<input type="number" id="edminboost-anm-digest-recent-count" class="small-text" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[digest][recent_notices_count]" value="<?php echo esc_attr( (string) ( $settings['digest']['recent_notices_count'] ?? 10 ) ); ?>" min="1" max="25" step="1" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Optional closing paragraph appended to the digest email.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
							<label for="edminboost-anm-digest-footer"><?php esc_html_e( 'Closing message', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
						</th>
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
								<div class="edminboost-anm-digest-preview__meta-row">
									<dt><?php esc_html_e( 'Next send', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></dt>
									<dd data-edminboost-anm-digest-preview-next-send></dd>
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
						<th scope="row">
							<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Opt-in: POST notice summaries to your URL when notices are captured. Disabled by default; no data is sent until enabled.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
							<?php esc_html_e( 'Webhook alerts', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
						</th>
						<td>
							<label for="edminboost-anm-webhook-enabled">
								<input type="checkbox" id="edminboost-anm-webhook-enabled" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[webhook][enabled]" value="1" <?php checked( ! empty( $settings['webhook']['enabled'] ) ); ?> />
								<?php esc_html_e( 'Enable webhook alert integration (opt-in external transmission)', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'HTTPS endpoint for JSON payloads (Slack, Discord, custom integrations).', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
							<label for="edminboost-anm-webhook-url"><?php esc_html_e( 'Webhook URL', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
						</th>
						<td>
							<input type="url" id="edminboost-anm-webhook-url" class="large-text" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[webhook][url]" value="<?php echo esc_attr( $settings['webhook']['url'] ?? '' ); ?>" placeholder="https://hooks.slack.com/services/..." />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Plain-text alert for the JSON text field. Leave blank for the default; placeholders are listed below.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
							<label for="edminboost-anm-webhook-message"><?php esc_html_e( 'Alert message', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
						</th>
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
							<div id="edminboost-anm-webhook-preview" class="edminboost-anm-webhook-preview" aria-live="polite">
								<div class="edminboost-anm-webhook-preview__toolbar">
									<h4 id="edminboost-anm-webhook-preview-heading" class="edminboost-anm-webhook-preview__heading"><?php esc_html_e( 'Alert preview', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h4>
									<button type="button" class="button button-small" id="edminboost-anm-webhook-preview-btn"><?php esc_html_e( 'Refresh', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
								</div>
								<p class="description edminboost-anm-webhook-preview__hint"><?php esc_html_e( 'Updates as you edit. Placeholders are filled using your latest inbox notice or sample data.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
								<p class="edminboost-anm-webhook-preview__sample" data-edminboost-anm-webhook-preview-sample></p>
								<div class="edminboost-anm-webhook-preview__panel">
									<p class="edminboost-anm-webhook-preview__label"><?php esc_html_e( 'Alert text (`text` field)', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
									<div class="edminboost-anm-webhook-preview__text" data-edminboost-anm-webhook-preview-text></div>
									<p class="edminboost-anm-webhook-preview__label"><?php esc_html_e( 'JSON payload', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
									<pre class="edminboost-anm-webhook-preview__json" data-edminboost-anm-webhook-preview-json></pre>
								</div>
							</div>
						</td>
					</tr>
				</table>
			</div>
		</div>
		</div>

		<div
			id="<?php echo esc_attr( 'edminboost-anm-settings-panel-white_label' ); ?>"
			class="edminboost-anm-tab-panel"
			role="tabpanel"
			data-tab="white_label"
			aria-labelledby="<?php echo esc_attr( 'edminboost-anm-settings-tab-white_label' ); ?>"
			<?php echo 'white_label' !== $edminboost_anm_active_tab ? ' hidden' : ''; ?>
		>
		<div class="edminboost-anm-card edminboost-anm-white-label-layout" data-edminboost-anm-white-label>
			<h2><?php esc_html_e( 'Agency white label', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
			<p class="description"><?php esc_html_e( 'Rebrand the admin menu and dashboard header for client sites. Plugin headers on the Plugins screen are unchanged.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
			<div class="edminboost-anm-white-label-layout__columns">
				<div class="edminboost-anm-white-label-layout__settings">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'When enabled, custom menu and brand settings below replace the default Notice Manager and Edminboost labels in the admin UI.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
						<?php esc_html_e( 'White label mode', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
					</th>
					<td>
						<label for="edminboost-anm-white-label-enabled">
							<input type="checkbox" id="edminboost-anm-white-label-enabled" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[white_label][enabled]" value="1" <?php checked( ! empty( $edminboost_anm_white_label['enabled'] ) ); ?> />
							<?php esc_html_e( 'Enable white label branding', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Replaces the top-level admin menu label. Leave blank to keep “Notice Manager”.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
						<label for="edminboost-anm-white-label-menu-title"><?php esc_html_e( 'Admin menu title', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text" id="edminboost-anm-white-label-menu-title" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[white_label][menu_title]" value="<?php echo esc_attr( $edminboost_anm_white_label['menu_title'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Notice Manager', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Shown in the dashboard top bar when white label is on. Leave blank with no logo to hide the brand area.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
						<label for="edminboost-anm-white-label-brand-name"><?php esc_html_e( 'Dashboard brand name', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text" id="edminboost-anm-white-label-brand-name" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[white_label][brand_name]" value="<?php echo esc_attr( $edminboost_anm_white_label['brand_name'] ?? '' ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Optional logo image URL (HTTPS recommended). Shown beside the brand name on the dashboard.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
						<label for="edminboost-anm-white-label-logo-url"><?php esc_html_e( 'Dashboard logo URL', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
					</th>
					<td>
						<input type="url" class="large-text code" id="edminboost-anm-white-label-logo-url" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[white_label][logo_url]" value="<?php echo esc_attr( $edminboost_anm_white_label['logo_url'] ?? '' ); ?>" placeholder="https://" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Removes the Edminboost brand block from the dashboard top bar entirely.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
						<?php esc_html_e( 'Dashboard brand', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
					</th>
					<td>
						<label for="edminboost-anm-white-label-hide-brand">
							<input type="checkbox" id="edminboost-anm-white-label-hide-brand" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[white_label][hide_brand]" value="1" <?php checked( ! empty( $edminboost_anm_white_label['hide_brand'] ) ); ?> />
							<?php esc_html_e( 'Hide dashboard brand area', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Removes the Docs link from this plugin’s row on the Plugins screen.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
						<?php esc_html_e( 'Plugins screen', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
					</th>
					<td>
						<label for="edminboost-anm-white-label-hide-docs">
							<input type="checkbox" id="edminboost-anm-white-label-hide-docs" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[white_label][hide_docs_link]" value="1" <?php checked( ! empty( $edminboost_anm_white_label['hide_docs_link'] ) ); ?> />
							<?php esc_html_e( 'Hide Docs link', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Optional replacement URL for the Docs link. Leave blank to use the default documentation URL.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
						<label for="edminboost-anm-white-label-docs-url"><?php esc_html_e( 'Custom Docs URL', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
					</th>
					<td>
						<input type="url" class="large-text code" id="edminboost-anm-white-label-docs-url" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[white_label][docs_url]" value="<?php echo esc_attr( $edminboost_anm_white_label['docs_url'] ?? '' ); ?>" placeholder="https://" />
					</td>
				</tr>
			</table>
				</div>
				<aside class="edminboost-anm-white-label-layout__preview" aria-labelledby="edminboost-anm-white-label-preview-heading">
					<div
						id="edminboost-anm-white-label-preview"
						class="edminboost-anm-white-label-preview edminboost-anm-display-experience-preview"
						aria-live="polite"
						data-default-docs-url="<?php echo esc_url( EDMINBOOST_ANM_PLUGIN_DOCS_URL ); ?>"
					>
						<h4 id="edminboost-anm-white-label-preview-heading" class="edminboost-anm-display-experience-preview__heading"><?php esc_html_e( 'Live preview', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h4>
						<p class="description edminboost-anm-white-label-preview__hint"><?php esc_html_e( 'Updates as you edit. Sample layout only — not live admin data.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
						<div class="edminboost-anm-white-label-preview__stage" data-edminboost-anm-white-label-preview-stage></div>
						<p class="edminboost-anm-display-experience-preview__caption description" data-edminboost-anm-white-label-preview-caption></p>
					</div>
				</aside>
			</div>
		</div>
		</div>

		<?php if ( isset( $edminboost_anm_settings_tabs['multisite'] ) ) : ?>
		<div
			id="<?php echo esc_attr( 'edminboost-anm-settings-panel-multisite' ); ?>"
			class="edminboost-anm-tab-panel"
			role="tabpanel"
			data-tab="multisite"
			aria-labelledby="<?php echo esc_attr( 'edminboost-anm-settings-tab-multisite' ); ?>"
			<?php echo 'multisite' !== $edminboost_anm_active_tab ? ' hidden' : ''; ?>
		>
			<div class="edminboost-anm-card">
				<h2><?php esc_html_e( 'Network configuration', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
				<?php
				$edminboost_anm_ms_policy       = EDMINBOOST_ANM_Multisite::get_policy();
				$edminboost_anm_ms_policy_label = EDMINBOOST_ANM_Multisite::get_policy_labels()[ $edminboost_anm_ms_policy ] ?? $edminboost_anm_ms_policy;
				?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Policy', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
						<td><?php echo esc_html( $edminboost_anm_ms_policy_label ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Site settings', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
						<td>
							<?php
							echo esc_html(
								EDMINBOOST_ANM_Multisite::site_can_edit_settings()
									? __( 'Site administrators can customize settings.', EDMINBOOST_ANM_TEXT_DOMAIN )
									: __( 'Settings are locked to network defaults.', EDMINBOOST_ANM_TEXT_DOMAIN )
							);
							?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Auto-rules', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
						<td>
							<?php
							if ( EDMINBOOST_ANM_Multisite::shares_network_rules() ) {
								esc_html_e( 'Network auto-rules are applied on this site.', EDMINBOOST_ANM_TEXT_DOMAIN );
							} else {
								esc_html_e( 'Only site auto-rules apply.', EDMINBOOST_ANM_TEXT_DOMAIN );
							}
							echo ' ';
							echo esc_html(
								EDMINBOOST_ANM_Multisite::site_can_edit_rules()
									? __( 'Site administrators may add site-specific rules.', EDMINBOOST_ANM_TEXT_DOMAIN )
									: __( 'Site rule editing is disabled.', EDMINBOOST_ANM_TEXT_DOMAIN )
							);
							?>
						</td>
					</tr>
				</table>
				<?php if ( current_user_can( EDMINBOOST_ANM_Multisite::NETWORK_CAPABILITY ) ) : ?>
					<p>
						<a class="button button-secondary" href="<?php echo esc_url( EDMINBOOST_ANM_Multisite::get_network_admin_url() ); ?>">
							<?php esc_html_e( 'Open network multisite settings', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
						</a>
					</p>
				<?php endif; ?>
			</div>
		</div>
		<?php endif; ?>

		</fieldset>
		<?php if ( ! $edminboost_anm_settings_readonly ) : ?>
			<?php submit_button(); ?>
		<?php endif; ?>
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
		<h2><?php esc_html_e( 'Competitor Importer', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Import rules from legacy Admin Notices Manager–compatible plugins detected on this site.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
					<?php esc_html_e( 'Legacy import', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
				</th>
				<td>
					<?php if ( ! empty( $legacy ) ) : ?>
						<p><?php esc_html_e( 'Detected legacy plugin data. Click to import existing rules instantly.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
						<p class="description"><?php echo esc_html( implode( ', ', $legacy ) ); ?></p>
					<?php else : ?>
						<p class="description"><?php esc_html_e( 'No legacy Admin Notices Manager data detected. You can still run the importer to seed default rules.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
					<?php endif; ?>
					<p>
						<button type="button" class="button button-primary edminboost-anm-run-importer"><?php esc_html_e( 'Run One-Click Importer', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
					</p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Agency JSON Configuration', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Download your rules as JSON for backup or deployment to client sites.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
					<?php esc_html_e( 'Export', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
				</th>
				<td>
					<p>
						<a href="<?php echo esc_url( $export_url ); ?>" class="button button-secondary"><?php esc_html_e( 'Export Rules (JSON)', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></a>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php echo EDMINBOOST_ANM_Admin::help_tip( __( 'Upload a JSON rules file exported from this plugin or another site.', EDMINBOOST_ANM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
					<label for="edminboost-anm-import-file"><?php esc_html_e( 'Import', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
				</th>
				<td>
					<form class="edminboost-anm-import-form" enctype="multipart/form-data">
						<input type="file" id="edminboost-anm-import-file" name="import_file" accept=".json,application/json" />
						<button type="submit" class="button button-secondary"><?php esc_html_e( 'Import Rules', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></button>
					</form>
				</td>
			</tr>
		</table>
	</div>
	</div>
	</div>
</div>
