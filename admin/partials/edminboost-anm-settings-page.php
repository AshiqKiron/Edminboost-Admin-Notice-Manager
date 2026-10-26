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
$edminboost_anm_custom_phrases = $settings['upsell_preset']['custom_phrases'] ?? array();
$edminboost_anm_builtin_count  = count( EDMINBOOST_ANM_Rules::get_builtin_preset_phrases() );
?>
<div class="wrap edminboost-anm-wrap edminboost-anm-settings" data-edminboost-anm-settings>
	<header class="edminboost-anm-hero">
		<h1><?php esc_html_e( 'Settings, Security & Migration', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h1>
	</header>

	<form method="post" action="options.php">
		<?php settings_fields( EDMINBOOST_ANM_Settings::SETTINGS_GROUP ); ?>

		<div class="edminboost-anm-card">
			<h2><?php esc_html_e( 'General', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable plugin', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
					<td>
						<label for="edminboost-anm-enabled">
							<input type="checkbox" id="edminboost-anm-enabled" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[enabled]" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?> />
							<?php esc_html_e( 'Turn on Admin Notice Manager features', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Hide core notices', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
					<td>
						<label for="edminboost-anm-hide-core-notices">
							<input type="checkbox" id="edminboost-anm-hide-core-notices" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[hide_core_notices]" value="1" <?php checked( ! empty( $settings['hide_core_notices'] ) ); ?> />
							<?php esc_html_e( 'Hide default admin notices outside plugin screens', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Admin bar panel', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
					<td>
						<label for="edminboost-anm-notice-panel">
							<input type="checkbox" id="edminboost-anm-notice-panel" name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[notice_panel][enabled]" value="1" <?php checked( ! empty( $settings['notice_panel']['enabled'] ) ); ?> />
							<?php esc_html_e( 'Show a Notices shortcut in the admin bar', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
						</label>
					</td>
				</tr>
			</table>
		</div>

		<div class="edminboost-anm-card">
			<h2><?php esc_html_e( 'Upsell Blacklist', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
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
			<h2><?php esc_html_e( 'Core Safety & Client Shield', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>

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
		</div>

		<div class="edminboost-anm-card">
			<h2><?php esc_html_e( 'Notifications & Digests', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>

			<div class="edminboost-anm-feature-block">
				<div class="edminboost-anm-feature-block__header">
					<span class="dashicons dashicons-email" aria-hidden="true"></span>
					<h3><?php esc_html_e( 'Weekly "Notice Diet" Email Digest', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h3>
				</div>
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
				</table>
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
				</table>
			</div>
		</div>

		<?php submit_button(); ?>
	</form>

	<div class="edminboost-anm-card">
		<h2><?php esc_html_e( 'Migration & Agency Tools', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>

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
