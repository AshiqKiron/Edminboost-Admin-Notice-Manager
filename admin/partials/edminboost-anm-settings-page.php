<?php
/**
 * Settings page template.
 *
 * @package EdminBoost\AdminNoticeManager
 *
 * @var array $settings Plugin settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$edminboost_anm_option_name = EDMINBOOST_ANM_Settings::OPTION_NAME;
?>
<div class="wrap edminboost-anm-wrap edminboost-anm-settings">
	<header class="edminboost-anm-hero">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<p class="edminboost-anm-hero__lead">
			<?php esc_html_e( 'Configure how admin notices are managed across your WordPress dashboard.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
		</p>
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
							<input
								type="checkbox"
								id="edminboost-anm-enabled"
								name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[enabled]"
								value="1"
								<?php checked( ! empty( $settings['enabled'] ) ); ?>
							/>
							<?php esc_html_e( 'Turn on Admin Notice Manager features', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
						</label>
					</td>
				</tr>
			</table>
		</div>

		<div class="edminboost-anm-card">
			<h2><?php esc_html_e( 'Notice Display', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Hide core notices', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
					<td>
						<label for="edminboost-anm-hide-core-notices">
							<input
								type="checkbox"
								id="edminboost-anm-hide-core-notices"
								name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[hide_core_notices]"
								value="1"
								<?php checked( ! empty( $settings['hide_core_notices'] ) ); ?>
							/>
							<?php esc_html_e( 'Hide default admin notices outside plugin screens', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Admin bar panel', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
					<td>
						<label for="edminboost-anm-notice-panel">
							<input
								type="checkbox"
								id="edminboost-anm-notice-panel"
								name="<?php echo esc_attr( $edminboost_anm_option_name ); ?>[notice_panel][enabled]"
								value="1"
								<?php checked( ! empty( $settings['notice_panel']['enabled'] ) ); ?>
							/>
							<?php esc_html_e( 'Show a Notices shortcut in the admin bar', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
						</label>
					</td>
				</tr>
			</table>
		</div>

		<?php submit_button(); ?>
	</form>
</div>
