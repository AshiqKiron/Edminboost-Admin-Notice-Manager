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
			<?php include EDMINBOOST_ANM_PLUGIN_DIR . 'admin/partials/edminboost-anm-multisite-policy-fields.php'; ?>
		</div>

		<?php include EDMINBOOST_ANM_PLUGIN_DIR . 'admin/partials/edminboost-anm-multisite-policy-next-steps.php'; ?>

		<?php if ( ! empty( $edminboost_anm_network_inbox_rollup ) ) : ?>
			<div class="edminboost-anm-card">
				<h2><?php esc_html_e( 'Network inbox rollup (Pro)', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></h2>
				<p class="description"><?php esc_html_e( 'Sites with the highest open alert counts across the network.', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></p>
				<table class="widefat striped">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Site', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
							<th scope="col"><?php esc_html_e( 'Alerts', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
							<th scope="col"><?php esc_html_e( 'Inbox total', EDMINBOOST_ANM_TEXT_DOMAIN ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $edminboost_anm_network_inbox_rollup as $edminboost_anm_rollup_row ) : ?>
							<tr>
								<td><?php echo esc_html( $edminboost_anm_rollup_row['name'] ?? '' ); ?></td>
								<td><?php echo esc_html( (string) ( $edminboost_anm_rollup_row['alerts'] ?? 0 ) ); ?></td>
								<td><?php echo esc_html( (string) ( $edminboost_anm_rollup_row['total'] ?? 0 ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>

		<?php submit_button(); ?>
	</form>
</div>
