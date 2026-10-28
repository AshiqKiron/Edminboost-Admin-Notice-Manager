<?php
/**
 * Multisite policy fields (shared by network admin and site Settings tab).
 *
 * @package EdminBoost\AdminNoticeManager
 *
 * @var array $edminboost_anm_network Network policy option values.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$edminboost_anm_network = EDMINBOOST_ANM_Multisite::normalize_policy_option(
	is_array( $edminboost_anm_network ?? null ) ? $edminboost_anm_network : array()
);
$edminboost_anm_policy_labels = EDMINBOOST_ANM_Multisite::get_policy_labels();
$edminboost_anm_policy          = $edminboost_anm_network['policy'];
?>
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
				<?php esc_html_e( 'Apply network auto-rules on every site', EDMINBOOST_ANM_TEXT_DOMAIN ); ?>
			</label>
		</td>
	</tr>
</table>
