<?php
/**
 * Network multisite policy settings.
 *
 * @package EdminBoost\AdminNoticeManager
 *
 * @var array $edminboost_anm_network Network policy option.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$edminboost_anm_policy_labels = EDMINBOOST_ANM_Multisite::get_policy_labels();
$edminboost_anm_policy        = sanitize_key( $edminboost_anm_network['policy'] ?? 'per_site' );
?>
<div class="wrap edminboost-anm-wrap edminboost-anm-settings">
	<header class="edminboost-anm-hero">
		<h1><?php esc_html_e( 'Multisite policy', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h1>
		<p class="description">
			<?php esc_html_e( 'Control how Notice Manager settings and auto-rules apply across every site on the network.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
		</p>
	</header>

	<form method="post" action="options.php" class="edminboost-anm-settings-form">
		<?php settings_fields( EDMINBOOST_ANM_Multisite::NETWORK_POLICY_GROUP ); ?>

		<div class="edminboost-anm-card">
			<h2><?php esc_html_e( 'Configuration mode', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="edminboost-anm-network-policy"><?php esc_html_e( 'Policy', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></label>
					</th>
					<td>
						<select id="edminboost-anm-network-policy" name="<?php echo esc_attr( EDMINBOOST_ANM_Multisite::NETWORK_OPTION ); ?>[policy]">
							<?php foreach ( $edminboost_anm_policy_labels as $edminboost_anm_policy_slug => $edminboost_anm_policy_label ) : ?>
								<option value="<?php echo esc_attr( $edminboost_anm_policy_slug ); ?>" <?php selected( $edminboost_anm_policy, $edminboost_anm_policy_slug ); ?>>
									<?php echo esc_html( $edminboost_anm_policy_label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<?php esc_html_e( 'Per site: no network layer. Network defaults: sites inherit network defaults and may override. Network managed: sites use network defaults unless you allow overrides below.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Site overrides', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( EDMINBOOST_ANM_Multisite::NETWORK_OPTION ); ?>[allow_site_settings]" value="1" <?php checked( ! empty( $edminboost_anm_network['allow_site_settings'] ) ); ?> />
							<?php esc_html_e( 'Allow site administrators to change plugin settings', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
						</label>
						<br />
						<label>
							<input type="checkbox" name="<?php echo esc_attr( EDMINBOOST_ANM_Multisite::NETWORK_OPTION ); ?>[allow_site_rules]" value="1" <?php checked( ! empty( $edminboost_anm_network['allow_site_rules'] ) ); ?> />
							<?php esc_html_e( 'Allow site administrators to manage site-specific auto-rules', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Shared auto-rules', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( EDMINBOOST_ANM_Multisite::NETWORK_OPTION ); ?>[share_network_rules]" value="1" <?php checked( ! empty( $edminboost_anm_network['share_network_rules'] ) ); ?> />
							<?php esc_html_e( 'Apply network auto-rules on every site (configure rules under Network Auto-Rules)', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
						</label>
					</td>
				</tr>
			</table>
		</div>

		<div class="edminboost-anm-card">
			<h2><?php esc_html_e( 'Next steps', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
			<ul class="ul-disc">
				<li>
					<a href="<?php echo esc_url( EDMINBOOST_ANM_Multisite::get_network_settings_admin_url() ); ?>">
						<?php esc_html_e( 'Edit network default settings', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
					</a>
				</li>
				<li>
					<a href="<?php echo esc_url( network_admin_url( 'admin.php?page=' . EDMINBOOST_ANM_Multisite::NETWORK_PAGE_SLUG . '-rules' ) ); ?>">
						<?php esc_html_e( 'Manage network auto-rules', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
					</a>
				</li>
			</ul>
			<p class="description">
				<?php esc_html_e( 'Each site still keeps its own notice inbox and statistics. Digest emails and webhooks run per site using the effective settings for that site.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
			</p>
		</div>

		<?php submit_button(); ?>
	</form>
</div>
